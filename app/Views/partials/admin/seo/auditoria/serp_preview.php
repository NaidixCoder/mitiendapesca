<?php
/** @var array<string,mixed> $r Vista preparada del producto. */
?>
<div class="av3-seo-snippet" aria-label="Vista previa estimada en buscadores">
  <a class="av3-seo-snippet__title" href="<?= e((string)$r['public_url']) ?>" target="_blank" rel="noopener noreferrer" title="Abrir ficha pública"><?= e((string)$r['serp_title']) ?> <span aria-hidden="true">↗</span></a>
  <span class="av3-seo-snippet__url" title="<?= e((string)$r['public_url']) ?>"><?= e((string)$r['public_url']) ?></span>
  <?php if (!empty($r['has_meta_description'])): ?>
    <span class="av3-seo-snippet__desc"><?= e((string)$r['serp_desc']) ?></span>
  <?php else: ?>
    <span class="av3-seo-snippet__empty">Sin descripción SEO</span>
  <?php endif; ?>
</div>
