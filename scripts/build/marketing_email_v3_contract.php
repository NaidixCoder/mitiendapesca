<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$must = [
 'app/Views/pages/admin/marketing_email/index.php' => ['data-email-summary','marketing_email_nav.php','Suscriptores','Cola de envío','Nuevo envío'],
 'app/Views/pages/admin/marketing_email/subscribers.php' => ['data-email-subscribers','av3-email-table','av3-email-mobile-list','name="status"','name="per"','pagination','marketing_email_subscriber_action.php'],
 'app/Views/pages/admin/marketing_email/automations.php' => ['data-email-automations','av3-email-steps','toggle_automation','toggle_step','csrf_field()'],
 'app/Views/partials/admin/marketing_email_nav.php' => ['av3-email-nav','Suscriptores','Automatizaciones','Envíos','Cola','Plantillas'],
 'app/Repositories/Marketing/EmailSubscriberRepository.php' => ['countForAdmin','OFFSET'],
 'app/Controllers/Admin/EmailMarketingController.php' => ['countForAdmin($q, $status)','listForAdmin($q, $status, $per'],
 'public_html/assets/css/admin-v3/pages/marketing-email.css' => ['container-type:inline-size','@container (min-width:600px)','av3-email-mobile-item','av3-email-automation-list'],
 'app/Views/partials/head.php' => ['css/admin-v3/pages/marketing-email.css'],
];
foreach ($must as $path=>$terms) {
 $text = @file_get_contents($root.'/'.$path);
 if (!is_string($text)) throw new RuntimeException('Archivo faltante: '.$path);
 foreach($terms as $term) if(!str_contains($text,$term)) throw new RuntimeException($path.': falta '.$term);
}
foreach (['index','subscribers','automations'] as $page) {
 $text = (string)file_get_contents($root.'/app/Views/pages/admin/marketing_email/'.$page.'.php');
 if (str_contains($text,'class="btn') || str_contains($text,'max-h-[')) throw new RuntimeException('Legacy presente en '.$page);
}
echo "[email-v3-contract] OK: resumen, suscriptores paginados, automatizaciones y CSS mobile-first\n";
