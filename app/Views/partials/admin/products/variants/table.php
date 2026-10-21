<section class="av3-card av3-p-0 av3-overflow-hidden">
  <header class="av3-card-header">
    <h3 class="av3-card-title">Listado de variantes</h3>
  </header>

  <div class="max-h-[70vh] av3-overflow-auto">
    <table class="av3-table av3-width-full av3-type-body">
      <thead class="av3-sticky top-0 av3-surface-white">
        <tr>
          <th class="av3-text-left">SKU</th>
          <th class="av3-text-left">Variante</th>
          <th class="av3-text-right">Precio</th>
          <th class="av3-text-right">Stock</th>
          <th class="av3-text-left">Estado</th>
          <th class="av3-text-left">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($variants)): foreach ($variants as $v):
          $vid = (int)($v['id'] ?? 0);
          $sku = (string)($v['sku'] ?? '');
          $title = trim((string)($v['title'] ?? ''));
          $price = $v['price'] ?? null;
          $stock = $v['stock'] ?? null;
          $active = !empty($v['is_active']);

          if ($title === '' && !empty($attrsMap[$vid])) {
            $parts = [];
            foreach ($attrsMap[$vid] as $a) {
              $an = trim((string)($a['attribute'] ?? ''));
              $av = trim((string)($a['value'] ?? ''));
              if ($an === '' || $av === '') continue;
              $parts[] = $an.': '.$av;
            }
            $title = implode(' � ', $parts);
          }
          if ($title === '') $title = '�';
        ?>
          <tr>
            <td class="whitespace-nowrap"><?= $sku !== '' ? e($sku) : '<span class="av3-text-gray-400">�</span>' ?></td>
            <td class="av3-weight-medium"><?= e($title) ?></td>
            <td class="av3-text-right whitespace-nowrap"><?php if ($price === null || $price === '') { echo '<span class="av3-text-gray-500">Precio a consultar</span>'; } else { echo e('$'.number_format((int)$price, 0, ',', '.')); } ?></td>
            <td class="av3-text-right whitespace-nowrap"><?php if ($stock === null || $stock === '') { echo '<span class="av3-text-gray-400">�</span>'; } else { echo e(number_format((int)$stock, 0, ',', '.')); } ?></td>
            <td><span class="badge <?= $active ?'badge-ok' : 'badge-muted' ?>"><?= $active ? 'activa' : 'pausada' ?></span></td>
            <td><a class="btn-secondary btn-sm" href="<?= url('/admin/products/edit?id='.(int)$productId) ?>">Editar</a></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="av3-py-10 av3-text-center av3-text-var-admin-muted">Este producto no tiene variantes.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="av3-pad-x4 av3-pad-y3 av3-type-caption av3-text-gray-500">Tip: este listado es para ver r�pidamente SKUs, precios y stock por variante. La edici�n completa se hace desde el producto.</div>
</section>
