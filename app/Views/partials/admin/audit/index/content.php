<?php
$pageTitle = 'Auditoria';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

// ViewModel preparado por AuditViewPresenterService.
$tipo = (string)($tipo ?? 'todos');
$action = (string)($action ?? '');
$range = (string)($range ?? '7');
$q = (string)($q ?? '');
$actor = (string)($actor ?? '');
$entity_id = (string)($entity_id ?? '');
$from = (string)($from ?? '');
$to = (string)($to ?? '');
$page = max(1, (int)($page ?? 1));
$perPage = (int)($perPage ?? 25);
$total = (int)($total ?? 0);
$pages = (int)($pages ?? 1);
$fromRow = (int)($fromRow ?? 0);
$toRow = (int)($toRow ?? 0);
$actionGroups = is_array($actionGroups ?? null) ? $actionGroups : [];
$rangeOptions = is_array($rangeOptions ?? null) ? $rangeOptions : [];
$hasAdvancedFilters = (bool)($hasAdvancedFilters ?? false);
$itemsPrepared = is_array($items_prepared ?? null) ? $items_prepared : [];
$pager = is_array($pager ?? null) ? $pager : ['prev_disabled' => true, 'next_disabled' => true, 'prev_url' => '#', 'next_url' => '#'];
?>
<section class="admin-surface">
  <div class="av3-wrap">
    <?php
    $adminHeaderTitle='Auditoría';
    $adminHeaderDescription='Registro de acciones administrativas y del sistema.';
    $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Auditoría']];
    require BASE_PATH.'/app/Views/partials/admin/page_header.php';
    ?>
    <div class="audit-wrap audit-min">
      <?php require BASE_PATH.'/app/Views/partials/admin/audit/index/hero.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/audit/index/feed.php'; ?>
    </div>
  </div>
</section>
