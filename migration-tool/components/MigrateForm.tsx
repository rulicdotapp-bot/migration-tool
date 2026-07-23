'use client';

import { useState, FormEvent, useRef } from 'react';
import LogConsole from './LogConsole';

type Status = 'idle' | 'running' | 'success' | 'error' | 'ended-unexpectedly';

export default function MigrateForm() {
  const [domain, setDomain] = useState('');
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
      formData.append('domain', domain);
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
        <div className="field">
          <label htmlFor="domain">Domain</label>
          <input
            id="domain"
            type="text"
            placeholder="elektricienamsterdam020.com"
            value={domain}
            onChange={(e) => setDomain(e.target.value)}
            disabled={disabled}
            required
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
        <button type="submit" disabled={disabled || !domain}>
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
