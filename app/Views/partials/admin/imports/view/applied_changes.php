<?php
if (empty($show['is_success'])) { return; }
$hasDiagnostics = !empty($show['has_diagnostics']);
$planStats = is_array($show['plan_stats'] ?? null) ? $show['plan_stats'] : [];
$catalogLists = is_array($show['catalog_lists'] ?? null) ? $show['catalog_lists'] : [];
$optUsedLabels = is_array($show['opt_used_labels'] ?? null) ? $show['opt_used_labels'] : [];
$statsLabels = [
  'create_products' => 'Productos creados', 'update_products' => 'Productos actualizados', 'noop_products' => 'Productos sin cambios',
  'create_variants' => 'Variantes creadas', 'update_variants' => 'Variantes actualizadas', 'noop_variants' => 'Variantes sin cambios',
  'create_parents_from_variants' => 'Padres implícitos creados',
  'create_brands' => 'Marcas creadas', 'create_categories' => 'Categorías creadas', 'create_suppliers' => 'Proveedores creados',
];
?>
<section class="av3-ui-card av3-imports__block" aria-labelledby="imports-applied-heading">
  <header class="av3-imports__block-head"><h3 id="imports-applied-heading">Cambios ejecutados</h3><span>Recuento registrado por el importador</span></header>
  <div class="av3-imports__block-body">
    <?php if (!$hasDiagnostics): ?>
      <p class="av3-imports__hint">El resumen por fila no está disponible en los diagnósticos conservados. Esto no significa que no se hayan realizado cambios: consultá el snapshot y la auditoría para verificar la ejecución.</p>
    <?php else: ?>
    <dl class="av3-imports__changes-grid">
      <?php foreach ($statsLabels as $key => $label): ?>
        <?php if ((int)($planStats[$key] ?? 0) <= 0) continue; ?>
        <div class="av3-imports__change-count"><dt><?= e($label) ?></dt><dd><?= (int)$planStats[$key] ?></dd></div>
      <?php endforeach; ?>
    </dl>
    <?php if (!array_filter($planStats, static fn($value) => (int)$value > 0)): ?>
      <p class="av3-imports__hint">No se registraron altas ni actualizaciones en esta ejecución.</p>
    <?php endif; ?>
    <?php if ($catalogLists): ?>
      <div class="av3-imports__meta-divider"></div>
      <h4 class="av3-imports__subheading">Entidades nuevas del catálogo</h4>
      <div class="av3-imports__catalog-preview av3-imports__catalog-preview--detail">
        <?php foreach ($catalogLists as $list): ?>
          <details class="av3-imports__mini-details">
            <summary><?= e((string)($list['title'] ?? '')) ?><span class="av3-ui-badge av3-ui-badge--neutral"><?= (int)($list['count'] ?? 0) ?></span><span class="av3-imports__chevron" aria-hidden="true"></span></summary>
            <div class="av3-imports__chips">
              <?php foreach ((array)($list['sample'] ?? []) as $name): ?><span class="av3-imports__chip"><?= e((string)$name) ?></span><?php endforeach; ?>
              <?php if (!empty($list['more'])): ?><span class="av3-imports__hint">Y <?= (int)$list['more'] ?> más</span><?php endif; ?>
            </div>
          </details>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($optUsedLabels): ?>
      <div class="av3-imports__meta-divider"></div>
      <details class="av3-imports__mini-details av3-imports__used-options">
        <summary>Opciones utilizadas <span class="av3-ui-badge av3-ui-badge--neutral"><?= count($optUsedLabels) ?></span><span class="av3-imports__chevron" aria-hidden="true"></span></summary>
        <div class="av3-imports__chips"><?php foreach ($optUsedLabels as $label): ?><span class="av3-imports__chip"><?= e((string)$label) ?></span><?php endforeach; ?></div>
      </details>
    <?php endif; ?>
  </div>
</section>
