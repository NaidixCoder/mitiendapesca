from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'app/Views/partials/admin/analytics/overview.php'
s=p.read_text(encoding='utf8')
start=s.index('<section class="av3-analytics__section" aria-labelledby="analytics-journey-title">')
end=s.index('<section class="av3-analytics__section" aria-labelledby="analytics-reports-title">')
block='''<section class="av3-analytics__section av3-analytics__funnel-section" aria-labelledby="analytics-journey-title">
  <div class="av3-analytics__section-heading">
    <div><h2 id="analytics-journey-title">Embudo de conversión</h2><p>Del checkout a la compra registrada.</p></div>
  </div>
  <?php
    $visits = isset($funnel[0]['value']) && is_numeric($funnel[0]['value']) ? max(0, (int)$funnel[0]['value']) : 0;
    $purchases = isset($funnel[1]['value']) && is_numeric($funnel[1]['value']) ? max(0, (int)$funnel[1]['value']) : 0;
    $ratio = $visits > 0 ? min(100, ($purchases / $visits) * 100) : 0;
  ?>
  <div class="av3-analytics__funnel">
    <div class="av3-analytics__funnel-row"><div class="av3-analytics__funnel-label"><span>Checkout iniciado</span><strong><?= number_format($visits, 0, ',', '.') ?></strong></div><div class="av3-analytics__funnel-track"><span class="av3-analytics__funnel-fill av3-analytics__funnel-fill--start" style="width:<?= $visits > 0 ? '100' : '0' ?>%"></span></div></div>
    <div class="av3-analytics__funnel-row"><div class="av3-analytics__funnel-label"><span>Compras registradas</span><strong><?= number_format($purchases, 0, ',', '.') ?></strong></div><div class="av3-analytics__funnel-track"><span class="av3-analytics__funnel-fill" style="width:<?= number_format($ratio, 2, '.', '') ?>%"></span></div></div>
    <div class="av3-analytics__funnel-summary">Conversión: <strong><?= $visits > 0 ? number_format($ratio, 2, ',', '.') . '%' : 'Sin datos suficientes' ?></strong></div>
  </div>
</section>
'''
s=s[:start]+block+s[end:]
p.write_text(s,encoding='utf8')
p=r/'public_html/assets/css/admin-v3/pages/analytics.css'
s=p.read_text(encoding='utf8')
s += '''
/* Ajuste de densidad exclusivo de Analíticas. Mantiene .av3-wrap y el layout global. */
body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:repeat(3,minmax(0,300px));gap:12px;justify-content:start;margin-bottom:24px}
body[data-page^="admin"] .av3-analytics__kpi{min-height:100px;padding:16px 18px}
body[data-page^="admin"] .av3-analytics__kpi-label{font-size:13px;margin-bottom:10px;font-weight:500}
body[data-page^="admin"] .av3-analytics__kpi-value{font-size:26px;font-weight:600}
body[data-page^="admin"] .av3-analytics__reports{grid-template-columns:repeat(2,minmax(0,480px));justify-content:start;gap:16px}
body[data-page^="admin"] .av3-analytics__section-heading h2{font-weight:600}
body[data-page^="admin"] .av3-analytics__funnel{max-width:920px;padding:18px 20px;background:#fff;border:1px solid var(--av3-color-border);border-radius:11px}
body[data-page^="admin"] .av3-analytics__funnel-row+.av3-analytics__funnel-row{margin-top:14px}
body[data-page^="admin"] .av3-analytics__funnel-label{display:flex;justify-content:space-between;gap:12px;font-size:13px;color:#455b68;margin-bottom:7px}
body[data-page^="admin"] .av3-analytics__funnel-label strong{font-weight:600;color:#182b39}
body[data-page^="admin"] .av3-analytics__funnel-track{height:12px;overflow:hidden;border-radius:6px;background:#ebf1f2}
body[data-page^="admin"] .av3-analytics__funnel-fill{display:block;height:100%;background:#006b6b;border-radius:6px}
body[data-page^="admin"] .av3-analytics__funnel-fill--start{background:#7dbcb5}
body[data-page^="admin"] .av3-analytics__funnel-summary{margin-top:12px;font-size:12px;color:#647786}
body[data-page^="admin"] .av3-analytics__funnel-summary strong{color:#182b39}
body[data-page^="admin"] .av3-analytics__panel-heading h3{font-size:14px;font-weight:600}
body[data-page^="admin"] .av3-analytics__table td{font-size:13px}
@media(max-width:1100px){body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:repeat(3,minmax(0,1fr))}body[data-page^="admin"] .av3-analytics__reports{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:780px){body[data-page^="admin"] .av3-analytics__reports{grid-template-columns:1fr}body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:480px){body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:1fr}body[data-page^="admin"] .av3-analytics__funnel{padding:14px}}
'''
p.write_text(s,encoding='utf8')
print('ANALYTICS_COMPACTED')
