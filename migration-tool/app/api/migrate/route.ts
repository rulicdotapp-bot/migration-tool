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

interface MigrateRequestBody {
  domain?: string;
  pageId?: string;
  dryRun?: boolean;
}

export async function POST(req: NextRequest) {
  // Defense in depth — middleware already gates this route, but the one
  // route that opens SSH connections and can mutate live client sites
  // checks the session again itself rather than trusting that alone.
  const authed = await verifySession(req.cookies.get(COOKIE_NAME)?.value);
  if (!authed) {
    return Response.json({ error: 'Not authenticated' }, { status: 401 });
  }

  let body: MigrateRequestBody;
  try {
    body = await req.json();
  } catch {
    return Response.json({ error: 'Invalid request body' }, { status: 400 });
  }

  const domain = (body.domain || '').trim();
  if (!domain) {
    return Response.json({ error: 'domain is required' }, { status: 400 });
  }

  const encoder = new TextEncoder();

  const stream = new ReadableStream({
    async start(controller) {
      const send = (event: Record<string, unknown>) => {
        controller.enqueue(encoder.encode(JSON.stringify(event) + '\n'));
      };
      const log = (message: string) => send({ type: 'log', message });

      try {
        await migrate({ domain, pageId: body.pageId, dryRun: !!body.dryRun }, log);
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
