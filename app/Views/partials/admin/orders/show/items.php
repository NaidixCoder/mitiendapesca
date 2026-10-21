<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h4 class="av3-weight-strong">Productos</h4>
    <div class="av3-type-body av3-text-var-fg">Total: <?= e((string)$orderView['total_fmt']) ?></div>
  </header>

  <?php if (empty($items)): ?>
    <p class="av3-text-var-fg">Este pedido no tiene items.</p>
  <?php else: ?>
    <div class="av3-overflow-x-auto">
      <table class="av3-width-full av3-type-body">
        <thead>
          <tr class="av3-text-left av3-text-var-fg">
            <th>SKU</th>
            <th>Producto</th>
            <th class="av3-text-right">Cantidad</th>
            <th class="av3-text-right">Precio</th>
            <th class="av3-text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr class="av3-border-top av3-border-var-line-soft">
            <td class="av3-pad-y2 av3-pad-right-3"><?= e((string)($it['sku'] ?? '—')) ?></td>
            <td class="av3-pad-y2 av3-pad-right-3">
              <div class="av3-weight-medium"><?= e((string)($it['name'] ?? '—')) ?></div>
              <?php if (!empty($it['variant_label'])): ?>
                <div class="av3-type-caption av3-text-var-fg">Variante: <?= e((string)$it['variant_label']) ?></div>
              <?php endif; ?>
            </td>
            <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= (int)($it['qty'] ?? 0) ?></td>
            <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= e((string)($it['price_fmt'] ?? '$ 0')) ?></td>
            <td class="av3-pad-y2 av3-pad-right-3 av3-text-right"><?= e((string)($it['sub_fmt'] ?? '$ 0')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
