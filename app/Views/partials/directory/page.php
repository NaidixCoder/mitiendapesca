<?php
$title = (string)($title ?? '');
$lead = (string)($lead ?? '');
$allProductsUrl = (string)($allProductsUrl ?? url('/productos'));
$allProductsLabel = (string)($allProductsLabel ?? 'Ver todos los productos');
$emptyTitle = (string)($emptyTitle ?? 'Todavia no hay elementos cargados.');
$emptyCopy = (string)($emptyCopy ?? 'Volve mas tarde para explorar esta seccion.');
$items = is_array($items ?? null) ? $items : [];
$related = is_array($related ?? null) ? $related : [];
$cardVariant = (string)($cardVariant ?? '');
?>

<section class="directory-page">
  <div class="directory-page__inner">
    <header class="directory-page__hero">
      <h1 class="directory-page__title"><?= e($title) ?></h1>
      <p class="directory-page__lead"><?= e($lead) ?></p>
      <a class="directory-page__all-link" href="<?= e($allProductsUrl) ?>"><?= e($allProductsLabel) ?></a>
    </header>

    <?php if ($items === []): ?>
      <div class="directory-page__empty">
        <p class="directory-page__empty-title"><?= e($emptyTitle) ?></p>
        <p class="directory-page__empty-copy"><?= e($emptyCopy) ?></p>
      </div>
    <?php else: ?>
      <div class="directory-page__grid">
        <?php foreach ($items as $item): ?>
          <?php
            $name = trim((string)($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $href = (string)($item['href'] ?? '#');
            $count = max(0, (int)($item['count'] ?? 0));
            $imageUrl = trim((string)($item['image_url'] ?? ''));
            $countLabel = $count === 1 ? '1 producto' : ($count . ' productos');
            $isEmpty = $count <= 0;
            $statusLabel = $isEmpty ? 'Sin productos' : $countLabel;
            $variantClass = $cardVariant !== '' ? (' directory-card--' . preg_replace('/[^a-z0-9_-]+/i', '', $cardVariant)) : '';
          ?>
          <a class="directory-card<?= $variantClass ?><?= $isEmpty ?' is-empty' : '' ?>" href="<?= e($href) ?>">
            <div class="directory-card__media-wrap<?= $variantClass !=='' ? (' directory-card__media-wrap--' . preg_replace('/[^a-z0-9_-]+/i', '', $cardVariant)) : '' ?>">
              <img
                class="directory-card__media<?= $variantClass !=='' ? (' directory-card__media--' . preg_replace('/[^a-z0-9_-]+/i', '', $cardVariant)) : '' ?>"
                src="<?= e($imageUrl !== '' ? $imageUrl : asset('img/placeholder.jpg')) ?>"
                alt="<?= e($name) ?>"
                loading="lazy"
              >
              <?php if ($isEmpty): ?>
                <span class="directory-card__empty-pill">Sin productos</span>
              <?php endif; ?>
            </div>

            <div class="directory-card__body">
              <h2 class="directory-card__title"><?= e($name) ?></h2>
              <span class="directory-card__meta"><?= e($statusLabel) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($related !== []): ?>
      <div class="directory-page__related">
        <?php section('partials/components/related-products', ['related' => $related]); ?>
      </div>
    <?php endif; ?>
  </div>
</section>
