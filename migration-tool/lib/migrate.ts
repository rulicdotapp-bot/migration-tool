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
import {
  connect,
  disconnect,
  execQuiet,
  execStdout,
  wp,
  wpQuiet,
  uploadDirectory,
  uploadBuffer,
  type Client,
  type SshConnectionInfo,
} from './ssh-client';
import { extract, mapContent, transform, pushToSite } from './pipeline';

export interface MigrateOptions {
  // Every site has its own hosting account now — pasted into the dashboard
  // form fresh for each run and never persisted anywhere (no Settings page,
  // no env vars, no database). The site's own domain is discovered over
  // this connection (see step 0 below) rather than typed in separately.
  ssh: SshConnectionInfo;
  pageId?: string;
  dryRun?: boolean;
  /** Optional per-site logo (from the dashboard's file input) — used for both the header and footer logo. */
  logo?: { buffer: Buffer; ext: string };
}

export type Logger = (line: string) => void;

function parseWpJson(raw: string): unknown {
  let d = JSON.parse(raw.trim());
  if (typeof d === 'string') d = JSON.parse(d);
  return d;
}

const THEME_SLUG = 'elektricien';
// The theme's bundled default images ship under this same literal prefix
// (assets/images/elektricien-*.webp). Kept as its own constant, separate
// from THEME_SLUG, since the two only coincide today because that's the
// theme's own folder/slug name; the rename step below is what makes them
// diverge correctly once this theme is deployed to a differently-named site.
const DEFAULT_IMAGE_SLUG = 'elektricien';
// process.cwd() (not __dirname) — Next.js consistently sets the working
// directory to the app's own root both in `next dev`/`next build` and in
// the deployed Vercel function, whereas __dirname gets rewritten to
// something meaningless once this code is bundled (that's what broke the
// theme upload with an ENOENT against a nonsense path). The theme folder
// lives inside migration-tool/ itself now, so this is a plain in-project
// path — no extra file-tracing config needed.
const THEME_DIR = path.join(process.cwd(), THEME_SLUG);

