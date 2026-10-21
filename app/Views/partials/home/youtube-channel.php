<?php
$youtube = is_array($youtube ?? null) ? $youtube : [];
$channel = is_array($youtube['channel'] ?? null) ? $youtube['channel'] : [];
$featured = is_array($youtube['featured_video'] ?? null) ? $youtube['featured_video'] : [];
$usedProducts = is_array($youtube['used_products'] ?? null) ? $youtube['used_products'] : [];

$channelTitle = trim((string)($channel['title'] ?? 'Canal de YouTube'));
$channelUrl = trim((string)($channel['url'] ?? ''));
$channelThumb = trim((string)($channel['thumbnail'] ?? ''));
$channelFallbackThumb = trim((string)($channel['fallback_thumbnail'] ?? ''));
$subscribers = (int)($channel['subscriber_count'] ?? 0);
$videoCount = (int)($channel['video_count'] ?? 0);
$featuredTitle = trim((string)($featured['title'] ?? ''));
$embedUrl = trim((string)($featured['embed_url'] ?? ''));
$videoUrl = trim((string)($featured['url'] ?? ''));
$hasVideo = $embedUrl !== '';

?>
<section class="home-sec home-youtube" aria-label="Canal de YouTube">
  <div class="home-sec__container">
    <article class="home-youtube__panel">
      <header class="home-youtube__head">
        <div class="home-youtube__channel">
          <?php if ($channelUrl !== ''): ?>
            <a href="<?= e($channelUrl) ?>" target="_blank" rel="noopener" class="home-youtube__channel-link" aria-label="<?= e($channelTitle) ?>">
          <?php endif; ?>
            <span class="home-youtube__avatar-shell" aria-hidden="true">
              <?php if ($channelThumb !== ''): ?>
                <img
                  src="<?= e($channelThumb) ?>"
                  alt="<?= e($channelTitle) ?>"
                  class="home-youtube__avatar"
                  loading="lazy"
                  decoding="async"
                  onerror="if(this.dataset.fallbackSrc){this.onerror=null;this.src=this.dataset.fallbackSrc;this.dataset.fallbackSrc='';return;}this.hidden=true;this.parentNode.classList.add('is-fallback');"
                  <?= $channelFallbackThumb !== '' ? 'data-fallback-src="' . e($channelFallbackThumb) . '"' : '' ?>
                >
              <?php elseif ($channelFallbackThumb !== ''): ?>
                <img
                  src="<?= e($channelFallbackThumb) ?>"
                  alt="<?= e($channelTitle) ?>"
                  class="home-youtube__avatar"
                  loading="lazy"
                  decoding="async"
                  onerror="this.hidden=true;this.parentNode.classList.add('is-fallback');"
                >
              <?php endif; ?>
              <span class="home-youtube__avatar-fallback<?= ($channelThumb ==='' && $channelFallbackThumb === '') ? ' is-visible' : '' ?>">
                <?= ui_icon('youtube-brand') ?>
              </span>
            </span>
            <div class="home-youtube__channel-info">
              <h3 class="home-youtube__title"><?= e($channelTitle) ?></h3>
              <p class="home-youtube__subtitle">Tecnicas, reviews y salidas de pesca.</p>
            </div>
          <?php if ($channelUrl !== ''): ?>
            </a>
          <?php endif; ?>
        </div>
      </header>

      <div class="home-youtube__layout">
        <?php if ($hasVideo): ?>
          <div class="home-youtube__video">
            <iframe
              class="home-youtube__video-frame"
              src="<?= e($embedUrl) ?>"
              title="<?= e($featuredTitle !== '' ? $featuredTitle : 'Video destacado') ?>"
              loading="lazy"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              referrerpolicy="strict-origin-when-cross-origin"
              allowfullscreen
            ></iframe>
          </div>
        <?php else: ?>
          <div class="home-youtube__video home-youtube__video--empty">
            <p class="home-youtube__empty-text">No hay video destacado disponible por el momento.</p>
          </div>
        <?php endif; ?>

        <aside class="home-youtube__sidebar">
          <div class="home-youtube__stats">
            <?php if ($videoCount > 0): ?>
              <div class="home-youtube__stat home-youtube__stat--primary">
                <span class="home-youtube__stat-value">+<?= number_format($videoCount, 0, ',', '.') ?></span>
                <span class="home-youtube__stat-label">videos publicados</span>
              </div>
            <?php endif; ?>
            <div class="home-youtube__stat">
              <span class="home-youtube__stat-value"><?= number_format($subscribers, 0, ',', '.') ?></span>
              <span class="home-youtube__stat-label">suscriptores</span>
            </div>
          </div>

          <p class="home-youtube__copy">Salidas, tecnicas y pruebas reales de pesca para una comunidad que sigue creciendo.</p>

          <div class="home-youtube__actions">
            <?php if ($channelUrl !== ''): ?>
              <a
                href="<?= e($channelUrl) ?>?sub_confirmation=1"
                target="_blank"
                rel="noopener"
                class="home-youtube__btn"
              >
                <span class="home-youtube__btn-icon" aria-hidden="true">
                  <?= ui_icon('youtube') ?>
                </span>
                <span>Suscribirme en YouTube</span>
              </a>
            <?php elseif ($videoUrl !== ''): ?>
              <a
                href="<?= e($videoUrl) ?>"
                target="_blank"
                rel="noopener"
                class="home-youtube__btn"
              >
                <span class="home-youtube__btn-icon" aria-hidden="true">
                  <?= ui_icon('youtube') ?>
                </span>
                <span>Ver en YouTube</span>
              </a>
            <?php endif; ?>
          </div>
        </aside>
      </div>

      <div class="home-youtube-products">
        <h4 class="home-youtube-products__title">Productos usados en el video</h4>
        <?php if ($usedProducts !== []): ?>
          <div class="home-youtube-products__carousel" data-home-carousel data-home-carousel-mobile-only="1" data-home-carousel-interval="3200">
            <div class="home-youtube-products__grid home-youtube-products__track" data-home-carousel-track>
            <?php foreach ($usedProducts as $idx => $p): ?>
              <?php
                $card = is_array($p['card'] ?? null) ? $p['card'] : [];
                if ($card === []) {
                  continue;
                }
              ?>
              <article class="home-youtube-products__slide <?= $idx === 0 ?'is-active' : '' ?>" data-home-carousel-slide>
                <?php section('partials/catalogo/product-card', ['product' => $card]); ?>
              </article>
            <?php endforeach; ?>
            </div>

            <div class="home-youtube-products__controls">
              <button class="home-youtube-products__control home-youtube-products__control--prev" type="button" data-home-carousel-prev aria-label="Producto anterior">
                <span aria-hidden="true"></span>
              </button>
              <button class="home-youtube-products__control home-youtube-products__control--next" type="button" data-home-carousel-next aria-label="Producto siguiente">
                <span aria-hidden="true"></span>
              </button>
            </div>

            <div class="home-youtube-products__dots" role="tablist" aria-label="Selector de productos">
              <?php foreach ($usedProducts as $idx => $_): ?>
                <button
                  class="home-youtube-products__dot <?= $idx === 0 ?'is-active' : '' ?>"
                  type="button"
                  data-home-carousel-dot="<?= (int)$idx ?>"
                  aria-label="Ver producto <?= (int)$idx + 1 ?>"
                  aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"
                ></button>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <p class="home-youtube__empty-text">No hay productos vinculados para este video.</p>
        <?php endif; ?>
      </div>
    </article>
  </div>
</section>
