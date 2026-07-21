import { NextRequest, NextResponse } from 'next/server';
import bcrypt from 'bcryptjs';
import { COOKIE_NAME, SESSION_MAX_AGE_SECONDS, signSession } from '../../../lib/auth';

export const runtime = 'nodejs';

export async function POST(req: NextRequest) {
  const hash = process.env.AUTH_PASSWORD_HASH;
  if (!hash) {
    return NextResponse.json({ error: 'Server is not configured (AUTH_PASSWORD_HASH missing)' }, { status: 500 });
  }

  let password: unknown;
  try {
    const body = await req.json();
    password = body?.password;
  } catch {
    return NextResponse.json({ error: 'Invalid request' }, { status: 400 });
  }

  if (typeof password !== 'string' || !password) {
    return NextResponse.json({ error: 'Invalid credentials' }, { status: 401 });
  }

  const ok = await bcrypt.compare(password, hash);
  if (!ok) {
    // Generic message — never confirm/deny which part was wrong.
    return NextResponse.json({ error: 'Invalid credentials' }, { status: 401 });
  }

  const token = await signSession();
  const res = NextResponse.json({ success: true });
  res.cookies.set(COOKIE_NAME, token, {
    httpOnly: true,
    secure: true,
    sameSite: 'lax',
    path: '/',
    maxAge: SESSION_MAX_AGE_SECONDS,
  });
  return res;
}
