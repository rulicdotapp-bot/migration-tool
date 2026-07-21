/**
 * Domain → hosting-account lookup. Replaces the local ssh-accounts.json +
 * ~/.ssh/config workflow (gen-ssh-config.js) — same shape, sourced from the
 * SSH_ACCOUNTS_JSON env var instead of a file, since there's no persistent
 * filesystem/SSH config on Vercel.
 *
 * This lookup doubles as the domain allow-list: a migration can only ever
 * target a domain that's explicitly enumerated here. Anything else is
 * rejected before any SSH connection is attempted.
 */

export interface SshAccount {
  host: string;
  port: number;
  user: string;
  domains: string[];
}

export interface ResolvedAccount {
  host: string;
  port: number;
  username: string;
}

// Deliberately not cached: read fresh from process.env on every call. This
// is a handful of small JSON.parse calls, not worth optimizing, and caching
// it previously meant a settings-page edit to SSH_ACCOUNTS_JSON wouldn't
// take effect without restarting the dev server.
function loadAccounts(): SshAccount[] {
  const raw = process.env.SSH_ACCOUNTS_JSON;
  if (!raw) {
    throw new Error('SSH_ACCOUNTS_JSON env var is not set');
  }

  let parsed: unknown;
  try {
    parsed = JSON.parse(raw);
  } catch {
    throw new Error('SSH_ACCOUNTS_JSON is not valid JSON');
  }

  if (!Array.isArray(parsed)) {
    throw new Error('SSH_ACCOUNTS_JSON must be a JSON array');
  }

  return parsed as SshAccount[];
}

/** Returns the {host, port, user} for a domain, or null if it isn't allow-listed. */
export function findAccountForDomain(domain: string): ResolvedAccount | null {
  const accounts = loadAccounts();
  const normalized = domain.trim().toLowerCase();

  for (const acct of accounts) {
    if (!acct.host || !acct.port || !acct.user || !Array.isArray(acct.domains)) continue;
    if (acct.domains.some((d) => d.toLowerCase() === normalized)) {
      return { host: acct.host, port: acct.port, username: acct.user };
    }
  }

  return null;
}
