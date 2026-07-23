# Migration tool (web UI)

Login-gated web interface for the `extract → map → transform → push` site
migration pipeline. This app is self-contained — `pipeline/` and
`elektricien/` here are copies of the repo-root
`extract.js`/`map.js`/`transform.js`/`push.js` and theme folder, not
references to them (reaching outside the project broke once Next.js
bundled the code — `__dirname`/relative-path resolution to files outside
`migration-tool/` isn't reliable in the bundled/deployed output). See
`lib/pipeline.ts` and `lib/migrate.ts` for how they're used.

**If you edit the theme or pipeline scripts at the repo root, run
`npm run sync-theme` before testing/deploying this app** — otherwise it
silently keeps using the old copies (this has already happened twice).

This is an **internal tool, not a public one** — it holds SSH credentials
capable of mutating live client WordPress sites. Everything behind the
login gate should stay that way.

## Local setup

```bash
cd migration-tool
npm install
cp .env.local.example .env.local
```

Fill in `.env.local`:

1. **`AUTH_PASSWORD_HASH`** — pick a password, then:
   ```bash
   npm run hash-password -- "your-password-here"
   ```
   paste the output (not the plaintext password) into `.env.local`.
2. **`SESSION_SECRET`** — `node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"`.
3. **`SSH_HOST`**, **`SSH_USERNAME`**, **`SSH_PORT`**, **`SSH_PRIVATE_KEY`**,
   **`SSH_PRIVATE_KEY_PASSPHRASE`** — leave these blank in the file. Fill
   them in from the app instead (see below) once you're logged in.

```bash
npm run dev
```

Visit `http://localhost:3000`, sign in, then go to **Settings** and fill in
your hosting account's SSH details — hostname, username, port, read
straight off your hosting panel's SSH access page — plus the private key
that already works with `ssh <that-host>` locally (e.g. `~/.ssh/id_ed25519`;
get its content with `cat ~/.ssh/id_ed25519` and paste the whole thing,
BEGIN/END lines included). This tool supports **one hosting account**,
used for every migration regardless of which domain you type on the
dashboard — the domain only selects the addon-domain folder on that one
account (how SiteGround/Hostinger addon-domain hosting actually works), it
doesn't select between multiple accounts. Settings writes straight to
`.env.local` and only works for local dev — the deployed Vercel app
refuses these requests (its filesystem is read-only and `.env.local` isn't
even part of the deployed bundle), so production secrets still go through
the Vercel dashboard as described below.

(`SSH_PASSWORD` also works instead of a key, if this host allows SSH
password login — most managed hosts, SiteGround included, don't. The key
takes priority automatically if both are set.)

Back on the dashboard, try a **dry run** first — it fetches and previews
without touching the live site, and finishes in seconds regardless of
Vercel plan tier.

## Deploying

You (not this assistant) create the Vercel project and enter every secret
above directly in its dashboard — see the repo's plan file for the full
reasoning. A few things to double check there:

- **Root Directory**: `migration-tool`
- **`app/api/migrate/route.ts`**: `export const maxDuration` is set to `60`
  (Hobby's ceiling) to start. Everything except a real end-to-end migration
  (login, the UI, dry runs) works fine on Hobby. Once you're on **Pro with
  Fluid Compute**, raise `maxDuration` to whatever Vercel's current max is
  for that tier — a full migration (theme upload + plugin installs +
  WP-CLI calls) can take 1–3+ minutes on a slow host and will otherwise
  get cut off mid-run.
- Env vars: scope `SSH_HOST`, `SSH_USERNAME`, `SSH_PORT`, `SSH_PASSWORD`
  (or `SSH_PRIVATE_KEY`), etc. to **Production only** unless you also want
  preview deployments to be able to touch live sites.

## What a timeout looks like

If a run gets cut off mid-flight (most likely on Hobby, or a very slow
host), the browser's log console just stops — no final success/error
banner. `lib/migrate.ts` self-heals for this: it deletes any leftover
`migration-*`-named application passwords before minting a new one each
run, and the theme upload/plugin install steps are already idempotent
(`rm -rf` first, `--force` installs), so simply re-running is safe.
