#!/usr/bin/env node
/**
 * Copies the repo-root theme folder and pipeline scripts into
 * migration-tool/, so the web app (which needs its own self-contained
 * copies — see lib/migrate.ts and lib/pipeline.ts for why) doesn't quietly
 * drift out of sync with edits made at the repo root.
 *
 * Run this after changing elektricien/ or
 * extract.js/map.js/transform.js/push.js at the repo root, and before
 * testing/deploying the web app.
 *
 * Usage: npm run sync-theme
 */
const fs = require('fs');
const path = require('path');

const repoRoot = path.join(__dirname, '..', '..');
const appRoot = path.join(__dirname, '..');

const THEME_SLUG = 'elektricien';
const PIPELINE_FILES = ['extract.js', 'map.js', 'transform.js', 'push.js'];

fs.cpSync(path.join(repoRoot, THEME_SLUG), path.join(appRoot, THEME_SLUG), { recursive: true });
console.log(`✔ Synced ${THEME_SLUG}/`);

for (const file of PIPELINE_FILES) {
  fs.copyFileSync(path.join(repoRoot, file), path.join(appRoot, 'pipeline', file));
  console.log(`✔ Synced pipeline/${file}`);
}
