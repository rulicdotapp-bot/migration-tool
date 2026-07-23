import { NextRequest } from 'next/server';
import { COOKIE_NAME, verifySession } from '../../../lib/auth';
import { migrate } from '../../../lib/migrate';

// ssh2 needs Node's `net`/`crypto` — not available on the Edge runtime.
export const runtime = 'nodejs';
// Hobby's ceiling is 60s — this is deliberately left at that value until
// the project is on Pro (+ Fluid Compute), at which point raise it to
// whatever Vercel's current max is so a real migration has enough headroom
// to finish (theme upload + plugin installs + WP-CLI calls can take
// 1-3+ minutes). See migration-tool build plan for details.
export const maxDuration = 60;

export async function POST(req: NextRequest) {
  // Defense in depth — middleware already gates this route, but the one
  // route that opens SSH connections and can mutate live client sites
  // checks the session again itself rather than trusting that alone.
  const authed = await verifySession(req.cookies.get(COOKIE_NAME)?.value);
  if (!authed) {
    return Response.json({ error: 'Not authenticated' }, { status: 401 });
  }

  // multipart/form-data, not JSON — the optional logo file needs a real
  // file upload, which fetch's FormData handles natively.
  let form: FormData;
  try {
    form = await req.formData();
  } catch {
    return Response.json({ error: 'Invalid form data' }, { status: 400 });
  }

  // Every site has its own hosting account — these come from the form
  // fresh on every request and are never written to disk or env vars.
  const sshHost = String(form.get('sshHost') || '').trim();
  const sshUsername = String(form.get('sshUsername') || '').trim();
  const sshPortRaw = String(form.get('sshPort') || '').trim();
  const sshPrivateKey = String(form.get('sshPrivateKey') || '').trim();
  const sshPrivateKeyPassphrase = String(form.get('sshPrivateKeyPassphrase') || '').trim();
  const sshPassword = String(form.get('sshPassword') || '').trim();

  const missing = [
    !sshHost && 'SSH host',
    !sshUsername && 'SSH username',
    !sshPortRaw && 'SSH port',
    !sshPrivateKey && !sshPassword && 'SSH private key or password',
  ].filter(Boolean);
  if (missing.length) {
    return Response.json({ error: `Missing: ${missing.join(', ')}` }, { status: 400 });
  }
  const sshPort = Number(sshPortRaw);
  if (!Number.isInteger(sshPort) || sshPort <= 0) {
    return Response.json({ error: `SSH port must be a positive integer, got "${sshPortRaw}"` }, { status: 400 });
  }

  const pageIdRaw = form.get('pageId');
  const pageId = pageIdRaw ? String(pageIdRaw) : undefined;
  const dryRun = form.get('dryRun') === 'true';

  let logo: { buffer: Buffer; ext: string } | undefined;
  const logoEntry = form.get('logo');
  if (logoEntry instanceof File && logoEntry.size > 0) {
    const buffer = Buffer.from(await logoEntry.arrayBuffer());
    const ext = (logoEntry.name.split('.').pop() || 'webp').toLowerCase();
    logo = { buffer, ext };
  }

  const encoder = new TextEncoder();

  const stream = new ReadableStream({
    async start(controller) {
      const send = (event: Record<string, unknown>) => {
        controller.enqueue(encoder.encode(JSON.stringify(event) + '\n'));
      };
      const log = (message: string) => send({ type: 'log', message });

      try {
        const ssh = {
          host: sshHost,
          username: sshUsername,
          port: sshPort,
          privateKey: sshPrivateKey || undefined,
          privateKeyPassphrase: sshPrivateKeyPassphrase || undefined,
          password: sshPassword || undefined,
        };
        await migrate({ ssh, pageId, dryRun, logo }, log);
        send({ type: 'done' });
      } catch (err) {
        send({ type: 'error', message: err instanceof Error ? err.message : String(err) });
      } finally {
        controller.close();
      }
    },
  });

  return new Response(stream, {
    headers: {
      'Content-Type': 'application/x-ndjson; charset=utf-8',
      'Cache-Control': 'no-cache, no-transform',
      'X-Content-Type-Options': 'nosniff',
    },
  });
}
