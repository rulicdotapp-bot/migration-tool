/**
 * Single shared operator/team password, signed session cookie. There is no
 * user table or signup flow — this tool is for the operator/team only, and
 * a shared credential is sufficient (see the migration-tool build plan).
 *
 * jose is used instead of jsonwebtoken because it's Web-Crypto-based and
 * runs anywhere Next.js executes this code (API routes, proxy.ts) without
 * needing Node's `crypto` module — one less runtime assumption to track as
 * Next.js's execution model evolves.
 */
import { SignJWT, jwtVerify } from 'jose';

export const COOKIE_NAME = 'session';
export const SESSION_MAX_AGE_SECONDS = 60 * 60 * 24 * 7; // 7 days

function getSecretKey(): Uint8Array {
  const secret = process.env.SESSION_SECRET;
  if (!secret) {
    throw new Error('SESSION_SECRET env var is not set');
  }
  return new TextEncoder().encode(secret);
}

export async function signSession(): Promise<string> {
  return new SignJWT({ sub: 'operator' })
    .setProtectedHeader({ alg: 'HS256' })
    .setIssuedAt()
    .setExpirationTime('7d')
    .sign(getSecretKey());
}

export async function verifySession(token: string | undefined | null): Promise<boolean> {
  if (!token) return false;
  try {
    await jwtVerify(token, getSecretKey());
    return true;
  } catch {
    return false;
  }
}
