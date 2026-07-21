/**
 * Reads/writes migration-tool/.env.local directly, so the Settings page can
 * update SSH credentials without hand-editing the file. Local-dev only —
 * Vercel's deployed filesystem is read-only and .env.local isn't even part
 * of the deployed bundle (it's gitignored), so callers must refuse to run
 * this when process.env.VERCEL is set (see app/api/settings/route.ts).
 *
 * Values are always written SINGLE-quoted. Confirmed against @next/env's
 * bundled dotenv parser (node_modules/@next/env/dist/index.js): a
 * double-quoted value only ever gets `\n`/`\r` unescaped back to real
 * characters — `\"` is never unescaped back to `"` — so double-quoting is
 * fundamentally incompatible with a value containing literal double quotes
 * (i.e. any JSON). Single-quoted values, by contrast, get NO escape
 * processing at all beyond stripping the outer quote pair, which correctly
 * round-trips both a PEM key's real newlines and a JSON string's literal
 * double quotes with zero escaping needed for either. The one thing still
 * needed is escaping a literal `$`, since Next's separate *expand* step
 * unescapes `\$` back to `$` regardless of quote style — left unescaped,
 * bare `$word` sequences get treated as variable references and silently
 * stripped. A literal single quote in the value has no correct
 * representation under single-quoting either, so setEnvValue() rejects it.
 */
import fs from 'node:fs';
import path from 'node:path';

const ENV_LOCAL_PATH = path.join(/* turbopackIgnore: true */ process.cwd(), '.env.local');

// Matches a single-quoted, double-quoted, or bare value. The character
// classes match across real newlines (JS regex negated classes aren't
// restricted by line boundaries), so this captures multi-line quoted
// values — e.g. a PEM key — in one shot.
const VALUE_PATTERN = `('[^']*'|"[^"]*"|[^\\r\\n]*)`;

function readEnvFile(): string {
  try {
    return fs.readFileSync(/* turbopackIgnore: true */ ENV_LOCAL_PATH, 'utf8');
  } catch {
    return '';
  }
}

function escapeEnvValue(value: string): string {
  return `'${value.replace(/\$/g, '\\$')}'`;
}

function unescapeEnvValue(rawValue: string): string {
  if (rawValue.startsWith("'") && rawValue.endsWith("'")) {
    return rawValue.slice(1, -1).replace(/\\\$/g, '$');
  }
  if (rawValue.startsWith('"') && rawValue.endsWith('"')) {
    // Legacy/manually-written double-quoted values — supported for
    // reading, though setEnvValue() never writes this form itself.
    return rawValue
      .slice(1, -1)
      .replace(/\\n/g, '\n')
      .replace(/\\r/g, '\r')
      .replace(/\\\$/g, '$');
  }
  return rawValue.replace(/\\\$/g, '$');
}

/** Returns the current value for `key` in .env.local, or null if not present. */
export function getEnvValue(key: string): string | null {
  const content = readEnvFile();
  const match = content.match(new RegExp(`^${key}=${VALUE_PATTERN}`, 'm'));
  return match ? unescapeEnvValue(match[1]) : null;
}

/** Sets (or adds) `key=value` in .env.local, preserving every other line. */
export function setEnvValue(key: string, rawValue: string): void {
  if (rawValue.includes("'")) {
    throw new Error(
      `Can't store a value containing a literal single-quote character for ${key} — edit .env.local directly for this one.`
    );
  }

  const content = readEnvFile();
  const line = `${key}=${escapeEnvValue(rawValue)}`;
  const re = new RegExp(`^${key}=${VALUE_PATTERN}`, 'm');

  const next = re.test(content)
    ? content.replace(re, line)
    : `${content}${content.endsWith('\n') || content === '' ? '' : '\n'}${line}\n`;

  fs.writeFileSync(/* turbopackIgnore: true */ ENV_LOCAL_PATH, next, 'utf8');
}
