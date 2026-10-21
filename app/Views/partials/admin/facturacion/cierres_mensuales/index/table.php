<?php
/** @var array<int,array<string,mixed>> $closures_prepared */
/** @var bool $has_closures */
/** @var int $closures_count */
?>
<div class="card av3-pad-4">
  <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h2 class="av3-weight-strong">Ultimos cierres</h2>
    <span class="av3-type-caption av3-text-gray-500"><?= (int)$closures_count ?> registro(s)</span>
  </div>

  <div class="max-h-[60vh] av3-overflow-auto">
    <table class="table av3-width-full av3-type-body">
      <thead class="av3-sticky top-0 av3-surface-white">
        <tr>
          <th class="av3-text-left">Periodo</th>
          <th class="av3-text-right">IVA Ventas</th>
          <th class="av3-text-right">IVA Compras</th>
          <th class="av3-text-right">Saldo IVA</th>
          <th class="av3-text-left">Cerrado</th>
          <th class="av3-text-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$has_closures): ?>
          <tr><td colspan="6" class="av3-text-center av3-text-gray-500 av3-pad-y6">Todavia no hay cierres.</td></tr>
        <?php else: ?>
          <?php foreach ($closures_prepared as $closure): ?>
            <tr>
              <td class="av3-weight-medium"><?= e((string)($closure['period'] ?? '')) ?></td>
              <td class="av3-text-right"><?= e((string)($closure['iva_ventas_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($closure['iva_compras_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($closure['saldo_iva_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-left av3-type-caption av3-text-gray-600"><?= e((string)($closure['closed_at'] ?? '')) ?></td>
              <td class="av3-text-right">
                <a class="btn btn-outline btn-xs" href="<?= e((string)($closure['show_url'] ?? '')) ?>">Abrir</a>
                <a class="btn btn-outline btn-xs" href="<?= e((string)($closure['csv_url'] ?? '')) ?>">CSV</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