export async function migrate(opts: MigrateOptions, log: Logger): Promise<void> {
  const { ssh, pageId, dryRun = false, logo } = opts;

  log(`=== Migrating ${ssh.host}${dryRun ? '  [DRY RUN]' : ''} ===`);

  log('→ Connecting over SSH...');
  const client: Client = await connect(ssh);
  log(`  ✔ connected to ${ssh.host}:${ssh.port} as ${ssh.username}`);

  let wpPath: string | null = null;
  let siteUrl: string | null = null;
  let adminUser: string | null = null;
  let appPassUuid: string | null = null;

  try {
    // ---- 0. Resolve remote WordPress path ----------------------------------
    // No domain is typed in up front — this account is dedicated to one
    // site, so the WordPress install is found by searching for its
    // wp-config.php under $HOME rather than guessing a path from a domain
    // string (which also means this doesn't care whether the host uses an
    // addon-domain layout, a primary-domain layout, or something else).
    log('→ Locating the WordPress install...');
    // $HOME itself is sometimes a symlink into a virtualized/jailed backend
    // path (seen on at least one account: /home/xxxxx -> customer) that a
    // plain `ls`/`find "$HOME"` doesn't resolve correctly when passed as a
    // raw path argument — but `cd` into it does, since that's the normal
    // login path these jails are built to support. So: cd first, then ask
    // the shell what directory it's actually standing in (`pwd -P`,
    // physical/symlink-free) and use THAT absolute path for everything
    // else, rather than trusting the literal "$HOME" string.
    const realHome = await execStdout(client, 'cd "$HOME" 2>/dev/null && pwd -P');
    if (!realHome) {
      throw new Error(`Could not resolve $HOME on this account (cd into it failed).`);
    }
    // execStdout, not execQuiet — `find` commonly exits non-zero just from
    // hitting one unreadable subdirectory under a shared-hosting home dir
    // (mail spools, .cagefs, etc.), even though it still printed the
    // matches we actually want to stdout before that. execQuiet would
    // discard that output entirely and wrongly report "nothing found".
    const findRaw = await execStdout(client, `find "${realHome}" -maxdepth 7 -iname wp-config.php 2>/dev/null`);
    const candidatePaths = (findRaw || '')
      .split('\n')
      .map((p) => p.trim())
      .filter(Boolean)
      .map((p) => path.posix.dirname(p));
    if (!candidatePaths.length) {
      // Self-diagnosing instead of a dead-end error — show what's actually
      // under $HOME so this is fixable from the log console alone, without
      // needing to hand SSH credentials to anyone else to go look.
      const listing = await execStdout(client, `ls -la "${realHome}" 2>/dev/null`);
      throw new Error(
        `No wp-config.php found under $HOME (searched 7 levels deep). ` +
          `$HOME resolves to: ${realHome}\nContents:\n${listing || '(could not list)'}`
      );
    }
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
        `Found wp-config.php but WP-CLI couldn't run against it. Tried: ${candidatePaths.join(', ')}`
      );
    }
    siteUrl = (await wp(client, wpPath, 'option get home')).replace(/\/$/, '');
    log(`  ✔ site URL: ${siteUrl}`);
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
      log(`=== ✔ ${siteUrl} dry-run complete (nothing changed on the site) ===`);
      return;
    }

    // ---- 2. Upload the theme --------------------------------------------
    log('→ Uploading theme...');
    await execQuiet(client, `rm -rf '${remoteThemePath}'`);
    const fileCount = await uploadDirectory(client, THEME_DIR, remoteThemePath);
    log(`  ✔ ${fileCount} theme file(s) uploaded to ${remoteThemePath}`);

    // The theme's static default images ship under the theme's own default
    // slug (assets/images/elektricien-*). functions.php computes
    // theme_image_slug() at render time as sanitize_title(get_bloginfo('name')),
    // so for this new site's own name to produce matching filenames, the
    // bundled files need renaming to that same slug right after upload —
    // using WP-CLI's own sanitize_title() call keeps the two sides in
    // agreement even if WordPress's slugify rules ever change.
    log("→ Renaming default images to match this site's title...");
    const blogName = await wp(client, wpPath, 'option get blogname');
    const imageSlug = await wp(client, wpPath, `eval 'echo sanitize_title( get_option( "blogname" ) );'`);
    const remoteImagesDir = `${remoteThemePath}/assets/images`;
    if (imageSlug && imageSlug !== DEFAULT_IMAGE_SLUG) {
      await execQuiet(
        client,
        `for f in '${remoteImagesDir}'/${DEFAULT_IMAGE_SLUG}-*; do [ -e "$f" ] && mv "$f" "${remoteImagesDir}/${imageSlug}-$(basename "$f" | sed 's/^${DEFAULT_IMAGE_SLUG}-//')"; done`
      );
      log(`  ✔ images renamed to "${imageSlug}-*" (from site title "${blogName}")`);
    } else {
      log(`  ✔ site title already matches "${DEFAULT_IMAGE_SLUG}" — no rename needed`);
    }

    // Optional per-site logo from the dashboard's file input — overrides the
    // bundled default logo images (any extension: theme_static_image_url()
    // on the PHP side finds the file regardless of extension). Used for both
    // the header and footer logo slots, since the form only collects one.
    if (logo) {
      log('→ Uploading custom logo...');
      const ext = logo.ext.replace(/[^a-z0-9]/gi, '').toLowerCase() || 'webp';
      await execQuiet(client, `rm -f '${remoteImagesDir}'/${imageSlug}-logo.* '${remoteImagesDir}'/${imageSlug}-logo-footer.*`);
      await uploadBuffer(client, logo.buffer, `${remoteImagesDir}/${imageSlug}-logo.${ext}`);
      await uploadBuffer(client, logo.buffer, `${remoteImagesDir}/${imageSlug}-logo-footer.${ext}`);
      log(`  ✔ logo uploaded as ${imageSlug}-logo.${ext} (header) and ${imageSlug}-logo-footer.${ext} (footer)`);
    }

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

    const siteHost = (siteUrl as string).replace(/^https?:\/\//, '');
    const siteContent = mapContent({ widgets: extracted.widgets as unknown[], site: siteHost });
    log('  ✔ mapped to site-content');

    const themeFields = transform({ siteContent: siteContent as Record<string, unknown> });
    log(`  ✔ transformed → ${Object.keys(themeFields.fields).length} field(s)`);

    const pushResult = await pushToSite({
      payload: themeFields,
      url: siteUrl as string,
      user: adminUser,
      pass: password,
    });
    log(`  ✔ pushed — updated (${pushResult.updated.length}): ${pushResult.updated.join(', ')}`);
    if (pushResult.skipped?.length) log(`  ⚠ skipped (not registered on theme): ${pushResult.skipped.join(', ')}`);
    if (pushResult.errors?.length) log(`  ✗ errors: ${JSON.stringify(pushResult.errors)}`);

    // Best-effort cache purge — a re-migration can overwrite a static
    // asset (e.g. a replaced logo) at the exact same filename, and a
    // full-page cache plugin (common on shared/LiteSpeed hosting) can keep
    // serving old HTML that embeds the old reference regardless of the
    // file itself changing. Each of these no-ops harmlessly via execQuiet
    // if that particular plugin isn't installed on this site.
    await execQuiet(client, `wp --path='${wpPath}' cache flush`);
    await execQuiet(client, `wp --path='${wpPath}' litespeed-purge all`);
    await execQuiet(client, `wp --path='${wpPath}' w3tc flush all`);
    await execQuiet(client, `wp --path='${wpPath}' rocket clean --confirm`);

    log(`=== ✔ ${siteUrl} migrated ===`);
  } finally {
    if (adminUser && appPassUuid && wpPath) {
      await execQuiet(client, `wp --path='${wpPath}' user application-password delete ${adminUser} ${appPassUuid}`);
      log('  ✔ temporary application password revoked');
    }
    disconnect(client);
  }
}
