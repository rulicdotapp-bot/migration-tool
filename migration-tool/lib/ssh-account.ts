/**
 * The single hosting-account SSH connection info, sourced from
 * SSH_HOST / SSH_USERNAME / SSH_PORT. Used for every migration regardless
 * of which domain is being migrated — the domain typed into the dashboard
 * only selects the addon-domain folder on this one account (matching how
 * SiteGround's addon-domain hosting actually works: one account, many
 * domains), it doesn't select between multiple accounts. This tool only
 * supports one hosting account at a time; if that ever changes, this is
 * the file to revisit.
 */

export interface SshAccount {
  host: string;
  port: number;
  username: string;
}

export function getAccount(): SshAccount {
  const host = process.env.SSH_HOST;
  const username = process.env.SSH_USERNAME;
  const portRaw = process.env.SSH_PORT;

  const missing = [
    !host && 'SSH_HOST',
    !username && 'SSH_USERNAME',
    !portRaw && 'SSH_PORT',
  ].filter(Boolean);
  if (missing.length) {
    throw new Error(`Missing SSH connection settings: ${missing.join(', ')}. Set them in Settings.`);
  }

  const port = Number(portRaw);
  if (!Number.isInteger(port) || port <= 0) {
    throw new Error(`SSH_PORT must be a positive integer, got "${portRaw}"`);
  }

  return { host: host as string, port, username: username as string };
}
