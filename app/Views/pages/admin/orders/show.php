<?php
$pageTitle = 'Pedido';

$showView = is_array($show_view ?? null) ? $show_view : [];

extract($showView, EXTR_SKIP);

require BASE_PATH . '/app/Views/partials/admin/subnav.php';

if (!$orderView) {
  require BASE_PATH . '/app/Views/partials/admin/orders/show/not_found.php';
  return;
}
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php
$adminHeaderTitle='Pedido #'.(int)$orderView['id'];
$adminHeaderDescription='Detalle operativo, pago, facturación y trazabilidad.';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Pedidos','href'=>url('/admin/orders')],['label'=>'Pedido #'.(int)$orderView['id']]];
$adminHeaderActions='<a class="btn-secondary" href="'.e((string)$orderView['index_url']).'">Volver a pedidos</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>

    <?php require BASE_PATH . '/app/Views/partials/admin/orders/show/order_header_card.php'; ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/orders/show/fiscal_request.php'; ?>

    <?php require BASE_PATH . '/app/Views/partials/admin/orders/show/payment.php'; ?>

    <div id="arca-box">
      <?php section('admin/orders/arca_box', [
        'orderView' => $orderView,
        'comprobante' => $comprobante,
        'creditNote' => $creditNote,
        'comprobantesHasKind' => (bool)$comprobantesHasKind,
        'comprobantes' => $comprobantes,
      ]); ?>
    </div>

    <?php section('admin/confirm_modal'); ?>
    <?php section('admin/processing_overlay'); ?>

    <?php require BASE_PATH . '/app/Views/partials/admin/orders/show/delivery.php'; ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/orders/show/shipping.php'; ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/orders/show/items.php'; ?>

  </div>
</section>
