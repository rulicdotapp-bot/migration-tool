/**
 * ssh2-based replacement for migrate.js's `ssh`/`scp` CLI shell-outs.
 * Vercel serverless functions don't have those binaries on PATH, so this
 * talks the SSH/SFTP protocol directly via the pure-JS `ssh2` package.
 */
import { Client, SFTPWrapper } from 'ssh2';
import fs from 'node:fs';
import path from 'node:path';

export interface SshConnectionInfo {
  host: string;
  port: number;
  username: string;
}

export async function connect(info: SshConnectionInfo): Promise<Client> {
  const privateKey = process.env.SSH_PRIVATE_KEY;
  if (!privateKey) throw new Error('SSH_PRIVATE_KEY env var is not set');
  const passphrase = process.env.SSH_PRIVATE_KEY_PASSPHRASE || undefined;

  const client = new Client();

  await new Promise<void>((resolve, reject) => {
    client
      .on('ready', () => resolve())
      .on('error', (err) => reject(err))
      .connect({
        host: info.host,
        port: info.port,
        username: info.username,
        privateKey: privateKey.trim(),
        passphrase,
        readyTimeout: 20000,
        keepaliveInterval: 10000,
        // Many of these hosting accounts share IPs across many domains, so a
        // new domain hitting an already-trusted host key is expected and
        // normal — this mirrors the local CLI's deliberate
        // StrictHostKeyChecking=accept-new, not a new gap.
        hostVerifier: () => true,
      });
  });

  return client;
}

export function disconnect(client: Client): void {
  client.end();
}

interface ExecResult {
  stdout: string;
  stderr: string;
  code: number | null;
}

function execRaw(client: Client, cmd: string): Promise<ExecResult> {
  return new Promise((resolve, reject) => {
    client.exec(cmd, (err, stream) => {
      if (err) return reject(err);
      let stdout = '';
      let stderr = '';
      stream
        .on('data', (data: Buffer) => {
          stdout += data.toString('utf8');
        })
        .on('close', (code: number | null) => {
          resolve({ stdout: stdout.trim(), stderr: stderr.trim(), code });
        })
        .stderr.on('data', (data: Buffer) => {
          stderr += data.toString('utf8');
        });
    });
  });
}

/** Runs a remote command, throws (with stderr) on non-zero exit. */
export async function exec(client: Client, cmd: string): Promise<string> {
  const { stdout, stderr, code } = await execRaw(client, cmd);
  if (code !== 0) {
    throw new Error(`Remote command failed (exit ${code}): ${cmd}\n${stderr || stdout}`);
  }
  return stdout;
}

/** Same as exec(), but returns null instead of throwing — for "try candidate path" style probing. */
export async function execQuiet(client: Client, cmd: string): Promise<string | null> {
  try {
    return await exec(client, cmd);
  } catch {
    return null;
  }
}

export function wp(client: Client, wpPath: string, cliArgs: string): Promise<string> {
  return exec(client, `wp --path='${wpPath}' ${cliArgs}`);
}

export function wpQuiet(client: Client, wpPath: string, cliArgs: string): Promise<string | null> {
  return execQuiet(client, `wp --path='${wpPath}' ${cliArgs}`);
}

function getSftp(client: Client): Promise<SFTPWrapper> {
  return new Promise((resolve, reject) => {
    client.sftp((err, sftp) => {
      if (err) return reject(err);
      resolve(sftp);
    });
  });
}

function sftpMkdir(sftp: SFTPWrapper, remotePath: string): Promise<void> {
  return new Promise((resolve) => {
    // Tolerate "already exists" (and any other mkdir error) — the caller
    // rm -rf's the remote theme dir first, but this keeps the walk robust
    // either way rather than failing an entire upload over a mkdir race.
    sftp.mkdir(remotePath, () => resolve());
  });
}

function sftpFastPut(sftp: SFTPWrapper, localPath: string, remotePath: string): Promise<void> {
  return new Promise((resolve, reject) => {
    sftp.fastPut(localPath, remotePath, (err) => {
      if (err) reject(err);
      else resolve();
    });
  });
}

/**
 * Recursively uploads localDir's contents into remoteDir (POSIX path,
 * assumed to already exist on the remote host). Replaces `scp -r` — ssh2
 * has no built-in recursive copy, so this walks the local directory itself.
 * Returns the number of files uploaded.
 */
export async function uploadDirectory(client: Client, localDir: string, remoteDir: string): Promise<number> {
  const sftp = await getSftp(client);
  let fileCount = 0;

  async function walk(local: string, remote: string) {
    await sftpMkdir(sftp, remote);
    const entries = fs.readdirSync(local, { withFileTypes: true });
    for (const entry of entries) {
      const localPath = path.join(local, entry.name);
      const remotePath = `${remote}/${entry.name}`;
      if (entry.isDirectory()) {
        await walk(localPath, remotePath);
      } else if (entry.isFile()) {
        await sftpFastPut(sftp, localPath, remotePath);
        fileCount += 1;
      }
    }
  }

  await walk(localDir, remoteDir);
  return fileCount;
}

export type { Client };
