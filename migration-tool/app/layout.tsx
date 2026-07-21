import type { Metadata } from 'next';
import './globals.css';

export const metadata: Metadata = {
  title: 'Site Migration Tool',
  description: 'Internal tool — migrate electrician sites into the theme.',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>
        <main>{children}</main>
      </body>
    </html>
  );
}
