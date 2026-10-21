// admin/dashboard/trend_api.js — Acceso a endpoint de tendencia
export async function fetchTrend(days, baseURI) {
  const candidates = [
    new URL('admin/dashboard/trend.json', baseURI),
    new URL('admin/dashboard/trend', baseURI),
  ];

  for (const u of candidates) {
    try {
      u.searchParams.set('days', String(days));
      const r = await fetch(u, { cache: 'no-store', credentials: 'same-origin' });
      if (!r.ok) continue;
      const j = await r.json();
      if (j && j.ok && Array.isArray(j.data)) return j.data;
    } catch (_) {}
  }

  throw new Error('trend endpoint not reachable');
}
