<?php
/** @var array $navigationAnalytics */
$na = $navigationAnalytics ?? [];
$days = (int)($na['days'] ?? 30);
$funnel = $na['funnel_cards'] ?? [];
$reports = [
  ['title' => 'Páginas más visitadas', 'detail' => 'Eventos page_view', 'key' => 'top_pages', 'columns' => ['Página', 'Vistas'], 'fields' => ['label', 'metric']],
  ['title' => 'Productos más visitados', 'detail' => 'Eventos product_view', 'key' => 'top_products', 'columns' => ['Producto', 'Vistas'], 'fields' => ['label', 'metric']],
  ['title' => 'Páginas de entrada', 'detail' => 'Primera página de cada sesión', 'key' => 'landing_pages', 'columns' => ['Página', 'Sesiones'], 'fields' => ['label', 'metric']],
  ['title' => 'Origen del tráfico', 'detail' => 'Referidos y campañas UTM', 'key' => 'top_sources', 'columns' => ['Fuente', 'Campaña', 'Sesiones'], 'fields' => ['source', 'campaign', 'sessions']],
  ['title' => 'Productos agregados al carrito', 'detail' => 'Eventos add_to_cart', 'key' => 'top_add_to_cart', 'columns' => ['Producto', 'Variante', 'Agregados'], 'fields' => ['label', 'variant_title', 'metric']],
  ['title' => 'De visita a carrito', 'detail' => 'Conversión por variante', 'key' => 'top_view_to_cart', 'columns' => ['Producto', 'Variante', 'Vistas', 'Agregados', 'Conversión'], 'fields' => ['label', 'variant_title', 'views', 'adds', 'conversion']],
];
?>
<div class="av3-analytics__toolbar">
  <div class="av3-analytics__period"><span class="av3-analytics__period-icon" aria-hidden="true">◷</span> Últimos <?= $days ?> días</div>
  <span class="av3-analytics__tag av3-ui-badge">Seguimiento propio</span>
</div>
<section class="av3-analytics__kpis" aria-label="Indicadores de conversión">
  <?php foreach ($funnel as $i => $card): ?>
    <article class="av3-analytics__kpi">
      <p class="av3-analytics__kpi-label"><?= e((string)($card['label'] ?? 'Indicador')) ?></p>
      <p class="av3-analytics__kpi-value"><?= e(is_int($card['value'] ?? null) ? number_format((int)$card['value'], 0, ',', '.') : (string)($card['value'] ?? '—')) ?></p>
      <div class="av3-analytics__kpi-rule"></div>
    </article>
  <?php endforeach; ?>
</section>
<section class="av3-analytics__section av3-analytics__funnel-section" aria-labelledby="analytics-journey-title">
  <div class="av3-analytics__section-heading">
    <div><h2 id="analytics-journey-title">Embudo de conversión</h2><p>Del checkout a la compra registrada.</p></div>
  </div>
  <?php
    $visits = isset($funnel[0]['value']) && is_numeric($funnel[0]['value']) ? max(0, (int)$funnel[0]['value']) : 0;
    $purchases = isset($funnel[1]['value']) && is_numeric($funnel[1]['value']) ? max(0, (int)$funnel[1]['value']) : 0;
    $ratio = $visits > 0 ? min(100, ($purchases / $visits) * 100) : 0;
  ?>
  <div class="av3-analytics__funnel-layout"><div class="av3-analytics__funnel">
    <div class="av3-analytics__funnel-row"><div class="av3-analytics__funnel-label"><span>Checkout iniciado</span><strong><?= number_format($visits, 0, ',', '.') ?></strong></div><div class="av3-analytics__funnel-track"><span class="av3-analytics__funnel-fill av3-analytics__funnel-fill--start" style="width:<?= $visits > 0 ? '100' : '0' ?>%"></span></div></div>
    <div class="av3-analytics__funnel-row"><div class="av3-analytics__funnel-label"><span>Compras registradas</span><strong><?= number_format($purchases, 0, ',', '.') ?></strong></div><div class="av3-analytics__funnel-track"><span class="av3-analytics__funnel-fill" style="width:<?= number_format($ratio, 2, '.', '') ?>%"></span></div></div>
    <div class="av3-analytics__funnel-summary">Conversión: <strong><?= $visits > 0 ? number_format($ratio, 2, ',', '.') . '%' : 'Sin datos suficientes' ?></strong></div>
  </div>
  <aside class="av3-analytics__overview" aria-label="Resumen de tráfico">
    <h3>Actividad registrada</h3>
    <p>Datos del mismo período, sin estimaciones.</p>
    <dl>
      <div><dt>Páginas con visitas</dt><dd><?= count($na['top_pages'] ?? []) ?></dd></div>
      <div><dt>Fuentes de tráfico</dt><dd><?= count($na['top_sources'] ?? []) ?></dd></div>
      <div><dt>Productos consultados</dt><dd><?= count($na['top_products'] ?? []) ?></dd></div>
    </dl>
  </aside></div>
</section>
<section class="av3-analytics__section" aria-labelledby="analytics-reports-title">
  <div class="av3-analytics__section-heading"><div><h2 id="analytics-reports-title">Informes de navegación</h2><p>Resultados registrados en el período seleccionado.</p></div></div>
  <div class="av3-analytics__reports">
    <?php foreach ($reports as $report): ?>
      <article class="av3-analytics__panel av3-ui-card">
        <header class="av3-analytics__panel-heading">
          <div><h3><?= e($report['title']) ?></h3><p><?= e($report['detail']) ?></p></div>
          <span class="av3-analytics__count"><?= count($na[$report['key']] ?? []) ?> registros</span>
        </header>
        <div class="av3-analytics__table-wrap av3-ui-table-scroll">
          <table class="av3-analytics__table av3-ui-table">
            <thead><tr><?php foreach ($report['columns'] as $index => $label): ?><th scope="col"<?= $index === count($report['columns']) - 1 ? ' class="av3-analytics__numeric"' : '' ?>><?= e($label) ?></th><?php endforeach; ?></tr></thead>
            <tbody>
              <?php if (empty($na[$report['key']])): ?>
                <tr><td colspan="<?= count($report['columns']) ?>" class="av3-analytics__empty"><span class="av3-analytics__empty-icon" aria-hidden="true">▤</span><strong>Sin datos registrados</strong><span>Todavía no hay actividad para mostrar en este informe.</span></td></tr>
              <?php else: foreach ($na[$report['key']] as $row): ?>
                <tr>
                  <?php foreach ($report['fields'] as $index => $field): ?>
                    <td<?= $index === count($report['fields']) - 1 ? ' class="av3-analytics__numeric"' : '' ?>>
                      <?php $value = $row[$field] ?? null; ?>
                      <?php if ($index === 0 && !empty($row['href'])): ?>
                        <a class="av3-analytics__data-link" href="<?= e((string)$row['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)($value ?? '—')) ?></a>
                      <?php elseif (is_int($value)): ?>
                        <?= number_format($value, 0, ',', '.') ?>
                      <?php else: ?>
                        <?= e((string)($value === null || $value === '' ? '—' : $value)) ?>
                      <?php endif; ?>
                      <?php if ($field === 'source' && !empty($row['medium'])): ?><span class="av3-analytics__secondary"><?= e((string)$row['medium']) ?></span><?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<footer class="av3-analytics__footer">Los informes utilizan eventos propios de la tienda. No muestran información personal identificable.</footer>
