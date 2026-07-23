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

This is an **internal tool, not a public one** — anyone who logs in can
SSH into whatever hosting account they paste in and mutate that site.
Everything behind the login gate should stay that way.

## SSH credentials — pasted per run, never stored

Every site being migrated has its own separate hosting account with its
own SSH credentials. There's no saved "account" anywhere — no Settings
page, no env vars, no database. The dashboard's migration form has fields
for SSH host/username/port/private key (or password); whoever's running a
migration pastes that specific site's credentials in each time, straight
off its hosting panel's SSH access page. Nothing is written to disk or
persisted between requests — `lib/ssh-client.ts` takes the connection info
as a plain argument and that's it.

There's also no domain field: the app finds the WordPress install itself
by searching for `wp-config.php` under the account's home directory (see
`lib/migrate.ts` step 0), then reads the site's real URL straight from
WordPress (`wp option get home`) rather than trusting a typed string. This
also means it doesn't care whether the host uses an addon-domain layout or
a dedicated-account layout — it just finds WordPress wherever it is.

## Local setup

```bash
cd migration-tool
npm install
cp .env.local.example .env.local
```

Fill in `.env.local` — only two values are needed, both for the login gate
itself (not for any site being migrated):

1. **`AUTH_PASSWORD_HASH`** — pick a shared team password, then:
   ```bash
   npm run hash-password -- "your-password-here"
   ```
   paste the output (not the plaintext password) into `.env.local`.
2. **`SESSION_SECRET`** — `node -e "console.log(require('crypto').randomBytes(32).toString('hex'))"`.

```bash
npm run dev
```

Visit `http://localhost:3000`, sign in, then paste a site's SSH details
directly into the migration form. Try a **dry run** first — it fetches and
previews without touching the live site, and finishes in seconds
regardless of Vercel plan tier.

## Deploying

You (not this assistant) create the Vercel project and enter
`AUTH_PASSWORD_HASH` and `SESSION_SECRET` directly in its dashboard — see
the repo's plan file for the full reasoning. Nothing else needs to go in
the Vercel dashboard; SSH credentials never touch it, since they're pasted
into the app itself per migration. A few things to double check:

- **Root Directory**: `migration-tool`
- **Framework Preset**: `Next.js` (auto-detects once Root Directory is set
  correctly — if it's stuck on "Other" with a manual Output Directory
  override, that's a leftover from before Root Directory was fixed; clear
  the override)
- **`app/api/migrate/route.ts`**: `export const maxDuration` is set to `60`
  (Hobby's ceiling) to start. Everything except a real end-to-end migration
  (login, the UI, dry runs) works fine on Hobby. Once you're on **Pro with
  Fluid Compute**, raise `maxDuration` to whatever Vercel's current max is
  for that tier — a full migration (theme upload + plugin installs +
  WP-CLI calls) can take 1–3+ minutes on a slow host and will otherwise
  get cut off mid-run.

## What a timeout looks like

If a run gets cut off mid-flight (most likely on Hobby, or a very slow
host), the browser's log console just stops — no final success/error
banner. `lib/migrate.ts` self-heals for this: it deletes any leftover
`migration-*`-named application passwords before minting a new one each
run, and the theme upload/plugin install steps are already idempotent
(`rm -rf` first, `--force` installs), so simply re-running (with the same
SSH details pasted back in) is safe.
