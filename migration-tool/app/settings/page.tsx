'use client';

import { useEffect, useState, FormEvent } from 'react';
import Link from 'next/link';

interface SettingsData {
  sshAccountsJson: string;
  hasPrivateKey: boolean;
  hasPassphrase: boolean;
}

export default function SettingsPage() {
  const [data, setData] = useState<SettingsData | null>(null);
  const [sshAccountsJson, setSshAccountsJson] = useState('');
  const [sshPrivateKey, setSshPrivateKey] = useState('');
  const [sshPrivateKeyPassphrase, setSshPrivateKeyPassphrase] = useState('');
  const [loadError, setLoadError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [saveMessage, setSaveMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  useEffect(() => {
    fetch('/api/settings')
      .then(async (res) => {
        const body = await res.json();
        if (!res.ok) throw new Error(body.error || `Request failed (${res.status})`);
        return body as SettingsData;
      })
      .then((body) => {
        setData(body);
        setSshAccountsJson(body.sshAccountsJson);
      })
      .catch((err) => setLoadError(err instanceof Error ? err.message : String(err)));
  }, []);

  async function handleSubmit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setSaveMessage(null);
    try {
      const res = await fetch('/api/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sshAccountsJson, sshPrivateKey, sshPrivateKeyPassphrase }),
      });
      const body = await res.json();
      if (!res.ok) throw new Error(body.error || `Request failed (${res.status})`);
      setSaveMessage({ type: 'success', text: 'Saved to .env.local.' });
      // Clear the secret fields after a successful save — they're
      // write-only, so there's nothing meaningful left to show in them.
      setSshPrivateKey('');
      setSshPrivateKeyPassphrase('');
      const refreshed = await fetch('/api/settings').then((r) => r.json());
      setData(refreshed);
    } catch (err) {
      setSaveMessage({ type: 'error', text: err instanceof Error ? err.message : String(err) });
    } finally {
      setSaving(false);
    }
  }

  return (
    <>
      <div className="top-bar">
        <h1>Settings</h1>
        <Link href="/">&larr; Back to dashboard</Link>
      </div>

      {loadError && <p className="banner error">✗ {loadError}</p>}

      {!data && !loadError && <p>Loading…</p>}

      {data && (
        <form onSubmit={handleSubmit}>
          <div className="field">
            <label htmlFor="sshAccountsJson">SSH accounts (domain allow-list)</label>
            <textarea
              id="sshAccountsJson"
              rows={8}
              value={sshAccountsJson}
              onChange={(e) => setSshAccountsJson(e.target.value)}
              placeholder='[{"host":"ssh.example.com","port":18765,"user":"u123abc","domains":["example.com"]}]'
            />
          </div>

          <div className="field">
            <label htmlFor="sshPrivateKey">
              SSH private key {data.hasPrivateKey ? '(currently set — paste to replace)' : '(not set)'}
            </label>
            <textarea
              id="sshPrivateKey"
              rows={6}
              value={sshPrivateKey}
              onChange={(e) => setSshPrivateKey(e.target.value)}
              placeholder="-----BEGIN OPENSSH PRIVATE KEY-----&#10;...&#10;-----END OPENSSH PRIVATE KEY-----"
            />
          </div>

          <div className="field">
            <label htmlFor="sshPassphrase">
              Private key passphrase {data.hasPassphrase ? '(currently set — enter to replace)' : '(not set, if none)'}
            </label>
            <input
              id="sshPassphrase"
              type="password"
              value={sshPrivateKeyPassphrase}
              onChange={(e) => setSshPrivateKeyPassphrase(e.target.value)}
            />
          </div>

          <button type="submit" disabled={saving}>
            {saving ? 'Saving…' : 'Save'}
          </button>

          {saveMessage && <p className={`banner ${saveMessage.type}`}>{saveMessage.text}</p>}
        </form>
      )}
    </>
  );
}
