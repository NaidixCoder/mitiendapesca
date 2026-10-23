<?php
/** @var array<int,array<string,mixed>> $rows_prepared */
?>
<div class="max-h-[700px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
  <table class="av3-fiscal-data-table av3-min-w-full av3-type-body">
    <thead class="av3-sticky top-0 av3-surface-white">
      <tr class="av3-text-left av3-border-bottom">
        <th class="av3-pad-2">Pedido</th>
        <th class="av3-pad-2">Ultima emision</th>
        <th class="av3-pad-2">Estado pedido</th>
        <th class="av3-pad-2">Total pedido</th>
        <th class="av3-pad-2">Facturas</th>
        <th class="av3-pad-2">NC</th>
        <th class="av3-pad-2">Neto ARCA</th>
        <th class="av3-pad-2">Resultado fiscal</th>
        <th class="av3-pad-2">Entorno</th>
        <th class="av3-pad-2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows_prepared)): ?>
        <tr><td class="av3-pad-3 av3-text-slate-600" colspan="10">Sin casos relevantes en el periodo.</td></tr>
      <?php else: ?>
        <?php foreach ($rows_prepared as $row): ?>
          <tr class="av3-border-bottom">
            <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['order_url']) ?>">#<?= (int)$row['order_id'] ?></a></td>
            <td class="av3-pad-2"><?= e((string)$row['last_issued_at']) ?></td>
            <td class="av3-pad-2"><?= e((string)$row['order_status_label']) ?></td>
            <td class="av3-pad-2"><?= e((string)$row['order_total']) ?></td>
            <td class="av3-pad-2">
              <div class="av3-text-slate-900 av3-weight-medium"><?= (int)$row['invoices_count'] ?></div>
              <div class="av3-type-caption av3-text-slate-500"><?= e((string)$row['invoices_total']) ?></div>
            </td>
            <td class="av3-pad-2">
              <div class="av3-text-slate-900 av3-weight-medium"><?= (int)$row['credit_count'] ?></div>
              <div class="av3-type-caption av3-text-slate-500"><?= e((string)$row['credit_total']) ?></div>
            </td>
            <td class="av3-pad-2"><?= e((string)$row['net_issued']) ?></td>
            <td class="av3-pad-2">
              <span class="av3-inline-flex av3-pad-x2 av3-pad-yhalf av3-radius-full av3-type-caption av3-border <?= e((string)$row['result_class']) ?>"><?= e((string)$row['result_label']) ?></span>
            </td>
            <td class="av3-pad-2"><?= e((string)$row['env_text']) ?></td>
            <td class="av3-pad-2">
              <a class="underline" href="<?= e((string)$row['comprobantes_url']) ?>">Ver comprobantes</a>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
