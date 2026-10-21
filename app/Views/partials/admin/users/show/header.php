<?php
$u = is_array($show['user'] ?? null) ? $show['user'] : [];
$links = is_array($show['links'] ?? null) ? $show['links'] : [];
$uid = (int)($u['id'] ?? 0);
$name = trim((string)($u['name'] ?? ''));
$email = (string)($u['email'] ?? '');
$createdAtShort = (string)($u['created_at_short'] ?? '');
?>
<?php
ob_start(); ?>Cliente #<?= $uid ?><?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
      <?= $name !== '' ? e($name).' - ' : '' ?>
      <?= e($email) ?>
      <?php if ($createdAtShort !== ''): ?>
        - Alta: <?= e($createdAtShort) ?>
      <?php endif; ?>
    <?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
    <a class="btn btn-soft" href="<?= e((string)($links['users_index'] ?? url('/admin/users'))) ?>">Volver</a>
    <a class="btn" href="<?= e((string)($links['rebuild_stats'] ?? url('/admin/users/show?id='.(int)$uid.'&rebuild=1'))) ?>">Recalcular stats</a>
  <?php $adminHeaderActions=ob_get_clean();
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Usuarios','href'=>url('/admin/users')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
