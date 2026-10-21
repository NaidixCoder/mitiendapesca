<?php
$adminHeaderTitle = (string)($campaign['name'] ?? 'Campaña');
$adminHeaderBreadcrumbs = [
  ['label'=>'Inicio','href'=>url('/admin')],
  ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
  ['label'=>$adminHeaderTitle],
];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e((string)($links['export_csv'] ?? '')).'">Exportar CSV</a>'
 .'<a class="av3-ui-btn av3-ui-btn--primary" href="'.e((string)($links['edit'] ?? '')).'">Editar campaña</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<div class="av3-marketing-detail__identity">
  <span class="av3-marketing-status<?= !empty($campaign['is_active']) ? ' is-active' : '' ?>"><?= e((string)($campaign['active_label'] ?? 'Inactiva')) ?></span>
  <code><?= e((string)($campaign['promo_path'] ?? '')) ?></code>
</div>
