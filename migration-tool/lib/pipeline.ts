/**
 * Thin bridge to the CommonJS pipeline scripts in migration-tool/pipeline/
 * — local copies of the repo-root extract.js/map.js/transform.js/push.js,
 * kept in sync by hand (the root copies stay in place for standalone CLI
 * use). Copied in rather than required across the repo-root boundary
 * because `__dirname`/relative-path resolution for files outside the
 * project directory isn't reliable once Next.js bundles this code (it
 * broke the theme-folder upload with a "no such file or directory" against
 * a bundler-rewritten path) — keeping everything the app needs inside
 * migration-tool/ sidesteps that entirely and needs no extra tracing
 * config. Loaded via `require()` (not `import`) since they're untyped
 * CommonJS.
 */
/* eslint-disable @typescript-eslint/no-var-requires */

const extractMod = require('../pipeline/extract.js');
const mapMod = require('../pipeline/map.js');
const transformMod = require('../pipeline/transform.js');
const pushMod = require('../pipeline/push.js');

export const extract: (args: {
  pageData: unknown;
  header?: unknown;
  footer?: unknown;
  templates?: Record<string, unknown> | null;
}) => { widgets: unknown[]; [key: string]: unknown } = extractMod.extract;

export const mapContent: (args: {
  widgets: unknown[];
  site?: string | null;
}) => Record<string, unknown> = mapMod.mapContent;

export const transform: (args: {
  siteContent: Record<string, unknown>;
  city?: string;
}) => {
  site: string | null;
  city: string;
  generatedAt: string;
  sideload_images: boolean;
  clear: string[];
  fields: Record<string, unknown>;
} = transformMod.transform;

export const pushToSite: (args: {
  payload: unknown;
  url: string;
  user: string;
  pass: string;
}) => Promise<{
  success: boolean;
  updated: string[];
  cleared: string[];
  skipped: string[];
  errors: unknown[];
}> = pushMod.pushToSite;
