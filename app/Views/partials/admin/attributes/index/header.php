<?php
$adminHeaderTitle = 'Atributos';
$adminHeaderDescription = '';
$adminHeaderBreadcrumbs = [
    ['label' => 'Inicio', 'href' => url('/admin')],
    ['label' => 'Catálogo', 'href' => url('/admin/products')],
    ['label' => 'Atributos'],
];
$adminHeaderActions = '<a data-attr-tab-action="attributes" class="av3-ui-btn av3-ui-btn--primary" href="' . e(url('/admin/attributes/new')) . '"' . ($activeTab !== 'attributes' ? ' hidden' : '') . '>Nuevo atributo</a>'
    . '<a data-attr-tab-action="templates" class="av3-ui-btn" href="' . e(url('/admin/products')) . '"' . ($activeTab !== 'templates' ? ' hidden' : '') . '>Ir a Productos</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
