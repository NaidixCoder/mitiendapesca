<?php
$pageTitle = 'Suscriptores';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$q = (string)($q ?? '');
$status = (string)($status ?? '');
$rows = is_array($rows_prepared ?? null) ? $rows_prepared : [];
$statusOptions = is_array($status_options ?? null) ? $status_options : [];
$links = is_array($links ?? null) ? $links : [];
$pagination = is_array($pagination ?? null) ? $pagination : [];
$adminHeaderTitle = 'Suscriptores';
$adminHeaderBreadcrumbs = [
 ['label'=>'Inicio','href'=>url('/admin')],
 ['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],
 ['label'=>'Email marketing','href'=>url('/admin/marketing/email')],
 ['label'=>'Suscriptores'],
];
?>
<section class="admin-surface">
 <div class="av3-wrap av3-marketing-email" data-email-subscribers>
  <?php require BASE_PATH.'/app/Views/partials/admin/page_header.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
  <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_nav.php'; ?>
  <?php if ($m=flash('ok')): ?><div class="av3-alert ok" role="status"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m=flash('error')): ?><div class="av3-alert error" role="alert"><?= e($m) ?></div><?php endif; ?>
  <section class="av3-email-panel av3-email-filters" aria-label="Buscar suscriptores">
    <form method="get" action="<?= e((string)($links['index'] ?? url('/admin/marketing/email/suscriptores'))) ?>">
      <div class="av3-email-field av3-email-field--search"><label for="email-subscriber-query">Buscar email</label><input id="email-subscriber-query" type="search" name="q" value="<?= e($q) ?>" placeholder="Correo electrónico"></div>
      <div class="av3-email-field"><label for="email-subscriber-status">Estado</label><select id="email-subscriber-status" name="status">
        <?php foreach ($statusOptions as $opt): ?><option value="<?= e((string)$opt['value']) ?>"<?= !empty($opt['selected']) ? ' selected' : '' ?>><?= e((string)$opt['label']) ?></option><?php endforeach; ?>
      </select></div>
      <div class="av3-email-field av3-email-field--per"><label for="email-subscriber-per">Por página</label><select id="email-subscriber-per" name="per">
        <?php foreach ([25,50] as $n): ?><option value="<?= $n ?>"<?= (int)($per ?? 25) === $n ? ' selected' : '' ?>><?= $n ?></option><?php endforeach; ?>
      </select></div>
      <button type="submit" class="av3-ui-btn av3-ui-btn--primary">Buscar</button>
      <a class="av3-email-textlink" href="<?= e((string)($links['index'] ?? url('/admin/marketing/email/suscriptores'))) ?>">Limpiar</a>
    </form>
  </section>
  <section class="av3-email-panel" aria-labelledby="email-subscriber-results-title">
    <header class="av3-email-panel__header">
      <div><h2 id="email-subscriber-results-title">Listado de contactos</h2><p><?= e((string)($range_label ?? '')) ?></p></div>
      <details class="av3-email-help-inline">
        <summary>Estados</summary>
        <p><strong>Suscripto:</strong> recibe emails. <strong>Pendiente:</strong> falta confirmar. <strong>Baja:</strong> solicitó dejar de recibirlos.</p>
      </details>
    </header>
    <?php if (!$rows): ?>
      <div class="av3-email-empty"><h3>No se encontraron suscriptores</h3><p>Probá con otra búsqueda o seleccioná todos los estados.</p></div>
    <?php else: ?>
      <div class="av3-email-table-wrap">
        <table class="av3-email-table">
          <thead><tr><th scope="col">Email</th><th scope="col">Estado</th><th scope="col">Alta</th><th scope="col">Confirmación</th><th scope="col" class="av3-email-right">Acción</th></tr></thead>
          <tbody><?php foreach ($rows as $r): ?>
            <tr>
              <td class="av3-email-address"><?= e((string)$r['email']) ?></td>
              <td><span class="av3-email-status av3-email-status--<?= e((string)($r['status_badge'] ?? 'ok')) ?>"><?= e((string)$r['status_label']) ?></span></td>
              <td><?= e((string)$r['created_at']) ?></td>
              <td><?= e((string)$r['confirmed_at']) ?></td>
              <td class="av3-email-right"><?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_subscriber_action.php'; ?></td>
            </tr>
          <?php endforeach; ?></tbody>
        </table>
      </div>
      <div class="av3-email-mobile-list">
        <?php foreach ($rows as $r): ?>
          <article class="av3-email-mobile-item">
            <div class="av3-email-mobile-item__top"><strong><?= e((string)$r['email']) ?></strong><span class="av3-email-status av3-email-status--<?= e((string)($r['status_badge'] ?? 'ok')) ?>"><?= e((string)$r['status_label']) ?></span></div>
            <div class="av3-email-mobile-item__meta">Alta: <?= e((string)$r['created_at']) ?><?= $r['confirmed_at'] !== '' ? ' · Confirmación: '.e((string)$r['confirmed_at']) : '' ?></div>
            <?php require BASE_PATH.'/app/Views/partials/admin/marketing_email_subscriber_action.php'; ?>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if ($pagination): ?>
        <nav class="av3-email-pagination" aria-label="Páginas de suscriptores">
          <span><?= e((string)$range_label) ?></span>
          <div><?php foreach ($pagination as $link): ?>
            <?php if ($link['disabled']): ?><span class="is-disabled"><?= e((string)$link['label']) ?></span>
            <?php elseif ($link['active']): ?><span class="is-active" aria-current="page"><?= e((string)$link['label']) ?></span>
            <?php else: ?><a href="<?= e((string)$link['url']) ?>"><?= e((string)$link['label']) ?></a><?php endif; ?>
          <?php endforeach; ?></div>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
 </div>
</section>
