const path = require('path');

/**
 * This app lives in migration-tool/ but reuses the CommonJS pipeline
 * scripts (extract.js/map.js/transform.js/push.js) and the theme folder
 * (elektricien-amstelveen/) one level up, at the repo root. Two things are
 * needed so Vercel's build-time file tracer actually bundles those into the
 * serverless function output instead of silently leaving them behind:
 *
 *  1. outputFileTracingRoot — lets the tracer resolve requires that reach
 *     outside this app's own directory.
 *  2. outputFileTracingIncludes — the theme folder is read at RUNTIME via a
 *     recursive directory walk (lib/ssh-client.ts's uploadDirectory), not a
 *     static import, so the tracer can't auto-discover it and it must be
 *     declared explicitly.
 *
 * outputFileTracingIncludes/-Root are top-level NextConfig properties as of
 * Next.js 16.2.11 (confirmed against node_modules/next/dist/server/
 * config-shared.d.ts) — they lived under `experimental.*` in older Next 14
 * releases, so re-check this if the installed Next.js version ever changes.
 *
 * @type {import('next').NextConfig}
 */
const nextConfig = {
  outputFileTracingRoot: path.join(__dirname, '..'),
  outputFileTracingIncludes: {
    '/api/**/*': ['../elektricien-amstelveen/**/*'],
  },
};

module.exports = nextConfig;
