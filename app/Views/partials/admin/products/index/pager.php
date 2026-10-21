<?php
/** @var array<int,array<string,mixed>> $pager_links */
/** @var int $pages */
?>
<?php if ($pages > 1): ?>
  <nav class="av3-pagination">
    <?php foreach ($pager_links as $link): ?>
      <a href="<?= e((string)($link['url'] ?? '#')) ?>" class="<?= !empty($link['active']) ? 'is-active' : '' ?>"><?= (int)($link['page'] ?? 1) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>
