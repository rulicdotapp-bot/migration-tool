'use client';

import { useState, FormEvent, useRef } from 'react';
import LogConsole from './LogConsole';

type Status = 'idle' | 'running' | 'success' | 'error' | 'ended-unexpectedly';

export default function MigrateForm() {
  const [sshHost, setSshHost] = useState('');
  const [sshUsername, setSshUsername] = useState('');
  const [sshPort, setSshPort] = useState('22');
  const [sshPrivateKey, setSshPrivateKey] = useState('');
  const [sshPrivateKeyPassphrase, setSshPrivateKeyPassphrase] = useState('');
  const [sshPassword, setSshPassword] = useState('');
  const [pageId, setPageId] = useState('');
  const [dryRun, setDryRun] = useState(true);
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [lines, setLines] = useState<string[]>([]);
  const [status, setStatus] = useState<Status>('idle');
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const runningRef = useRef(false);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    if (runningRef.current) return;
    runningRef.current = true;
    setStatus('running');
    setErrorMessage(null);
    setLines([]);

    try {
      const formData = new FormData();
      formData.append('sshHost', sshHost);
      formData.append('sshUsername', sshUsername);
      formData.append('sshPort', sshPort);
      if (sshPrivateKey) formData.append('sshPrivateKey', sshPrivateKey);
      if (sshPrivateKeyPassphrase) formData.append('sshPrivateKeyPassphrase', sshPrivateKeyPassphrase);
      if (sshPassword) formData.append('sshPassword', sshPassword);
      if (pageId) formData.append('pageId', pageId);
      formData.append('dryRun', String(dryRun));
      if (logoFile) formData.append('logo', logoFile);

      // No Content-Type header — the browser sets the multipart boundary
      // itself, which a hardcoded 'application/json' would break.
      const res = await fetch('/api/migrate', {
        method: 'POST',
        body: formData,
      });

      if (!res.ok || !res.body) {
        const errBody = await res.json().catch(() => ({}));
        setErrorMessage(errBody.error || `Request failed (${res.status})`);
        setStatus('error');
        return;
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      let sawTerminal = false;

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;
        buffer += decoder.decode(value, { stream: true });

        let newlineIndex = buffer.indexOf('\n');
        while (newlineIndex !== -1) {
          const rawLine = buffer.slice(0, newlineIndex);
          buffer = buffer.slice(newlineIndex + 1);
          newlineIndex = buffer.indexOf('\n');
          if (!rawLine.trim()) continue;

          let event: { type: string; message?: string };
          try {
            event = JSON.parse(rawLine);
          } catch {
            continue;
          }

          if (event.type === 'log') {
            setLines((prev) => [...prev, event.message || '']);
          } else if (event.type === 'done') {
            sawTerminal = true;
            setStatus('success');
          } else if (event.type === 'error') {
            sawTerminal = true;
            setErrorMessage(event.message || 'Migration failed');
            setStatus('error');
          }
        }
      }

      if (!sawTerminal) {
        // Stream ended with no done/error event — most likely a function
        // timeout mid-run. Don't claim success; the console above shows
        // exactly which step it reached, and re-running is safe.
        setStatus('ended-unexpectedly');
      }
    } catch (err) {
      setErrorMessage(err instanceof Error ? err.message : String(err));
      setStatus('error');
    } finally {
      runningRef.current = false;
    }
  }

  const disabled = status === 'running';

  return (
    <>
      <form onSubmit={handleSubmit}>
        <p style={{ color: '#9aa1ac', fontSize: 13, marginTop: 0, marginBottom: 16 }}>
          Paste this site&apos;s own SSH connection details — read them off its hosting panel&apos;s SSH access
          page. Nothing here is saved; you paste it fresh for every site you migrate.
        </p>
        <div className="field">
          <label htmlFor="sshHost">SSH Hostname</label>
          <input
            id="sshHost"
            type="text"
            placeholder="ssh.example.com"
            value={sshHost}
            onChange={(e) => setSshHost(e.target.value)}
            disabled={disabled}
            required
          />
        </div>
        <div className="field">
          <label htmlFor="sshUsername">SSH Username</label>
          <input
            id="sshUsername"
            type="text"
            placeholder="u123abc"
            value={sshUsername}
            onChange={(e) => setSshUsername(e.target.value)}
            disabled={disabled}
            required
          />
        </div>
        <div className="field">
          <label htmlFor="sshPort">SSH Port</label>
          <input
            id="sshPort"
            type="number"
            placeholder="22"
            value={sshPort}
            onChange={(e) => setSshPort(e.target.value)}
            disabled={disabled}
            required
          />
        </div>
        <div className="field">
          <label htmlFor="sshPrivateKey">SSH private key</label>
          <textarea
            id="sshPrivateKey"
            rows={6}
            placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----"
            value={sshPrivateKey}
            onChange={(e) => setSshPrivateKey(e.target.value)}
            disabled={disabled}
          />
        </div>
        <div className="field">
          <label htmlFor="sshPrivateKeyPassphrase">Private key passphrase (if any)</label>
          <input
            id="sshPrivateKeyPassphrase"
            type="password"
            value={sshPrivateKeyPassphrase}
            onChange={(e) => setSshPrivateKeyPassphrase(e.target.value)}
            disabled={disabled}
          />
        </div>
        <div className="field">
          <label htmlFor="sshPassword">
            SSH password (only if this host doesn&apos;t support key login — leave blank if you filled in a key above)
          </label>
          <input
            id="sshPassword"
            type="password"
            value={sshPassword}
            onChange={(e) => setSshPassword(e.target.value)}
            disabled={disabled}
          />
        </div>
        <div className="field">
          <label htmlFor="pageId">Page ID (optional)</label>
          <input
            id="pageId"
            type="number"
            placeholder="Leave blank to use the site's front-page setting"
            value={pageId}
            onChange={(e) => setPageId(e.target.value)}
            disabled={disabled}
          />
        </div>
        <div className="field">
          <label htmlFor="logo">Logo (optional — used in the header &amp; footer)</label>
          <input
            id="logo"
            type="file"
            accept="image/*"
            onChange={(e) => setLogoFile(e.target.files?.[0] || null)}
            disabled={disabled}
          />
        </div>
        <div className="checkbox-field">
          <input
            id="dryRun"
            type="checkbox"
            checked={dryRun}
            onChange={(e) => setDryRun(e.target.checked)}
            disabled={disabled}
          />
          <label htmlFor="dryRun">Dry run (fetch + preview only — no changes to the live site)</label>
        </div>
        <button
          type="submit"
          disabled={disabled || !sshHost || !sshUsername || !sshPort || (!sshPrivateKey && !sshPassword)}
        >
          {disabled ? 'Running…' : dryRun ? 'Run dry-run' : 'Run migration'}
        </button>
      </form>

      <LogConsole lines={lines} />

      {status === 'success' && <p className="banner success">✔ Completed successfully.</p>}
      {status === 'error' && <p className="banner error">✗ {errorMessage}</p>}
      {status === 'ended-unexpectedly' && (
        <p className="banner neutral">
          Connection ended without a final result — check the log above for the last step reached
          (likely a function timeout; safe to retry).
        </p>
      )}
    </>
  );
}
