<?php
$adminHeaderTitle='Atributos';
$adminHeaderDescription='Definí atributos de variantes y sus valores reutilizables.';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Atributos']];
$adminHeaderActions='<a class="btn-primary" href="'.url('/admin/attributes/new').'">Nuevo atributo</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
    <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2 av3-pt-1">
      <span class="av3-inline-flex av3-items-center av3-gap-2 av3-radius-full av3-border av3-border-var-admin-border av3-surface-white av3-pad-x3 av3-pad-y1 av3-type-caption"><span class="av3-size-h-2 av3-size-w-2 av3-radius-full av3-bg-emerald-500"></span><span class="av3-text-var-admin-muted">Atributos:</span><strong><?= (int)($stats['attributes'] ?? 0) ?></strong></span>
      <span class="av3-inline-flex av3-items-center av3-gap-2 av3-radius-full av3-border av3-border-var-admin-border av3-surface-white av3-pad-x3 av3-pad-y1 av3-type-caption"><span class="av3-size-h-2 av3-size-w-2 av3-radius-full av3-bg-sky-500"></span><span class="av3-text-var-admin-muted">Valores:</span><strong><?= (int)($stats['values'] ?? 0) ?></strong></span>
      <span class="av3-inline-flex av3-items-center av3-gap-2 av3-radius-full av3-border av3-border-var-admin-border av3-surface-white av3-pad-x3 av3-pad-y1 av3-type-caption"><span class="av3-size-h-2 av3-size-w-2 av3-radius-full av3-bg-amber-500"></span><span class="av3-text-var-admin-muted">Tip:</span><span>Cargá valores aquí y luego usalos en <strong>Productos → Variantes</strong>.</span></span>
    </div>
