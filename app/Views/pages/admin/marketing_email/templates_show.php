<?php
$pageId = 'admin-email-templates';
$templateShow = is_array($template_show ?? null) ? $template_show : [];
$pageTitle = (string)($templateShow['name'] ?? 'Plantilla');
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
?>
<section class="admin-surface">
 <div class="av3-wrap av3-marketing-email" data-email-template-detail>
  <?php render_partial('admin/marketing_email/templates_show/header',['template_show'=>$templateShow]); ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <div class="av3-email-template-detail">
    <?php render_partial('admin/marketing_email/templates_show/details',['template_show'=>$templateShow]); ?>
    <?php render_partial('admin/marketing_email/templates_show/preview',['template_show'=>$templateShow]); ?>
  </div>
 </div>
</section>
