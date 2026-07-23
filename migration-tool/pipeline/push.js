#!/usr/bin/env node
/**
 * Step 4 — Push to a site
 * -----------------------
 * Sends theme-fields.json to the theme's import endpoint.
 *
 * Usage:
 *   node push.js <theme-fields.json> --url=https://elektriciendordrecht.nl \
 *                --user=admin --pass="xxxx xxxx xxxx xxxx xxxx xxxx"
 *
 * Or with env vars: WP_URL, WP_USER, WP_APP_PASSWORD
 *
 * Verify afterwards:
 *   curl -u "user:app-pass" https://SITE/wp-json/sitegen/v1/export
 *
 * Also usable as a library (no file I/O):
 *   const { pushToSite } = require('./push');
 *   const result = await pushToSite({ payload, url, user, pass });
 */

const fs = require('fs');
const path = require('path');

function arg(name, fallback) {
  const a = process.argv.find((x) => x.startsWith(`--${name}=`));
  return a ? a.split('=').slice(1).join('=') : fallback;
}

// ---------------------------------------------------------------------------
// pushToSite() — pure network call, no file I/O. Throws on transport/HTTP
// failure; returns the parsed { success, updated, cleared, skipped, errors }
// response body on success.
// ---------------------------------------------------------------------------
async function pushToSite({ payload, url, user, pass }) {
  if (!url || !user || !pass) {
    throw new Error('pushToSite: missing url/user/pass');
  }

  const endpoint = url.replace(/\/$/, '') + '/wp-json/sitegen/v1/import';
  const auth = Buffer.from(`${user}:${pass}`).toString('base64');

  const res = await fetch(endpoint, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Basic ${auth}`,
    },
    body: JSON.stringify(payload),
  });

  const text = await res.text();
  let json;
  try { json = JSON.parse(text); } catch { json = null; }

  if (!res.ok) {
    const err = new Error(`push.js: HTTP ${res.status} from ${endpoint}`);
    err.status = res.status;
    err.body = text.slice(0, 1000);
    throw err;
  }

  return json;
}

// ---------------------------------------------------------------------------
// CLI entrypoint
// ---------------------------------------------------------------------------
async function main() {
  const inputPath = process.argv[2] || 'theme-fields.json';
  const url = arg('url', process.env.WP_URL);
  const user = arg('user', process.env.WP_USER);
  const pass = arg('pass', process.env.WP_APP_PASSWORD);

  if (!url || !user || !pass) {
    console.error('Missing --url / --user / --pass (or WP_URL / WP_USER / WP_APP_PASSWORD env vars)');
    process.exit(1);
  }

  // CLI-only — never reached when this module is required as a library
  // (see the require.main guard below); the ignore comment stops Next.js's
  // build-time tracer from over-conservatively bundling the whole repo.
  const payload = JSON.parse(fs.readFileSync(path.resolve(/* turbopackIgnore: true */ inputPath), 'utf8'));

  console.log(`→ POST ${url.replace(/\/$/, '')}/wp-json/sitegen/v1/import (${Object.keys(payload.fields || {}).length} fields)`);

  let json;
  try {
    json = await pushToSite({ payload, url, user, pass });
  } catch (e) {
    console.error(`✗ ${e.message}`);
    if (e.body) console.error(e.body);
    process.exit(1);
  }

  console.log(`✔ done`);
  console.log(`  updated (${json.updated.length}): ${json.updated.join(', ')}`);
  if (json.skipped?.length) console.log(`  ⚠ skipped (not registered on theme): ${json.skipped.join(', ')}`);
  if (json.errors?.length) console.log(`  ✗ errors:`, JSON.stringify(json.errors, null, 2));
}

if (require.main === module) {
  main().catch((e) => { console.error(e); process.exit(1); });
}

module.exports = { pushToSite };
