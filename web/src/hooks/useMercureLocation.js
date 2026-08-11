import { useEffect, useRef } from 'react';

const MERCURE_PUBLIC_URL = import.meta.env.VITE_MERCURE_PUBLIC_URL ?? 'http://localhost:3001/.well-known/mercure';

/**
 * Subscribe to a Mercure location topic and call onEvent for each message.
 * Automatically reconnects on error.
 */
export function useMercureLocation(locationSlug, onEvent) {
  const esRef = useRef(null);
  const onEventRef = useRef(onEvent);
  onEventRef.current = onEvent;

  useEffect(() => {
    if (!locationSlug) return;

    const url = new URL(MERCURE_PUBLIC_URL);
    url.searchParams.append('topic', `location/${locationSlug}`);

    function connect() {
      const es = new EventSource(url.toString());
      esRef.current = es;

      es.onmessage = (e) => {
        try {
          const data = JSON.parse(e.data);
          onEventRef.current(data);
        } catch {}
      };

      es.onerror = () => {
        es.close();
        // Reconnect after 3s
        setTimeout(connect, 3000);
      };
    }

    connect();

    return () => {
      esRef.current?.close();
    };
  }, [locationSlug]);
}
