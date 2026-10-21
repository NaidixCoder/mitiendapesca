<?php
/** @var array<int,array<string,mixed>> $pagination */
$pagination = is_array($pagination ?? null) ? $pagination : [];
?>
<?php if ($pagination): ?>
<nav class="av3-email-pagination" aria-label="Páginas de resultados">
  <span><?= e((string)($range_label ?? '')) ?></span>
  <div>
    <?php foreach ($pagination as $link): ?>
      <?php if (!empty($link['disabled'])): ?><span class="is-disabled"><?= e((string)$link['label']) ?></span>
      <?php elseif (!empty($link['active'])): ?><span class="is-active" aria-current="page"><?= e((string)$link['label']) ?></span>
      <?php else: ?><a href="<?= e((string)$link['url']) ?>"><?= e((string)$link['label']) ?></a><?php endif; ?>
    <?php endforeach; ?>
  </div>
</nav>
<?php endif; ?>
