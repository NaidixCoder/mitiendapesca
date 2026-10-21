<?php /** @var array $rows */ ?>
<section class="av3-panel av3-ranking">
  <header class="av3-panel__heading">
    <h3 >Productos destacados (30 dias)</h3>
    <a class="av3-action-link" href="<?= url('/admin/products') ?>">Ver todos</a>
  </header>
  <?php if (empty($rows)): ?>
    <p class="av3-empty">Sin ventas todavia.</p>
  <?php else: ?>
  <div class="av3-ranking__body">
    <div class="av3-ranking__scroll">
    <table class="av3-ranking__table">
      <thead>
        <tr>
          <th>SKU</th>
          <th>Producto</th>
          <th>Variante</th>
          <th >Unidades</th>
          <th >Importe</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr data-admin-row-link="1" data-href="<?= e((string)($r['edit_href'] ?? '')) ?>">
            <td><?= e((string)($r['sku'] ?? '-')) ?></td>
            <td><?= e((string)($r['product_name'] ?? '-')) ?></td>
            <td>
              <?php if (!empty($r['variant_title'])): ?>
                <span >
                  <?= e((string)$r['variant_title']) ?>
                </span>
              <?php else: ?>
                <span >-</span>
              <?php endif; ?>
            </td>
            <td ><?= (int)($r['qty'] ?? 0) ?></td>
            <td ><?= e((string)($r['amount_label'] ?? '$ 0')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <?php endif; ?>
</section>
