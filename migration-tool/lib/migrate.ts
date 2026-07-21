/**
 * Ported orchestrator — same 8 steps as the repo-root migrate.js CLI
 * (resolve WP path → fetch Elementor content → upload theme → install
 * plugins → activate theme + hero form → mint app password → run the
 * pipeline → push), rewritten against ssh-client.ts's ssh2-based exec/SFTP
 * instead of shelling out to `ssh`/`scp`, and calling the reused
 * extract/map/transform/push pipeline functions directly in-process instead
 * of spawning child `node` processes against temp files.
 *
 * Every step logs through the injected `log()` callback so the API route
 * can stream progress to the browser as it happens.
 */
import path from 'node:path';
import { findAccountForDomain } from './ssh-accounts';
import { connect, disconnect, exec, execQuiet, wp, wpQuiet, uploadDirectory, type Client } from './ssh-client';
import { extract, mapContent, transform, pushToSite } from './pipeline';

export interface MigrateOptions {
  domain: string;
  pageId?: string;
  dryRun?: boolean;
}

export type Logger = (line: string) => void;

function parseWpJson(raw: string): unknown {
  let d = JSON.parse(raw.trim());
  if (typeof d === 'string') d = JSON.parse(d);
  return d;
}

const THEME_SLUG = 'elektricien-amstelveen';
// migration-tool/lib -> migration-tool -> repo root -> elektricien-amstelveen.
// Bundled into the deployed function via next.config.js's
// outputFileTracingIncludes (this is a runtime fs walk, not a static
// import, so the tracer can't discover it on its own).
const THEME_DIR = path.join(__dirname, '..', '..', THEME_SLUG);

