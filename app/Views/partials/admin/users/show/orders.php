<?php
$ord=is_array($show['orders']??null)?$show['orders']:[];
$rowsOrders=is_array($ord['rows']??null)?$ord['rows']:[];
$pg=is_array($show['pagination']??null)?$show['pagination']:[];
?>
<section class="av3-ui-card">
 <div class="av3-ui-card-header"><h2 class="av3-ui-card-title">Historial de pedidos</h2><span class="av3-users__meta"><?= (int)($pg['total']??0) ?> pedidos</span></div>
 <div class="av3-ui-card-body av3-users__table-wrap">
 <?php if (!$rowsOrders): ?><p class="av3-ui-empty">Este usuario todavía no tiene pedidos.</p>
 <?php else: ?>
  <table class="av3-ui-table av3-users__table">
   <thead><tr><th>Pedido</th><th>Fecha</th><th>Estado</th><th>Medio de pago</th><th>Total</th></tr></thead>
   <tbody>
    <?php foreach ($rowsOrders as $o): ?>
    <tr>
     <td data-label="Pedido"><a href="<?= e((string)($o['href']??'#')) ?>">#<?= (int)($o['id']??0) ?></a></td>
     <td data-label="Fecha"><?= e((string)($o['date']??'')) ?></td>
     <td data-label="Estado"><?= e((string)($o['status_label']??'')) ?></td>
     <td data-label="Pago"><?= e((string)(($o['payment_method']??'')?:'No informado')) ?></td>
     <td data-label="Total"><?= e((string)($o['total_fmt']??money(0))) ?></td>
    </tr>
    <?php endforeach; ?>
   </tbody>
  </table>
 <?php endif; ?>
 </div>
 <?php if (($pg['pages']??1)>1): ?>
 <nav class="av3-users__pagination" aria-label="Paginación de pedidos">
  <span class="av3-users__meta">Página <?= (int)$pg['page'] ?> de <?= (int)$pg['pages'] ?></span>
  <div class="av3-ui-actions">
   <?php if (empty($pg['prev_disabled'])): ?><a class="av3-ui-btn" href="<?= e((string)$pg['prev_href']) ?>">Anterior</a><?php endif; ?>
   <?php if (empty($pg['next_disabled'])): ?><a class="av3-ui-btn" href="<?= e((string)$pg['next_href']) ?>">Siguiente</a><?php endif; ?>
  </div>
 </nav>
 <?php endif; ?>
</section>
