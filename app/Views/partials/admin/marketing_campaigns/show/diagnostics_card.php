<section class="av3-marketing-board av3-marketing-detail__diagnostics" aria-labelledby="mkt-diagnostics-title">
  <div class="av3-marketing-section-head"><div><h2 id="mkt-diagnostics-title">Qué visitaron los clientes</h2><p>Actividad de las sesiones atribuidas a esta campaña.</p></div></div>
  <div class="av3-marketing-detail__top-grid">
    <section aria-label="Páginas vistas">
      <h3>Páginas vistas</h3>
      <?php if (!$topPages): ?><p class="av3-marketing-detail__small-empty">Sin datos.</p>
      <?php else: ?><ul class="av3-marketing-detail__rank">
        <?php foreach ($topPages as $row): ?>
          <li><span><?php if (!empty($row['is_link'])): ?><a href="<?= e((string)$row['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$row['path']) ?></a><?php else: ?><?= e((string)$row['path']) ?><?php endif; ?></span><strong><?= e((string)$row['views_fmt']) ?></strong></li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
    </section>
    <section aria-label="Productos vistos">
      <h3>Productos vistos</h3>
      <?php if (!$topProductsViewed): ?><p class="av3-marketing-detail__small-empty">Sin datos.</p>
      <?php else: ?><ul class="av3-marketing-detail__rank">
        <?php foreach ($topProductsViewed as $row): ?>
          <li><span><?php if (!empty($row['has_link'])): ?><a href="<?= e((string)$row['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$row['label']) ?></a><?php else: ?><?= e((string)$row['label']) ?><?php endif; ?></span><strong><?= e((string)$row['views_fmt']) ?></strong></li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
    </section>
    <section aria-label="Productos agregados al carrito">
      <h3>Agregados al carrito</h3>
      <?php if (!$topProductsAdded): ?><p class="av3-marketing-detail__small-empty">Sin datos.</p>
      <?php else: ?><ul class="av3-marketing-detail__rank">
        <?php foreach ($topProductsAdded as $row): ?>
          <li><span><?php if (!empty($row['has_link'])): ?><a href="<?= e((string)$row['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$row['label']) ?></a><?php else: ?><?= e((string)$row['label']) ?><?php endif; ?></span><strong><?= e((string)$row['qty_fmt']) ?></strong></li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
    </section>
  </div>
</section>
