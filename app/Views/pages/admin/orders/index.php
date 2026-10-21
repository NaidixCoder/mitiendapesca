<?php
$pageTitle='Pedidos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$orders=is_array($orders_prepared??null)?$orders_prepared:[];
$filters=is_array($filters??null)?$filters:[];
$meta=is_array($orders_meta??null)?$orders_meta:[];
$quick=is_array($orders_quick_views??null)?$orders_quick_views:[];
$statusOptions=is_array($status_options_prepared??null)?$status_options_prepared:[];
$paymentOptions=is_array($payment_method_options_prepared??null)?$payment_method_options_prepared:[];
$links=is_array($links??null)?$links:[];
$indexUrl=(string)($links['index']??url('/admin/orders'));
$total=(int)($meta['total']??count($orders));
$page=(int)($meta['page']??1);
$perPage=(int)($meta['per_page']??25);
$pages=(int)($meta['pages']??1);
$start=$total?($page-1)*$perPage+1:0;
$end=min($total,$page*$perPage);
$advancedActive=(bool)($orders_advanced_active??false);
$activeFilters=is_array($orders_active_filters??null)?$orders_active_filters:[];
$pagination=is_array($orders_pagination??null)?$orders_pagination:[];
?>
<section class="admin-surface">
  <?php
  $adminHeaderTitle='Pedidos';
  $adminHeaderDescription='';
  $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Pedidos']];
  $adminHeaderActions='';
  require BASE_PATH.'/app/Views/partials/admin/page_header.php';
  ?>

  <main class="av3-orders-index" aria-label="Administración de pedidos">
    <section class="av3-orders-board">
      <header class="av3-orders-board__head">
        <div class="av3-orders-board__heading">
          <h2>Listado de pedidos</h2>
          <p>Consultá el estado y abrí cada pedido para gestionarlo.</p>
        </div>
        <span class="av3-orders-board__total"><?= $total ?> <?= $total===1?'resultado':'resultados' ?></span>
      </header>

      <nav class="av3-orders-quick" aria-label="Vistas de pedidos">
        <?php foreach($quick as $tab): ?>
          <a class="av3-orders-quick__tab<?= !empty($tab['selected'])?' is-active':'' ?>"
             href="<?= e((string)$tab['href']) ?>"
             <?= !empty($tab['selected'])?'aria-current="page"':'' ?>>
            <?= e((string)$tab['label']) ?>
            <span class="av3-orders-quick__count"><?= (int)$tab['count'] ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <form class="av3-orders-filters" method="get" action="<?= e($indexUrl) ?>">
        <input type="hidden" name="view" value="<?= e((string)($filters['view']??'all')) ?>">
        <div class="av3-orders-toolbar">
          <div class="av3-orders-search">
            <label for="orders-search">Buscar pedidos</label>
            <div class="av3-orders-search__field">
              <span aria-hidden="true" class="av3-orders-search__icon">⌕</span>
              <input id="orders-search" name="q" type="search"
                value="<?= e((string)($filters['q']??'')) ?>"
                placeholder="N.º de pedido, cliente o correo"
                autocomplete="off">
            </div>
          </div>
          <div class="av3-orders-sort">
            <label for="orders-sort">Ordenar</label>
            <select id="orders-sort" name="sort" data-orders-auto-submit>
              <option value="newest" <?= ($filters['sort']??'newest')==='newest'?'selected':'' ?>>Más recientes</option>
              <option value="oldest" <?= ($filters['sort']??'')==='oldest'?'selected':'' ?>>Más antiguos</option>
              <option value="total_desc" <?= ($filters['sort']??'')==='total_desc'?'selected':'' ?>>Mayor importe</option>
            </select>
          </div>
          <button class="av3-orders-search__submit" type="submit">Buscar</button>
        </div>

        <details class="av3-orders-advanced" <?= $advancedActive?'open':'' ?>>
          <summary><span aria-hidden="true">☷</span> Filtros avanzados <?= $advancedActive?'<span class="av3-orders-filter-dot" aria-label="Filtros activos"></span>':'' ?></summary>
          <div class="av3-orders-advanced__body">
            <div class="av3-orders-advanced__grid">
              <div class="av3-orders-field">
                <label for="orders-status">Estado del pedido</label>
                <select id="orders-status" name="status">
                  <option value="">Todos</option>
                  <?php foreach($statusOptions as $item): ?>
                    <option value="<?= e((string)$item['value']) ?>" <?= !empty($item['selected'])?'selected':'' ?>><?= e((string)$item['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="av3-orders-field">
                <label for="orders-payment-method">Método de pago</label>
                <select id="orders-payment-method" name="payment_method">
                  <option value="">Todos</option>
                  <?php foreach($paymentOptions as $item): ?>
                    <option value="<?= e((string)$item['value']) ?>" <?= !empty($item['selected'])?'selected':'' ?>><?= e((string)$item['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="av3-orders-field">
                <label for="orders-from">Desde</label>
                <input type="date" id="orders-from" name="from" value="<?= e((string)($filters['from']??'')) ?>">
              </div>
              <div class="av3-orders-field">
                <label for="orders-to">Hasta</label>
                <input type="date" id="orders-to" name="to" value="<?= e((string)($filters['to']??'')) ?>">
              </div>
              <div class="av3-orders-field">
                <label for="orders-page-size">Por página</label>
                <select id="orders-page-size" name="per_page">
                  <option value="25" <?= $perPage===25?'selected':'' ?>>25 pedidos</option>
                  <option value="50" <?= $perPage===50?'selected':'' ?>>50 pedidos</option>
                </select>
              </div>
            </div>
            <div class="av3-orders-advanced__actions">
              <button type="submit" class="av3-orders-btn av3-orders-btn--primary">Aplicar filtros</button>
              <a class="av3-orders-btn av3-orders-btn--light" href="<?= e($indexUrl) ?>">Limpiar todo</a>
            </div>
          </div>
        </details>
      </form>

      <?php if($activeFilters): ?>
        <div class="av3-orders-applied" aria-label="Filtros aplicados">
          <span>Filtros activos:</span>
          <?php foreach($activeFilters as $chip): ?>
            <a href="<?= e((string)$chip['href']) ?>"><?= e((string)$chip['label']) ?> <span aria-hidden="true">×</span></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if(!$orders): ?>
        <div class="av3-orders-empty">
          <span aria-hidden="true">⌕</span>
          <h3>No se encontraron pedidos</h3>
          <p>Probá con otros términos o quitá los filtros.</p>
          <a class="av3-orders-btn av3-orders-btn--light" href="<?= e($indexUrl) ?>">Ver todos los pedidos</a>
        </div>
      <?php else: ?>
        <div class="av3-orders-table-view">
          <table data-orders-index>
            <thead>
              <tr>
                <th scope="col">Pedido</th>
                <th scope="col">Cliente</th>
                <th scope="col">Estado</th>
                <th scope="col">Pago</th>
                <th scope="col" class="av3-orders-col-delivery">Entrega</th>
                <th scope="col" class="av3-orders-money">Total</th>
                <th scope="col" aria-label="Ver detalle"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($orders as $o): ?>
                <tr data-href="<?= e((string)$o['href']) ?>">
                  <td>
                    <a class="av3-orders-id" href="<?= e((string)$o['href']) ?>">#<?= (int)$o['id'] ?></a>
                    <small><?= e((string)$o['date_label']) ?> · <?= e((string)$o['time_label']) ?></small>
                  </td>
                  <td class="av3-orders-customer"><?= e((string)$o['customer']) ?></td>
                  <td><span class="av3-orders-status av3-orders-status--<?= e((string)$o['status_tone']) ?>"><?= e((string)$o['status_label']) ?></span></td>
                  <td>
                    <span class="av3-orders-status av3-orders-status--<?= e((string)$o['payment_tone']) ?>"><?= e((string)$o['payment_label']) ?></span>
                    <small><?= e((string)$o['payment_method_label']) ?></small>
                  </td>
                  <td class="av3-orders-col-delivery"><span class="av3-orders-status av3-orders-status--<?= e((string)$o['delivery_tone']) ?>"><?= e((string)$o['delivery_label']) ?></span></td>
                  <td class="av3-orders-money"><strong><?= e((string)$o['total_fmt']) ?></strong></td>
                  <td class="av3-orders-row-action"><a href="<?= e((string)$o['href']) ?>" aria-label="Ver pedido #<?= (int)$o['id'] ?>">›</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="av3-orders-mobile-list" aria-label="Listado de pedidos para móvil">
          <?php foreach($orders as $o): ?>
            <a class="av3-orders-mobile-item" href="<?= e((string)$o['href']) ?>" aria-label="Ver pedido #<?= (int)$o['id'] ?> de <?= e((string)$o['customer']) ?>">
              <div class="av3-orders-mobile-item__top">
                <strong>Pedido #<?= (int)$o['id'] ?></strong>
                <strong class="av3-orders-money"><?= e((string)$o['total_fmt']) ?></strong>
              </div>
              <div class="av3-orders-mobile-item__middle">
                <span><?= e((string)$o['customer']) ?></span>
                <time><?= e((string)$o['date_label']) ?></time>
              </div>
              <div class="av3-orders-mobile-item__bottom">
                <span class="av3-orders-status av3-orders-status--<?= e((string)$o['status_tone']) ?>"><?= e((string)$o['status_label']) ?></span>
                <span class="av3-orders-mobile-item__payment">Pago: <?= e((string)$o['payment_label']) ?> · <?= e((string)$o['payment_method_label']) ?></span>
                <span aria-hidden="true" class="av3-orders-mobile-item__arrow">›</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <footer class="av3-orders-pagination">
        <p><?= $total===0?'Sin pedidos':$start.'–'.$end.' de '.$total.' pedidos' ?></p>
        <nav aria-label="Páginas de pedidos">
          <?php if(!empty($pagination['prev_url'])): ?>
            <a href="<?= e((string)$pagination['prev_url']) ?>">‹ Anterior</a>
          <?php else: ?>
            <span aria-disabled="true">‹ Anterior</span>
          <?php endif; ?>
          <span>Página <?= $page ?> de <?= $pages ?></span>
          <?php if(!empty($pagination['next_url'])): ?>
            <a href="<?= e((string)$pagination['next_url']) ?>">Siguiente ›</a>
          <?php else: ?>
            <span aria-disabled="true">Siguiente ›</span>
          <?php endif; ?>
        </nav>
      </footer>
    </section>
  </main>
</section>
