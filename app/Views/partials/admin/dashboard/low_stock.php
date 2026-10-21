<?php /** @var array $rows */ ?>
<section >
  <header >
    <h3 >Stock bajo</h3>
    <a  href="<?= url('/admin/products') ?>">Ir a productos</a>
  </header>

  <?php if (empty($rows)): ?>
    <p >Todo en orden.</p>
  <?php else: ?>
    <div >
      <div >
        <table >
          <thead>
            <tr>
              <th>SKU</th>
              <th>Producto</th>
              <th>Variante</th>
              <th >Stock</th>
              <th >Umbral</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr class="<?= e((string)($r['row_class'] ?? '')) ?>" data-admin-row-link="1" data-href="<?= e((string)($r['edit_href'] ?? '')) ?>">
                <td><?= e((string)($r['sku'] ?? '')) ?></td>
                <td><?= e((string)($r['name'] ?? '')) ?></td>
                <td><?= !empty($r['variant_title']) ? e((string)$r['variant_title']) : '-' ?></td>
                <td ><?= (int)($r['stock'] ?? 0) ?></td>
                <td ><?= (int)($r['thr'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>
