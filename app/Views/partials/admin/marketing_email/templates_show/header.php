<?php
/** @var array<string,mixed> $template_show */
$adminHeaderTitle = (string)($template_show['name'] ?? 'Plantilla');
$adminHeaderBreadcrumbs = [
  ['label'=>'Inicio','href'=>url('/admin')],
  ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
  ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
  ['label'=>'Plantillas','href'=>url('/admin/marketing/email/templates')],
  ['label'=>$adminHeaderTitle],
];
$adminHeaderActions = '<a class="av3-ui-btn" href="'.e((string)($template_show['back_url'] ?? url('/admin/marketing/email/templates'))).'">Volver</a>'
 .'<a class="av3-ui-btn av3-ui-btn--primary" href="'.e((string)($template_show['use_url'] ?? '')).'">Usar en campaña</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
