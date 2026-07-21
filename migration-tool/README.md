# Migration tool (web UI)

Login-gated web interface for the `extract → map → transform → push` site
migration pipeline that lives at the repo root. Reuses that pipeline logic
and the `elektricien-amstelveen/` theme folder directly; see
`../extract.js`, `../map.js`, `../transform.js`, `../push.js` for the
underlying logic and `lib/migrate.ts` for the orchestrator.

This is an **internal tool, not a public one** — it holds an SSH private key
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
3. **`SSH_PRIVATE_KEY`**, **`SSH_PRIVATE_KEY_PASSPHRASE`**, **`SSH_ACCOUNTS_JSON`**
   — leave these blank in the file. Fill them in from the app instead (see
   below) once you're logged in — easier than round-tripping through an
   editor every time you add a domain.

```bash
npm run dev
```

Visit `http://localhost:3000`, sign in, then go to **Settings** and paste
in your SSH private key (the one that already works with `ssh <domain>`
locally, e.g. `~/.ssh/id_ed25519`) and your accounts list (same array shape
as the repo-root `ssh-accounts.json`: `[{"host","port","user","domains"}]`
— this list **is** the domain allow-list; a domain not present there is
rejected before any SSH connection is attempted). Settings writes straight
to `.env.local` and only works for local dev — the deployed Vercel app
refuses these requests (its filesystem is read-only and `.env.local` isn't
even part of the deployed bundle), so production secrets still go through
the Vercel dashboard as described below.

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
  (login, the UI, domain allow-list rejection, dry runs) works fine on
  Hobby. Once you're on **Pro with Fluid Compute**, raise `maxDuration` to
  whatever Vercel's current max is for that tier — a full migration
  (theme upload + plugin installs + WP-CLI calls) can take 1–3+ minutes on
  a slow host and will otherwise get cut off mid-run.
- Env vars: scope `SSH_PRIVATE_KEY`, `SSH_ACCOUNTS_JSON`, etc. to
  **Production only** unless you also want preview deployments to be able
  to touch live sites.

## What a timeout looks like

If a run gets cut off mid-flight (most likely on Hobby, or a very slow
host), the browser's log console just stops — no final success/error
banner. `lib/migrate.ts` self-heals for this: it deletes any leftover
`migration-*`-named application passwords before minting a new one each
run, and the theme upload/plugin install steps are already idempotent
(`rm -rf` first, `--force` installs), so simply re-running is safe.
