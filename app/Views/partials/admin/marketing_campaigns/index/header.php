<?php
$adminHeaderTitle = 'Marketing';
$adminHeaderBreadcrumbs = $crumbs ?: [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Marketing']];
$exportQuery = ['days' => (int)$days];
if ($q !== '') $exportQuery['q'] = $q;
if ($status !== '') $exportQuery['status'] = $status;
$adminHeaderActions =
    '<a class="av3-ui-btn" href="'.e(url('/admin/marketing/campanas/export?'.http_build_query($exportQuery))).'">Exportar CSV</a>'
    .'<a class="av3-ui-btn av3-ui-btn--primary" href="'.e(url('/admin/marketing/campanas/new')).'">Nueva campaña</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
