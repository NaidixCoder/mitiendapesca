<?php /** @var array<int,array<string,mixed>> $rows_prepared */ ?>
<div class="catalog-table-scroll">
  <table class="catalog-table">
    <thead><tr>
      <th scope="col">PRODUCTO</th><th scope="col">PRECIO</th><th scope="col">ACTUALIZADO</th><th scope="col">VARIANTES</th><th scope="col">VISIBILIDAD</th><th scope="col"><span class="sr-only">Acciones</span></th>
    </tr></thead>
    <tbody>
      <?php if (!empty($rows_prepared)): foreach ($rows_prepared as $r): ?>
        <tr>
          <td>
            <div class="catalog-product-cell">
              <span class="catalog-product-avatar" aria-hidden="true">▦</span>
              <span class="catalog-product-description">
                <strong><?= e((string)($r['name'] ?? '')) ?></strong>
                <small><?= !empty($r['sku_empty']) ? 'Sin SKU' : e((string)($r['sku_text'] ?? '')) ?></small>
              </span>
            </div>
          </td>
          <td class="catalog-price"><?= e((string)($r['price_text'] ?? 'Precio a consultar')) ?></td>
          <td class="catalog-muted"><?= !empty($r['updated_at_empty']) ? '—' : e((string)($r['updated_at_text'] ?? '')) ?></td>
          <td><?php if (!empty($r['has_variants_link'])): ?>
            <a class="catalog-count" href="<?= e((string)($r['variants_url'] ?? '')) ?>" aria-label="<?= (int)($r['variants_count'] ?? 0) ?> variantes"><?= (int)($r['variants_count'] ?? 0) ?> <span>Ver</span></a>
            <?php else: ?><span class="catalog-muted">—</span><?php endif; ?>
          </td>
          <td><span class="catalog-status <?= e((string)($r['status_badge_class'] ?? 'badge-muted')) ?>"><span class="catalog-status__dot" aria-hidden="true"></span><?= e((string)($r['status_label'] ?? 'Inactivo')) ?></span></td>
          <td class="catalog-table__action"><a class="catalog-button catalog-button--small catalog-button--outline" href="<?= e((string)($r['edit_url'] ?? '#')) ?>">Editar <span aria-hidden="true">→</span></a></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6" class="catalog-no-results"><strong>No encontramos productos</strong><span>Probá otra búsqueda o creá un producto nuevo.</span></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
