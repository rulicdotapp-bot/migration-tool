/**
 * The theme folder (elektricien/, copied in from the repo root —
 * see lib/migrate.ts and lib/pipeline.ts for why) is read at RUNTIME via a
 * recursive directory walk (lib/ssh-client.ts's uploadDirectory), not a
 * static import, so Next's build-time file tracer can't auto-discover it
 * even though it's inside the project now — it must be declared explicitly
 * or the deployed function silently ships without it.
 *
 * outputFileTracingIncludes is a top-level NextConfig property as of
 * Next.js 16.2.11 (confirmed against node_modules/next/dist/server/
 * config-shared.d.ts) — it lived under `experimental.*` in older Next 14
 * releases, so re-check this if the installed Next.js version ever changes.
 *
 * @type {import('next').NextConfig}
 */
const nextConfig = {
  outputFileTracingIncludes: {
    '/api/**/*': ['./elektricien/**/*'],
  },
};

module.exports = nextConfig;
