import { NextRequest } from 'next/server';
import { COOKIE_NAME, verifySession } from '../../../lib/auth';
import { getEnvValue, setEnvValue } from '../../../lib/env-file';

export const runtime = 'nodejs';

function refuseOnVercel() {
  if (process.env.VERCEL) {
    return Response.json(
      {
        error:
          'Settings editing only works for local development — it writes to .env.local, which does not exist on the deployed app. Set env vars directly in the Vercel dashboard for production.',
      },
      { status: 403 }
    );
  }
  return null;
}

export async function GET(req: NextRequest) {
  const blocked = refuseOnVercel();
  if (blocked) return blocked;

  const authed = await verifySession(req.cookies.get(COOKIE_NAME)?.value);
  if (!authed) return Response.json({ error: 'Not authenticated' }, { status: 401 });

  // The private key/passphrase are never sent back to the browser — only
  // whether one is currently set. SSH_ACCOUNTS_JSON isn't private key
  // material (just hostnames/usernames the operator already has locally),
  // and is what gets edited most often (adding a new domain), so it's
  // returned in full for editing in place.
  return Response.json({
    sshAccountsJson: getEnvValue('SSH_ACCOUNTS_JSON') || '',
    hasPrivateKey: !!(getEnvValue('SSH_PRIVATE_KEY') || '').trim(),
    hasPassphrase: !!(getEnvValue('SSH_PRIVATE_KEY_PASSPHRASE') || '').trim(),
  });
}

interface SettingsBody {
  sshAccountsJson?: string;
  sshPrivateKey?: string;
  sshPrivateKeyPassphrase?: string;
}

export async function POST(req: NextRequest) {
  const blocked = refuseOnVercel();
  if (blocked) return blocked;

  const authed = await verifySession(req.cookies.get(COOKIE_NAME)?.value);
  if (!authed) return Response.json({ error: 'Not authenticated' }, { status: 401 });

  let body: SettingsBody;
  try {
    body = await req.json();
  } catch {
    return Response.json({ error: 'Invalid request body' }, { status: 400 });
  }

  if (typeof body.sshAccountsJson === 'string' && body.sshAccountsJson.trim()) {
    try {
      const parsed = JSON.parse(body.sshAccountsJson);
      if (!Array.isArray(parsed)) throw new Error('not an array');
    } catch {
      return Response.json({ error: 'SSH accounts must be valid JSON (an array)' }, { status: 400 });
    }
  }

  // Blank fields mean "leave unchanged" — most saves are just adding a
  // domain to the accounts list, not re-pasting the private key.
  if (typeof body.sshAccountsJson === 'string') {
    setEnvValue('SSH_ACCOUNTS_JSON', body.sshAccountsJson.trim());
  }
  if (typeof body.sshPrivateKey === 'string' && body.sshPrivateKey.trim()) {
    setEnvValue('SSH_PRIVATE_KEY', body.sshPrivateKey.trim());
  }
  if (typeof body.sshPrivateKeyPassphrase === 'string' && body.sshPrivateKeyPassphrase) {
    setEnvValue('SSH_PRIVATE_KEY_PASSPHRASE', body.sshPrivateKeyPassphrase);
  }

  return Response.json({ success: true });
}
