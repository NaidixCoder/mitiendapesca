// admin/dashboard/trend_dom.js - refs del widget de tendencia
export function buildTrendUI(root) {
  const $days = root.querySelector('#trend-days');
  const $metric = root.querySelector('#trend-metric');
  const chartWrap = root.querySelector('#trend-chart-wrap');
  const message = root.querySelector('#trend-message');

  let tooltip = document.getElementById('trend-tooltip');
  if (!tooltip) {
    tooltip = document.createElement('div');
    tooltip.id = 'trend-tooltip';
    tooltip.className = 'chart-tooltip av3-hidden';
    document.body.appendChild(tooltip);
  }

  return {
    chartWrap,
    message,
    tooltip,
    $days,
    $metric,
  };
}

export function updateTrendTitle(days, metric) {
  const $days = document.getElementById('trend-title-days');
  const $metric = document.getElementById('trend-title-metric');

  if ($days) $days.textContent = `(${days} dias)`;

  if ($metric) {
    const map = { revenue: 'Ventas ($)', orders: 'Pedidos (#)', avg_ticket: 'Ticket promedio ($)' };
    $metric.textContent = map[metric] || 'Ventas ($)';
  }
}

export function showLoading(chartWrap) {
  if (!chartWrap) return;
  const message = chartWrap.querySelector('#trend-message');
  if (!message) return;
  message.textContent = 'Cargando...';
  message.classList.remove('av3-hidden');
}

export function showMessage(chartWrap, text) {
  if (!chartWrap) return;
  const message = chartWrap.querySelector('#trend-message');
  if (!message) return;
  message.textContent = String(text || '');
  message.classList.remove('av3-hidden');
}