export async function migrate(opts: MigrateOptions, log: Logger): Promise<void> {
  const { domain, pageId, dryRun = false } = opts;

  const account = findAccountForDomain(domain);
  if (!account) {
    throw new Error(`"${domain}" is not in the allow-listed SSH_ACCOUNTS_JSON — refusing to connect.`);
  }

  log(`=== Migrating ${domain}${dryRun ? '  [DRY RUN]' : ''} ===`);

  log('→ Connecting over SSH...');
  const client: Client = await connect(account);
  log(`  ✔ connected to ${account.host}:${account.port} as ${account.username}`);

  let wpPath: string | null = null;
  let adminUser: string | null = null;
  let appPassUuid: string | null = null;

  try {
    // ---- 0. Resolve remote WordPress path ----------------------------------
    log('→ Resolving remote WordPress path...');
    const remoteHome = await exec(client, 'echo $HOME');
    const candidatePaths = [
      `${remoteHome}/www/${domain}/public_html`, // addon domain
      `${remoteHome}/public_html`,                // account's primary domain
    ];
    for (const p of candidatePaths) {
      const v = await wpQuiet(client, p, 'core version');
      if (v) {
        wpPath = p;
        log(`  ✔ WordPress ${v} found at ${p}`);
        break;
      }
    }
    if (!wpPath) {
      throw new Error(
        `Could not find a WP-CLI-reachable WordPress install for ${domain}. Tried: ${candidatePaths.join(', ')}`
      );
    }
    const remoteThemesDir = `${wpPath}/wp-content/themes`;
    const remoteThemePath = `${remoteThemesDir}/${THEME_SLUG}`;

    // ---- 1. Fetch Elementor content off the live site ----------------------
    log('→ Fetching Elementor templates (global widgets + header/footer)...');
    const libraryIdsRaw = await wp(client, wpPath, 'post list --post_type=elementor_library --fields=ID --format=ids');
    const libraryIds = libraryIdsRaw.split(/\s+/).filter(Boolean);

    let header: unknown = null;
    let footer: unknown = null;
    const templates: Record<string, unknown> = {};
    let headerSaved = false;
    let footerSaved = false;

    for (const id of libraryIds) {
      const type = (await wpQuiet(client, wpPath, `post meta get ${id} _elementor_template_type`)) || '';
      const data = await wpQuiet(client, wpPath, `post meta get ${id} _elementor_data`);
      if (!data) continue;

      if (type === 'header' && !headerSaved) {
        header = parseWpJson(data);
        headerSaved = true;
        log(`  ✔ header template (post ${id})`);
      } else if (type === 'footer' && !footerSaved) {
        footer = parseWpJson(data);
        footerSaved = true;
        log(`  ✔ footer template (post ${id})`);
      } else if (type !== 'header' && type !== 'footer') {
        templates[id] = parseWpJson(data);
      }
    }
    log(`  ✔ ${Object.keys(templates).length} global widget template(s) fetched`);
    if (!headerSaved) log('  ⚠ no header Theme Builder template found (continuing without one)');
    if (!footerSaved) log('  ⚠ no footer Theme Builder template found (continuing without one)');

    log('→ Fetching home page content...');
    let homePageId = pageId || null;
    if (!homePageId) {
      const showOnFront = await wp(client, wpPath, 'option get show_on_front');
      if (showOnFront !== 'page') {
        throw new Error(
          `This site's reading settings aren't a static front page (show_on_front=${showOnFront}). Re-run with an explicit page ID.`
        );
      }
      homePageId = await wp(client, wpPath, 'option get page_on_front');
    }
    const pageDataRaw = await wpQuiet(client, wpPath, `post meta get ${homePageId} _elementor_data`);
    if (!pageDataRaw) {
      throw new Error(`Post ${homePageId} has no _elementor_data — is it really an Elementor page?`);
    }
    const pageData = parseWpJson(pageDataRaw);
    log(`  ✔ home page (post ${homePageId}) fetched`);

    if (dryRun) {
      log('[dry-run] Would now: upload theme, install ACF Pro + Contact Form 7, activate theme,');
      log('[dry-run] create the hero contact form, mint an application password, and push content.');
      log(`=== ✔ ${domain} dry-run complete (nothing changed on the site) ===`);
      return;
    }

    // ---- 2. Upload the theme --------------------------------------------
    log('→ Uploading theme...');
    await execQuiet(client, `rm -rf '${remoteThemePath}'`);
    const fileCount = await uploadDirectory(client, THEME_DIR, remoteThemePath);
    log(`  ✔ ${fileCount} theme file(s) uploaded to ${remoteThemePath}`);

    // ---- 3. Install required plugins ------------------------------------
    log('→ Installing required plugins...');
    await wp(client, wpPath, `plugin install '${remoteThemePath}/plugins/advanced-custom-fields-pro.zip' --activate --force`);
    log('  ✔ Advanced Custom Fields Pro');
    await wp(client, wpPath, 'plugin install contact-form-7 --activate');
    log('  ✔ Contact Form 7');

    // ---- 4. Activate the theme + create the hero contact form -----------
    log('→ Activating theme...');
    await wp(client, wpPath, `theme activate ${THEME_SLUG}`);
    log(`  ✔ "${THEME_SLUG}" active`);
    await wp(client, wpPath, "eval 'mytheme_create_hero_contact_form();'");
    log('  ✔ hero contact form ready');

    // ---- 5. Mint a one-off Application Password --------------------------
    log('→ Creating a temporary Application Password...');
    const userListCsv = await wp(client, wpPath, 'user list --role=administrator --field=user_login --format=csv');
    adminUser = userListCsv.split('\n')[0].trim();
    if (!adminUser) {
      throw new Error('No administrator user found on this site.');
    }

    // Self-heal: if an earlier run timed out before its `finally` block
    // could revoke its app password, clean up leftovers before minting a
    // fresh one, so timeouts don't leave orphaned passwords accumulating.
    const existingRaw = await wpQuiet(client, wpPath, `user application-password list ${adminUser} --format=json`);
    if (existingRaw) {
      try {
        const existing = JSON.parse(existingRaw) as Array<{ name: string; uuid: string }>;
        for (const p of existing) {
          if (p.name && p.name.startsWith('migration-')) {
            await execQuiet(client, `wp --path='${wpPath}' user application-password delete ${adminUser} ${p.uuid}`);
          }
        }
      } catch {
        // best-effort cleanup only — never block the run over this
      }
    }

    const appPassName = `migration-${Date.now()}`;
    const password = await wp(client, wpPath, `user application-password create ${adminUser} ${appPassName} --porcelain`);
    const listJson = await wp(client, wpPath, `user application-password list ${adminUser} --format=json`);
    const created = (JSON.parse(listJson) as Array<{ name: string; uuid: string }>).find((p) => p.name === appPassName);
    if (!created) {
      throw new Error(`Created an application password but couldn't find its UUID in the list (name: ${appPassName}).`);
    }
    appPassUuid = created.uuid;
    log(`  ✔ application password created for ${adminUser}`);

    // ---- 6. Run the pipeline in-process, then push -----------------------
    log('→ Running extract → map → transform → push...');
    const extracted = extract({ pageData, header, footer, templates });
    log(`  ✔ extracted ${(extracted.widgets as unknown[]).length} widget(s)`);

    const siteContent = mapContent({ widgets: extracted.widgets as unknown[], site: domain });
    log('  ✔ mapped to site-content');

    const themeFields = transform({ siteContent: siteContent as Record<string, unknown> });
    log(`  ✔ transformed → ${Object.keys(themeFields.fields).length} field(s)`);

    const pushResult = await pushToSite({
      payload: themeFields,
      url: `https://${domain}`,
      user: adminUser,
      pass: password,
    });
    log(`  ✔ pushed — updated (${pushResult.updated.length}): ${pushResult.updated.join(', ')}`);
    if (pushResult.skipped?.length) log(`  ⚠ skipped (not registered on theme): ${pushResult.skipped.join(', ')}`);
    if (pushResult.errors?.length) log(`  ✗ errors: ${JSON.stringify(pushResult.errors)}`);

    log(`=== ✔ ${domain} migrated ===`);
  } finally {
    if (adminUser && appPassUuid && wpPath) {
      await execQuiet(client, `wp --path='${wpPath}' user application-password delete ${adminUser} ${appPassUuid}`);
      log('  ✔ temporary application password revoked');
    }
    disconnect(client);
  }
}
