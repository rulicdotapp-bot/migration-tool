#!/usr/bin/env node
/**
 * migrate.js — One-command remote migration
 * -------------------------------------------
 * Given just a domain, SSHes into its SiteGround hosting account, pulls the
 * live Elementor content (home page + global widget templates + header/footer
 * Theme Builder templates), installs this repo's hardcoded theme + required
 * plugins, then runs the full extract → map → transform → push pipeline
 * straight into the freshly-installed theme.
 *
 * Requirements:
 *  - `ssh <domain>` must already work on its own — add a Host entry in
 *    ~/.ssh/config for this domain (hostname/port/user), so this script
 *    never needs to know those details.
 *  - Standard SiteGround layout: WordPress at either
 *    ~/www/<domain>/public_html (addon domain) or ~/public_html (the
 *    account's primary domain) — both are tried. WP-CLI must be on PATH.
 *
 * Usage:
 *   node migrate.js <domain> [--page-id=123] [--dry-run] [--keep-temp]
 *
 *   --page-id=123   Use this post ID as the home page instead of relying on
 *                   the "page_on_front" option (needed if the site's reading
 *                   settings aren't set to a static front page).
 *   --dry-run       Fetch and preview only — skip every mutating step
 *                   (theme/plugin install, activation, and the content push).
 *   --keep-temp     Don't delete the temp workspace afterwards (for
 *                   inspecting the fetched/generated JSON).
 *
 * Everything intermediate (fetched Elementor JSON, extracted/site-content/
 * theme-fields files) lives in a throwaway OS temp directory for this run.
 */

const { execFileSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

// Many of the 250 sites share underlying server IPs, so a *new* hostname
// pointing at an already-known key is common and expected here — accept-new
// trusts first-time hosts automatically without prompting, while still
// rejecting a host outright if a previously-seen one's key ever changes.
const SSH_OPTS = ['-o', 'StrictHostKeyChecking=accept-new'];

function sh(sshTarget, remoteCmd) {
  return execFileSync('ssh', [...SSH_OPTS, sshTarget, remoteCmd], { encoding: 'utf8' }).trim();
}

function shQuiet(sshTarget, remoteCmd) {
  try { return sh(sshTarget, remoteCmd); } catch { return null; }
}

function scpUp(sshTarget, localPath, remoteDir) {
  execFileSync('scp', [...SSH_OPTS, '-r', localPath, `${sshTarget}:${remoteDir}`], { stdio: 'inherit' });
}

function run(cmd, args, extraEnv) {
  console.log(`  $ ${cmd} ${args.map((a) => (a.includes(' ') ? `"${a}"` : a)).join(' ')}`);
  execFileSync(cmd, args, { stdio: 'inherit', env: { ...process.env, ...extraEnv } });
}

function wp(sshTarget, wpPath, cliArgs) {
  return sh(sshTarget, `wp --path='${wpPath}' ${cliArgs}`);
}

function wpQuiet(sshTarget, wpPath, cliArgs) {
  return shQuiet(sshTarget, `wp --path='${wpPath}' ${cliArgs}`);
}

function main() {
  const args = process.argv.slice(2);
  const domain = args.find((a) => !a.startsWith('--'));
  const dryRun = args.includes('--dry-run');
  const keepTemp = args.includes('--keep-temp');
  const pageIdArg = args.find((a) => a.startsWith('--page-id='));
  const explicitPageId = pageIdArg ? pageIdArg.split('=')[1] : null;

  if (!domain) {
    console.error('Usage: node migrate.js <domain> [--page-id=123] [--dry-run] [--keep-temp]');
    process.exit(1);
  }

  const sshTarget = domain; // relies on a matching Host entry in ~/.ssh/config
  const themeDir = path.resolve(__dirname, 'elektricien-amstelveen');
  const themeSlug = path.basename(themeDir);

  console.log(`\n=== Migrating ${domain}${dryRun ? '  [DRY RUN]' : ''} ===\n`);

  // ---- 0. Resolve remote paths -------------------------------------------
  console.log('→ Resolving remote WordPress path...');
  const remoteHome = sh(sshTarget, 'echo $HOME');
  const candidatePaths = [
    `${remoteHome}/www/${domain}/public_html`, // addon domain
    `${remoteHome}/public_html`,                // account's primary domain
  ];
  let wpPath = null;
  for (const p of candidatePaths) {
    const v = wpQuiet(sshTarget, p, 'core version');
    if (v) { wpPath = p; console.log(`  ✔ WordPress ${v} found at ${p}`); break; }
  }
  if (!wpPath) {
    console.error(`✗ Could not find a WP-CLI-reachable WordPress install for ${domain}.`);
    console.error(`  Tried: ${candidatePaths.join(', ')}`);
    process.exit(1);
  }
  const remoteThemesDir = `${wpPath}/wp-content/themes`;
  const remoteThemePath = `${remoteThemesDir}/${themeSlug}`;

  // ---- 1. Temp workspace ---------------------------------------------------
  const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), `sitegen-${domain}-`));
  const templatesDir = path.join(tmpDir, 'templates');
  fs.mkdirSync(templatesDir);
  console.log(`→ Working in ${tmpDir}`);

  let adminUser = null;
  let appPass = null;

  try {
    // ---- 2. Fetch Elementor content off the live site ---------------------
    console.log('\n→ Fetching Elementor templates (global widgets + header/footer)...');
    const libraryIdsRaw = wp(sshTarget, wpPath, 'post list --post_type=elementor_library --fields=ID --format=ids');
    const libraryIds = libraryIdsRaw.split(/\s+/).filter(Boolean);

    let headerSaved = false;
    let footerSaved = false;
    for (const id of libraryIds) {
      const type = wpQuiet(sshTarget, wpPath, `post meta get ${id} _elementor_template_type`) || '';
      const data = wpQuiet(sshTarget, wpPath, `post meta get ${id} _elementor_data`);
      if (!data) continue;

      if (type === 'header' && !headerSaved) {
        fs.writeFileSync(path.join(tmpDir, 'header.json'), data);
        headerSaved = true;
        console.log(`  ✔ header template (post ${id})`);
      } else if (type === 'footer' && !footerSaved) {
        fs.writeFileSync(path.join(tmpDir, 'footer.json'), data);
        footerSaved = true;
        console.log(`  ✔ footer template (post ${id})`);
      } else if (type !== 'header' && type !== 'footer') {
        fs.writeFileSync(path.join(templatesDir, `${id}.json`), data);
      }
    }
    console.log(`  ✔ ${fs.readdirSync(templatesDir).length} global widget template(s) saved`);
    if (!headerSaved) console.log('  ⚠ no header Theme Builder template found (continuing without one)');
    if (!footerSaved) console.log('  ⚠ no footer Theme Builder template found (continuing without one)');

    console.log('\n→ Fetching home page content...');
    let homePageId = explicitPageId;
    if (!homePageId) {
      const showOnFront = wp(sshTarget, wpPath, 'option get show_on_front');
      if (showOnFront !== 'page') {
        console.error(`✗ This site's reading settings aren't a static front page (show_on_front=${showOnFront}).`);
        console.error('  Re-run with --page-id=<id> to point at the right page explicitly.');
        process.exit(1);
      }
      homePageId = wp(sshTarget, wpPath, 'option get page_on_front');
    }
    const pageData = wpQuiet(sshTarget, wpPath, `post meta get ${homePageId} _elementor_data`);
    if (!pageData) {
      console.error(`✗ Post ${homePageId} has no _elementor_data — is it really an Elementor page?`);
      process.exit(1);
    }
    fs.writeFileSync(path.join(tmpDir, 'page.json'), pageData);
    console.log(`  ✔ home page (post ${homePageId}) saved`);

    if (dryRun) {
      console.log('\n[dry-run] Would now: upload theme, install ACF Pro + Contact Form 7, activate theme,');
      console.log('[dry-run] create the hero contact form, mint an application password, and push content.');
      console.log(`\n=== ✔ ${domain} dry-run complete (nothing changed on the site) ===\n`);
      return;
    }

    // ---- 3. Upload the theme (files only — activated after plugins) -------
    console.log('\n→ Uploading theme...');
    shQuiet(sshTarget, `rm -rf '${remoteThemePath}'`);
    scpUp(sshTarget, themeDir, remoteThemesDir + '/');
    console.log(`  ✔ theme files uploaded to ${remoteThemePath}`);

    // ---- 4. Install required plugins ---------------------------------------
    console.log('\n→ Installing required plugins...');
    wp(sshTarget, wpPath, `plugin install '${remoteThemePath}/plugins/advanced-custom-fields-pro.zip' --activate --force`);
    console.log('  ✔ Advanced Custom Fields Pro');
    wp(sshTarget, wpPath, 'plugin install contact-form-7 --activate');
    console.log('  ✔ Contact Form 7');

    // ---- 5. Activate the theme + create the hero contact form -------------
    console.log('\n→ Activating theme...');
    wp(sshTarget, wpPath, `theme activate ${themeSlug}`);
    console.log(`  ✔ "${themeSlug}" active`);
    wp(sshTarget, wpPath, "eval 'mytheme_create_hero_contact_form();'");
    console.log('  ✔ hero contact form ready');

    // ---- 6. Mint a one-off Application Password ----------------------------
    console.log('\n→ Creating a temporary Application Password...');
    adminUser = wp(sshTarget, wpPath, 'user list --role=administrator --field=user_login --format=csv')
      .split('\n')[0].trim();
    if (!adminUser) {
      console.error('✗ No administrator user found on this site.');
      process.exit(1);
    }
    // `create` only supports --porcelain (just the password) on some WP-CLI
    // versions, not --format=json — so look the UUID up separately via
    // `list`, matching on the name we just created, to get both values.
    const appPassName = `migration-${Date.now()}`;
    const password = wp(sshTarget, wpPath, `user application-password create ${adminUser} ${appPassName} --porcelain`);
    const listJson = wp(sshTarget, wpPath, `user application-password list ${adminUser} --format=json`);
    const created = JSON.parse(listJson).find((p) => p.name === appPassName);
    if (!created) {
      console.error(`✗ Created an application password but couldn't find its UUID in the list (name: ${appPassName}).`);
      process.exit(1);
    }
    appPass = { password, uuid: created.uuid };
    console.log(`  ✔ application password created for ${adminUser}`);

    // ---- 7. Run the local pipeline ------------------------------------------
    console.log('\n→ Running extract → map → transform → push...');
    const extractArgs = [
      path.join(__dirname, 'extract.js'),
      path.join(tmpDir, 'page.json'),
      path.join(tmpDir, 'extracted.json'),
      `--templates=${templatesDir}`,
    ];
    if (headerSaved) extractArgs.push(`--header=${path.join(tmpDir, 'header.json')}`);
    if (footerSaved) extractArgs.push(`--footer=${path.join(tmpDir, 'footer.json')}`);
    run('node', extractArgs);

    run('node', [
      path.join(__dirname, 'map.js'),
      path.join(tmpDir, 'extracted.json'),
      path.join(tmpDir, 'site-content.json'),
      `--site=${domain}`,
    ]);

    run('node', [
      path.join(__dirname, 'transform.js'),
      path.join(tmpDir, 'site-content.json'),
      path.join(tmpDir, 'theme-fields.json'),
    ]);

    run('node', [path.join(__dirname, 'push.js'), path.join(tmpDir, 'theme-fields.json')], {
      WP_URL: `https://${domain}`,
      WP_USER: adminUser,
      WP_APP_PASSWORD: appPass.password,
    });

    console.log(`\n=== ✔ ${domain} migrated ===\n`);
  } finally {
    if (adminUser && appPass) {
      wpQuiet(sshTarget, wpPath, `user application-password delete ${adminUser} ${appPass.uuid}`);
      console.log('  ✔ temporary application password revoked');
    }
    if (keepTemp) {
      console.log(`(kept temp workspace: ${tmpDir})`);
    } else {
      fs.rmSync(tmpDir, { recursive: true, force: true });
    }
  }
}

main();
