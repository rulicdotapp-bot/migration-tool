import { NextRequest, NextResponse } from 'next/server';
import { COOKIE_NAME, verifySession } from './lib/auth';

// Next.js 16 renamed the "middleware" file convention to "proxy" (same
// mechanism, runs on every matched request before the route handler).
export default async function proxy(req: NextRequest) {
  const token = req.cookies.get(COOKIE_NAME)?.value;
  const authed = await verifySession(token);

  if (authed) {
    return NextResponse.next();
  }

  if (req.nextUrl.pathname.startsWith('/api/')) {
    return NextResponse.json({ error: 'Not authenticated' }, { status: 401 });
  }

  const loginUrl = new URL('/login', req.url);
  return NextResponse.redirect(loginUrl);
}

export const config = {
  // Everything except the login page, its API, and Next's own static assets.
  matcher: ['/((?!login|api/login|_next/static|_next/image|favicon.ico).*)'],
};
