'use client';

import { useEffect, useState, FormEvent } from 'react';
import Link from 'next/link';

interface SettingsData {
  sshHost: string;
  sshUsername: string;
  sshPort: string;
  hasPrivateKey: boolean;
  hasPassphrase: boolean;
}

export default function SettingsPage() {
  const [data, setData] = useState<SettingsData | null>(null);
  const [sshHost, setSshHost] = useState('');
  const [sshUsername, setSshUsername] = useState('');
  const [sshPort, setSshPort] = useState('');
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
        setSshHost(body.sshHost);
        setSshUsername(body.sshUsername);
        setSshPort(body.sshPort);
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
        body: JSON.stringify({ sshHost, sshUsername, sshPort, sshPrivateKey, sshPrivateKeyPassphrase }),
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

      <p style={{ color: '#9aa1ac', fontSize: 13, marginTop: -8, marginBottom: 24 }}>
        One hosting account, used for every migration — read these off your hosting panel&apos;s SSH access page.
      </p>

      {loadError && <p className="banner error">✗ {loadError}</p>}

      {!data && !loadError && <p>Loading…</p>}

      {data && (
        <form onSubmit={handleSubmit}>
          <div className="field">
            <label htmlFor="sshHost">Hostname</label>
            <input
              id="sshHost"
              type="text"
              value={sshHost}
              onChange={(e) => setSshHost(e.target.value)}
              placeholder="ssh.example.com"
            />
          </div>

          <div className="field">
            <label htmlFor="sshUsername">Username</label>
            <input
              id="sshUsername"
              type="text"
              value={sshUsername}
              onChange={(e) => setSshUsername(e.target.value)}
              placeholder="u123abc"
            />
          </div>

          <div className="field">
            <label htmlFor="sshPort">Port</label>
            <input
              id="sshPort"
              type="number"
              value={sshPort}
              onChange={(e) => setSshPort(e.target.value)}
              placeholder="22"
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

      <p style={{ color: '#6b7280', fontSize: 12, marginTop: 24 }}>
        Get your key&apos;s content with <code>cat ~/.ssh/id_ed25519</code> (or whichever key already works with{' '}
        <code>ssh &lt;that-host&gt;</code> locally) and paste the whole thing above, including the BEGIN/END lines.
      </p>
    </>
  );
}
