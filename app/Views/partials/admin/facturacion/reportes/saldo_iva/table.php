<?php
/** @var array<int,array<string,mixed>> $rows_prepared */
/** @var bool $has_rows */
?>
<div class="card av3-pad-4 av3-overflow-auto" style="max-height: 520px;">
  <table class="table av3-width-full">
    <thead class="av3-sticky top-0 av3-surface-white">
      <tr>
        <th>Periodo</th>
        <th class="av3-text-right">IVA Ventas</th>
        <th class="av3-text-right">IVA Compras</th>
        <th class="av3-text-right">Saldo IVA</th>
        <th class="av3-text-right">Ventas Total</th>
        <th class="av3-text-right">Compras Total</th>
        <th class="av3-text-right">Comp. Ventas</th>
        <th class="av3-text-right">Comp. Compras</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows_prepared as $row): ?>
        <tr>
          <td><?= e((string)($row['ym'] ?? '')) ?></td>
          <td class="av3-text-right">$ <?= e((string)($row['ventas_iva_label'] ?? '0,00')) ?></td>
          <td class="av3-text-right">$ <?= e((string)($row['compras_iva_label'] ?? '0,00')) ?></td>
          <td class="av3-text-right">$ <?= e((string)($row['saldo_label'] ?? '0,00')) ?></td>
          <td class="av3-text-right">$ <?= e((string)($row['ventas_total_label'] ?? '0,00')) ?></td>
          <td class="av3-text-right">$ <?= e((string)($row['compras_total_label'] ?? '0,00')) ?></td>
          <td class="av3-text-right"><?= (int)($row['ventas_comprobantes'] ?? 0) ?></td>
          <td class="av3-text-right"><?= (int)($row['compras_comprobantes'] ?? 0) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$has_rows): ?>
        <tr><td colspan="8" class="av3-text-center av3-text-slate-500 av3-pad-y6">Sin datos para el periodo seleccionado.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
