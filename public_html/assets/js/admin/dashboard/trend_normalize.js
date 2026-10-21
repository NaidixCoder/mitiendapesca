// admin/dashboard/trend_normalize.js — Normaliza datos para el gráfico
function safeInt(x) {
  const n = Number(x);
  return Number.isFinite(n) ? n : 0;
}

export function normalizeTrend(raw, metric) {
  const rows = Array.isArray(raw) ? raw : [];
  return rows.map((r) => {
    const day = String(r.d || '');
    const revenue = safeInt(r.total);
    const orders = safeInt(r.orders);
    const avg = orders > 0 ? Math.round(revenue / orders) : 0;

    let value = revenue;
    if (metric === 'orders') value = orders;
    if (metric === 'avg_ticket') value = avg;

    return { day, revenue, orders, avg, value };
  });
}
