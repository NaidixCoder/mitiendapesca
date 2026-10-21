<?php
/** @var array $product */

$short = trim((string)($product['description'] ?? $product['short_desc'] ?? ''));
$long = trim((string)($product['long_desc'] ?? ''));
if ($short === '' && $long === '') {
    return;
}

$shortHtml = $short !== '' ? htmlspecialchars($short) : '';
$longHtml = $long !== '' ? htmlspecialchars($long) : '';
$tokens = [];
$tokensRaw = trim((string)($product['highlight_tokens_json'] ?? ''));
if ($tokensRaw !== '') {
    $decoded = json_decode($tokensRaw, true);
    if (is_array($decoded)) {
        foreach ($decoded as $token) {
            $token = trim((string)$token);
            if ($token !== '') $tokens[] = $token;
        }
    }
}
?>

<section class="product-description">
  <div class="product-description__card">
    <header class="product-description__header">
      <span class="product-description__header-icon" aria-hidden="true">
        <?= ui_icon('document') ?>
      </span>
      <div class="product-description__header-copy">
        <h2 class="product-description__title">Conocé este producto</h2>
        <p class="product-description__subtitle">Informacion clara para comprar con confianza.</p>
      </div>
    </header>

    <?php if ($shortHtml !== ''): ?>
      <article class="product-description__panel">
        <span class="product-description__panel-icon" aria-hidden="true">
          <?= ui_icon('box') ?>
        </span>
        <div class="product-description__panel-copy">
          <h3 class="product-description__section-title">Descripcion</h3>
          <div class="product-description__body"><?= $shortHtml ?></div>
          <?php if ($tokens !== []): ?>
            <div class="product-description__tokens" aria-label="Puntos destacados">
              <?php foreach ($tokens as $token): ?>
                <span class="product-description__token"><?= ui_icon('check-line') ?><?= e($token) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endif; ?>

    <?php if ($longHtml !== ''): ?>
      <article class="product-description__panel">
        <span class="product-description__panel-icon" aria-hidden="true">
          <?= ui_icon('tag-solid') ?>
        </span>
        <div class="product-description__panel-copy">
          <h3 class="product-description__section-title">Especificaciones</h3>
          <div class="product-description__body product-description__body--list"><?= $longHtml ?></div>
        </div>
      </article>
    <?php endif; ?>
  </div>
</section>
