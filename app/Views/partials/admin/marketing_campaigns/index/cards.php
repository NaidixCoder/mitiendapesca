<?php
$campaigns = is_array($campaigns_prepared ?? null) ? $campaigns_prepared : [];
$pagination = is_array($campaign_pagination ?? null) ? $campaign_pagination : [];
?>
<section class="av3-marketing-board av3-marketing-results" aria-labelledby="av3-marketing-results-title">
  <header class="av3-marketing-results__header">
    <div>
      <h2 id="av3-marketing-results-title">Campañas</h2>
      <p>Resultados del período seleccionado · <?= e((string)($campaign_range ?? '')) ?></p>
    </div>
  </header>
  <?php if (!$campaigns): ?>
    <div class="av3-marketing-empty">
      <div class="av3-marketing-empty__icon" aria-hidden="true">↗</div>
      <h3><?= !empty($campaign_filter_active) ? 'No se encontraron campañas' : 'Todavía no creaste campañas' ?></h3>
      <p><?= !empty($campaign_filter_active) ? 'Probá otro término o cambiá el filtro de estado.' : 'Creá un enlace para Instagram, WhatsApp o tus promociones y medí sus resultados.' ?></p>
      <?php if (!empty($campaign_filter_active)): ?>
        <a class="av3-ui-btn" href="<?= url('/admin/marketing/campanas') ?>">Quitar filtros</a>
      <?php else: ?>
        <a class="av3-ui-btn av3-ui-btn--primary" href="<?= url('/admin/marketing/campanas/new') ?>">Crear primera campaña</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="av3-marketing-table-wrap">
      <table class="av3-marketing-table">
        <thead><tr><th scope="col">Campaña</th><th scope="col">Visitas</th><th scope="col">Carrito</th><th scope="col">Compras</th><th scope="col">Ingresos</th><th scope="col">Estado</th><th scope="col" class="av3-marketing-table__right">Acciones</th></tr></thead>
        <tbody>
          <?php foreach ($campaigns as $c): ?>
          <tr>
            <td>
              <a class="av3-marketing-name" href="<?= e((string)$c['detail_url']) ?>"><?= e((string)$c['name']) ?></a>
              <div class="av3-marketing-subline"><span title="<?= e((string)$c['promo_url']) ?>"><?= e((string)$c['promo_path']) ?></span><button type="button" class="av3-marketing-copy" data-copy-text="<?= e((string)$c['promo_url']) ?>">Copiar</button></div>
            </td>
            <td class="av3-marketing-number"><?= (int)$c['hits'] ?></td>
            <td class="av3-marketing-number"><?= (int)$c['add_to_cart'] ?></td>
            <td class="av3-marketing-number"><?= (int)$c['purchases'] ?></td>
            <td class="av3-marketing-number"><?= e((string)$c['revenue_label']) ?></td>
            <td><span class="av3-marketing-status<?= !empty($c['is_active']) ? ' is-active' : '' ?>"><?= e((string)$c['state']) ?></span></td>
            <td class="av3-marketing-table__right">
              <a class="av3-marketing-action" href="<?= e((string)$c['detail_url']) ?>">Detalle</a>
              <a class="av3-marketing-action" href="<?= e((string)$c['edit_url']) ?>">Editar</a>
              <form class="av3-marketing-inline-action" method="post" action="<?= url('/admin/marketing/campanas/toggle') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <input type="hidden" name="is_active" value="<?= !empty($c['is_active']) ? 0 : 1 ?>">
                <button type="submit"><?= !empty($c['is_active']) ? 'Desactivar' : 'Activar' ?></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="av3-marketing-mobile-list">
      <?php foreach ($campaigns as $c): ?>
        <article class="av3-marketing-mobile-item">
          <div class="av3-marketing-mobile-item__top">
            <div><a class="av3-marketing-name" href="<?= e((string)$c['detail_url']) ?>"><?= e((string)$c['name']) ?></a><span class="av3-marketing-subline"><?= e((string)$c['promo_path']) ?></span></div>
            <span class="av3-marketing-status<?= !empty($c['is_active']) ? ' is-active' : '' ?>"><?= e((string)$c['state']) ?></span>
          </div>
          <div class="av3-marketing-mobile-stats"><span><strong><?= (int)$c['hits'] ?></strong> visitas</span><span><strong><?= (int)$c['purchases'] ?></strong> compras</span><span><strong><?= e((string)$c['revenue_label']) ?></strong> ingresos</span></div>
          <div class="av3-marketing-mobile-item__actions">
            <a href="<?= e((string)$c['detail_url']) ?>">Ver detalle <span aria-hidden="true">→</span></a>
            <button type="button" data-copy-text="<?= e((string)$c['promo_url']) ?>">Copiar enlace</button>
            <a href="<?= e((string)$c['edit_url']) ?>">Editar</a>
            <form class="av3-marketing-inline-action" method="post" action="<?= url('/admin/marketing/campanas/toggle') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <input type="hidden" name="is_active" value="<?= !empty($c['is_active']) ? 0 : 1 ?>">
              <button type="submit"><?= !empty($c['is_active']) ? 'Desactivar' : 'Activar' ?></button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if ($pagination): ?>
      <nav class="av3-marketing-pagination" aria-label="Páginas de campañas">
        <span><?= e((string)$campaign_range) ?></span>
        <div><?php foreach ($pagination as $link): ?>
          <?php if (!empty($link['disabled'])): ?><span class="is-disabled"><?= e((string)$link['label']) ?></span>
          <?php elseif (!empty($link['active'])): ?><span aria-current="page" class="is-active"><?= e((string)$link['label']) ?></span>
          <?php else: ?><a href="<?= e((string)$link['url']) ?>"><?= e((string)$link['label']) ?></a><?php endif; ?>
        <?php endforeach; ?></div>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
