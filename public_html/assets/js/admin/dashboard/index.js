// admin/dashboard/index.js — Orquestador del widget de tendencia
import { buildTrendUI, showLoading, showMessage, updateTrendTitle } from './trend_dom.js';
import { fetchTrend } from './trend_api.js';
import { normalizeTrend } from './trend_normalize.js';
import { drawTrend } from './trend_draw.js';

export function initTrendWidget() {
  const root = document.getElementById('trend-root');
  const surface = document.querySelector('.dashboard-v2');
  if (surface) {
    surface.addEventListener('click', (e) => {
      const row = e.target.closest('tr[data-admin-row-link]');
      if (!row) return;
      if (e.target.closest('a,button,select,input,label')) return;
      const href = row.getAttribute('data-href');
      if (href) window.location.href = href;
    });
  }

  if (!root) return;

  const ui = buildTrendUI(root);

  async function refresh() {
    const days = Number(ui.$days.value || 7);
    const metric = ui.$metric.value; // revenue | orders | avg_ticket

    updateTrendTitle(days, metric);

    showLoading(ui.chartWrap);
    try {
      const raw = await fetchTrend(days, document.baseURI);
      const data = normalizeTrend(raw, metric);
      drawTrend(ui.chartWrap, data, metric, ui.tooltip);
    } catch (e) {
      showMessage(ui.chartWrap, 'No se pudo cargar la tendencia.');
    }
  }

  ui.$days.addEventListener('change', refresh);
  ui.$metric.addEventListener('change', refresh);
  refresh();
}
