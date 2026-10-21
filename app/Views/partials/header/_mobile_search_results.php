<?php
$resultsItems = is_array($resultsItems ?? null) ? $resultsItems : [];
$resultsQuery = trim((string)($resultsQuery ?? ''));
?>

<?php foreach ($resultsItems as $it): ?>
  <?php
    $slug = trim((string)($it['slug'] ?? ''));
    $name = trim((string)($it['name'] ?? ''));
    if ($slug === '' || $name === '') continue;
    $brand = trim((string)($it['brand_name'] ?? ''));
    $category = trim((string)($it['category_name'] ?? ''));
    $meta = implode(' · ', array_values(array_filter([$brand, $category], static fn($v) => $v !== '')));
    $price = (int)($it['price'] ?? 0);
  ?>
  <a class="ph-search-result-item" href="<?= e(url('/producto?slug=' . rawurlencode($slug))) ?>">
    <div class="ph-block-result">
      <img class="ph-search-result-thumb" src="<?= e(product_cover_url($it)) ?>" alt="" loading="lazy" decoding="async">
      <div class="ph-search-result-main">
        <div class="ph-search-result-title"><?= e($name) ?></div>
        <?php if ($meta !== ''): ?>
          <div class="ph-search-result-meta"><?= e($meta) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($price > 0): ?>
      <div class="ph-search-result-price">$ <?= e(number_format($price, 0, ',', '.')) ?></div>
    <?php endif; ?>
  </a>
<?php endforeach; ?>

<?php if ($resultsQuery !== ''): ?>
  <a class="ph-search-result-item ph-search-result-more" href="<?= e(url('/productos?q=' . rawurlencode($resultsQuery))) ?>">
    Ver todos los resultados para "<?= e($resultsQuery) ?>"
  </a>
<?php endif; ?>
