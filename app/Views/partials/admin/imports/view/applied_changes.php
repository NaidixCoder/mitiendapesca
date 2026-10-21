<?php
$isSuccess = !empty($show['is_success']);
if (!$isSuccess) {
    return;
}
$planStats = is_array($show['plan_stats'] ?? null) ? $show['plan_stats'] : [];
$catalogLists = is_array($show['catalog_lists'] ?? null) ? $show['catalog_lists'] : [];
$optUsedLabels = is_array($show['opt_used_labels'] ?? null) ? $show['opt_used_labels'] : [];
?>
<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h3 class="av3-weight-strong">Cambios aplicados</h3>
    <div class="av3-type-body av3-text-var-muted">Resumen de lo que se ejecuto</div>
  </header>

  <div class="av3-grid av3-md-grid-cols-4 av3-gap-3">
    <div class="av3-kpi"><div class="av3-kpi-label">Productos creados</div><div class="av3-kpi-value"><?= (int)($planStats['create_products'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Productos actualizados</div><div class="av3-kpi-value"><?= (int)($planStats['update_products'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Productos sin cambios</div><div class="av3-kpi-value"><?= (int)($planStats['noop_products'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Variantes creadas</div><div class="av3-kpi-value"><?= (int)($planStats['create_variants'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Variantes actualizadas</div><div class="av3-kpi-value"><?= (int)($planStats['update_variants'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Variantes sin cambios</div><div class="av3-kpi-value"><?= (int)($planStats['noop_variants'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Padres implicitos creados</div><div class="av3-kpi-value"><?= (int)($planStats['create_parents_from_variants'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Marcas creadas</div><div class="av3-kpi-value"><?= (int)($planStats['create_brands'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Categorias creadas</div><div class="av3-kpi-value"><?= (int)($planStats['create_categories'] ?? 0) ?></div></div>
    <div class="av3-kpi"><div class="av3-kpi-label">Proveedores creados</div><div class="av3-kpi-value"><?= (int)($planStats['create_suppliers'] ?? 0) ?></div></div>
  </div>

  <?php if (!empty($catalogLists)): ?>
    <div class="av3-space-top-4 av3-border-top av3-pt-4">
      <div class="av3-flex av3-items-center av3-justify-between">
        <div class="av3-type-body av3-weight-medium">Catalogo creado</div>
        <div class="av3-type-caption av3-text-var-muted">Muestra de nombres (si aplica)</div>
      </div>
      <div class="av3-space-top-3 av3-grid av3-md-grid-cols-3 av3-gap-3">
        <?php foreach ($catalogLists as $list): ?>
          <?php
          $listTitle = (string)($list['title'] ?? '');
          $listCount = (int)($list['count'] ?? 0);
          $listSample = is_array($list['sample'] ?? null) ? (array)$list['sample'] : [];
          $listMore = (int)($list['more'] ?? 0);
          ?>
          <div class="av3-radius-card av3-border av3-pad-3 av3-surface-white">
            <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-2">
              <div class="av3-weight-medium av3-type-body"><?= e($listTitle) ?></div>
              <div class="av3-type-caption av3-text-var-muted"><?= $listCount ?></div>
            </div>
            <?php if (!empty($listSample)): ?>
              <div class="av3-flex av3-flex-wrap av3-gap-2">
                <?php foreach ($listSample as $nm): ?>
                  <span class="badge-pill badge-neutral"><?= e((string)$nm) ?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if ($listMore > 0): ?>
              <div class="av3-space-top-2 av3-type-caption av3-text-var-muted">+<?= $listMore ?> mas</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="av3-space-top-4">
    <div class="av3-type-caption av3-text-var-muted av3-space-bottom-2">Opciones usadas</div>
    <div class="av3-flex av3-flex-wrap av3-gap-2">
      <?php foreach ($optUsedLabels as $label): ?>
        <span class="badge-pill badge-neutral"><?= e((string)$label) ?></span>
      <?php endforeach; ?>
      <?php if (empty($optUsedLabels)): ?>
        <span class="av3-type-body av3-text-var-muted">-</span>
      <?php endif; ?>
    </div>
  </div>
</section>
