/**
 * Thin bridge to the repo-root CommonJS pipeline scripts. They're reused
 * as-is (see ../../extract.js, ../../map.js, ../../transform.js,
 * ../../push.js) — only their `main()` CLI wrappers were changed to also
 * export a plain in-memory function. Loaded via `require()` (not `import`)
 * since they're untyped CommonJS; static relative paths so Next's build-time
 * file tracer can follow them into the deployed function bundle (see
 * next.config.js's outputFileTracingRoot).
 */
/* eslint-disable @typescript-eslint/no-var-requires */

const extractMod = require('../../extract.js');
const mapMod = require('../../map.js');
const transformMod = require('../../transform.js');
const pushMod = require('../../push.js');

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
