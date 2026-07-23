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

  // Host/username/port aren't secret the way the key is — they're just
  // connection info you'd read off a hosting panel — so they're returned
  // in full for editing. The private key/passphrase are never sent back
  // to the browser, only whether one is currently set.
  return Response.json({
    sshHost: getEnvValue('SSH_HOST') || '',
    sshUsername: getEnvValue('SSH_USERNAME') || '',
    sshPort: getEnvValue('SSH_PORT') || '',
    hasPrivateKey: !!(getEnvValue('SSH_PRIVATE_KEY') || '').trim(),
    hasPassphrase: !!(getEnvValue('SSH_PRIVATE_KEY_PASSPHRASE') || '').trim(),
  });
}

interface SettingsBody {
  sshHost?: string;
  sshUsername?: string;
  sshPort?: string;
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

  if (typeof body.sshPort === 'string' && body.sshPort.trim()) {
    const port = Number(body.sshPort.trim());
    if (!Number.isInteger(port) || port <= 0) {
      return Response.json({ error: 'Port must be a positive integer' }, { status: 400 });
    }
  }

  // Blank fields mean "leave unchanged" — most saves are just fixing a
  // typo in the host, not re-pasting the private key every time.
  try {
    if (typeof body.sshHost === 'string') {
      setEnvValue('SSH_HOST', body.sshHost.trim());
    }
    if (typeof body.sshUsername === 'string') {
      setEnvValue('SSH_USERNAME', body.sshUsername.trim());
    }
    if (typeof body.sshPort === 'string') {
      setEnvValue('SSH_PORT', body.sshPort.trim());
    }
    if (typeof body.sshPrivateKey === 'string' && body.sshPrivateKey.trim()) {
      setEnvValue('SSH_PRIVATE_KEY', body.sshPrivateKey.trim());
    }
    if (typeof body.sshPrivateKeyPassphrase === 'string' && body.sshPrivateKeyPassphrase) {
      setEnvValue('SSH_PRIVATE_KEY_PASSPHRASE', body.sshPrivateKeyPassphrase);
    }
  } catch (err) {
    return Response.json({ error: err instanceof Error ? err.message : String(err) }, { status: 400 });
  }

  return Response.json({ success: true });
}
