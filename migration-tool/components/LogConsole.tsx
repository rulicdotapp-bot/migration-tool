'use client';

import { useEffect, useRef } from 'react';

export default function LogConsole({ lines }: { lines: string[] }) {
  const ref = useRef<HTMLPreElement>(null);

  useEffect(() => {
    if (ref.current) {
      ref.current.scrollTop = ref.current.scrollHeight;
    }
  }, [lines]);

  if (!lines.length) return null;

  return (
    <pre className="log-console" ref={ref}>
      {lines.join('\n')}
    </pre>
  );
}
