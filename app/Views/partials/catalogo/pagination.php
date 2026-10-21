<?php
if (isset($pager) && is_array($pager)) {
  $total = (int)($pager['total'] ?? 0);
  $perPage = (int)($pager['per_page'] ?? 12);
  $page = (int)($pager['page'] ?? 1);
} else {
  $total = (int)($total ?? 0);
  $perPage = (int)($perPage ?? 12);
  $page = (int)($page ?? 1);
}

$basePath = (string)($basePath ?? '/productos');
$pages = max(1, (int)ceil($total / max(1, $perPage)));
$page = max(1, min($page, $pages));

$qs = function (array $extra = []): string {
  $q = array_merge($_GET ?? [], $extra);
  foreach ($q as $k => $v) {
    if ($v === null || $v === '') {
      unset($q[$k]);
    }
  }
  return $q ? ('?' . http_build_query($q)) : '';
};

$from = ($total > 0) ? (($page - 1) * $perPage + 1) : 0;
$to = ($total > 0) ? min($total, $page * $perPage) : 0;
$window = 2;
$candidates = array_merge([1, $pages], range(max(1, $page - $window), min($pages, $page + $window)));
$candidates = array_values(array_unique($candidates));
sort($candidates);

$firstHref = url($basePath) . $qs(['page' => 1]);
$prevHref = url($basePath) . $qs(['page' => max(1, $page - 1)]);
$nextHref = url($basePath) . $qs(['page' => min($pages, $page + 1)]);
$lastHref = url($basePath) . $qs(['page' => $pages]);
?>

<nav class="catalog-pagination" aria-label="Paginacion">
  <div class="catalog-pagination__summary">
    <div class="catalog-pagination__meta">
      <?php if ($total > 0): ?>
        Mostrando
        <span class="catalog-pagination__meta-strong"><?= $from ?></span>
        <span class="catalog-pagination__meta-divider">-</span>
        <span class="catalog-pagination__meta-strong"><?= $to ?></span>
        <span class="catalog-pagination__meta-divider">de</span>
        <span class="catalog-pagination__meta-strong"><?= $total ?></span>
      <?php else: ?>
        <span class="catalog-pagination__meta-strong">0</span>
      <?php endif; ?>
    </div>

    <div class="catalog-pagination__controls">
      <?php if ($page > 1): ?>
        <a href="<?= e($firstHref) ?>" class="catalog-pagination__icon-btn" aria-label="Primera pagina">
          <?= ui_icon('chevrons-left') ?>
        </a>
      <?php else: ?>
        <span class="catalog-pagination__icon-btn is-disabled" aria-hidden="true">
          <?= ui_icon('chevrons-left') ?>
        </span>
      <?php endif; ?>

      <?php if ($page > 1): ?>
        <a rel="prev" href="<?= e($prevHref) ?>" class="catalog-pagination__icon-btn" aria-label="Pagina anterior">
          <?= ui_icon('chevron-left-solid') ?>
        </a>
      <?php else: ?>
        <span class="catalog-pagination__icon-btn is-disabled" aria-hidden="true">
          <?= ui_icon('chevron-left-solid') ?>
        </span>
      <?php endif; ?>

      <div class="catalog-pagination__pages">
        <?php
        $prevShown = 0;
        foreach ($candidates as $pno):
          if ($prevShown && $pno > $prevShown + 1):
            echo '<span class="catalog-pagination__ellipsis">…</span>';
          endif;
          $isCurrent = ($pno === $page);
          $href = url($basePath) . $qs(['page' => $pno]);
        ?>
          <?php if ($isCurrent): ?>
            <span aria-current="page" class="catalog-pagination__page is-current"><?= $pno ?></span>
          <?php else: ?>
            <a href="<?= e($href) ?>" class="catalog-pagination__page"><?= $pno ?></a>
          <?php endif; ?>
        <?php
          $prevShown = $pno;
        endforeach;
        ?>
      </div>

      <?php if ($page < $pages): ?>
        <a rel="next" href="<?= e($nextHref) ?>" class="catalog-pagination__icon-btn" aria-label="Pagina siguiente">
          <?= ui_icon('chevron-right-solid') ?>
        </a>
      <?php else: ?>
        <span class="catalog-pagination__icon-btn is-disabled" aria-hidden="true">
          <?= ui_icon('chevron-right-solid') ?>
        </span>
      <?php endif; ?>

      <?php if ($page < $pages): ?>
        <a href="<?= e($lastHref) ?>" class="catalog-pagination__icon-btn" aria-label="Ultima pagina">
          <?= ui_icon('chevrons-right') ?>
        </a>
      <?php else: ?>
        <span class="catalog-pagination__icon-btn is-disabled" aria-hidden="true">
          <?= ui_icon('chevrons-right') ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
</nav>
