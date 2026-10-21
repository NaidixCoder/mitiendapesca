<?php
/** @var array{title:string,seoText:string,imageUrl?:string,imageAlt?:string,bullets:array<int,string>,specs:array<int,array{label:string,value:string}>,faq:array<int,array{q:string,a:string}>} $editorial */
$editorialTitle = (string)($editorial['title'] ?? 'Informacion adicional');
$seoText = (string)($editorial['seoText'] ?? '');
$imageUrl = trim((string)($editorial['imageUrl'] ?? ''));
$imageAlt = (string)($editorial['imageAlt'] ?? $editorialTitle);
$bullets = is_array($editorial['bullets'] ?? null) ? $editorial['bullets'] : [];
$specs = is_array($editorial['specs'] ?? null) ? $editorial['specs'] : [];
$faq = is_array($editorial['faq'] ?? null) ? $editorial['faq'] : [];
?>

<section class="catalog-editorial" aria-label="<?= e($editorialTitle) ?>">
  <div class="catalog-editorial__panel">
    <?php if ($imageUrl !== ''): ?>
      <figure class="catalog-editorial__media">
        <img class="catalog-editorial__image" src="<?= e($imageUrl) ?>" alt="<?= e($imageAlt) ?>" loading="lazy">
      </figure>
    <?php endif; ?>

    <div class="catalog-editorial__intro">
      <p class="catalog-editorial__eyebrow">Guia de compra</p>
      <h2 class="catalog-editorial__title"><?= e($editorialTitle) ?></h2>
      <?php if ($seoText !== ''): ?>
        <div class="catalog-editorial__copy">
          <?= nl2br(e($seoText)) ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($bullets !== []): ?>
      <div class="catalog-editorial__section catalog-editorial__section--bullets">
        <h3 class="catalog-editorial__section-title">Puntos clave</h3>
        <ul class="catalog-editorial__bullet-list">
          <?php foreach ($bullets as $bullet): ?>
            <li class="catalog-editorial__bullet-item"><?= e((string)$bullet) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($specs !== []): ?>
      <div class="catalog-editorial__section catalog-editorial__section--specs">
        <h3 class="catalog-editorial__section-title">Guia rapida</h3>
        <dl class="catalog-editorial__spec-list">
          <?php foreach ($specs as $spec): ?>
            <div class="catalog-editorial__spec-item">
              <dt class="catalog-editorial__spec-label"><?= e((string)($spec['label'] ?? '')) ?></dt>
              <dd class="catalog-editorial__spec-value"><?= e((string)($spec['value'] ?? '')) ?></dd>
            </div>
          <?php endforeach; ?>
        </dl>
      </div>
    <?php endif; ?>

    <?php if ($faq !== []): ?>
      <div class="catalog-editorial__section catalog-editorial__section--faq">
        <h3 class="catalog-editorial__section-title">Preguntas frecuentes</h3>
        <div class="catalog-editorial__faq-list">
          <?php foreach ($faq as $item): ?>
            <details class="catalog-editorial__faq-item">
              <summary class="catalog-editorial__faq-question">
                <span class="catalog-editorial__faq-question-text"><?= e((string)($item['q'] ?? '')) ?></span>
                <span class="catalog-editorial__faq-chevron" aria-hidden="true">
                  <?= ui_icon('chevron-down-solid') ?>
                </span>
              </summary>
              <p class="catalog-editorial__faq-answer"><?= e((string)($item['a'] ?? '')) ?></p>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
