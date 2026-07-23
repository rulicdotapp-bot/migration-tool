'use client';

import { useRouter } from 'next/navigation';
import MigrateForm from '../components/MigrateForm';

export default function DashboardPage() {
  const router = useRouter();

  async function handleLogout() {
    await fetch('/api/logout', { method: 'POST' });
    router.push('/login');
    router.refresh();
  }

  return (
    <>
      <div className="top-bar">
        <h1>Site Migration Tool</h1>
        <div style={{ display: 'flex', alignItems: 'center', gap: 16 }}>
          <button onClick={handleLogout}>Sign out</button>
        </div>
      </div>
      <MigrateForm />
    </>
  );
}
