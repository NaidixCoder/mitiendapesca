<?php
$bullets = is_array($product['seo_bullets'] ?? null) ? $product['seo_bullets'] : [];
$specs = is_array($product['seo_specs'] ?? null) ? $product['seo_specs'] : [];
$faq = is_array($product['seo_faq'] ?? null) ? $product['seo_faq'] : [];

if (empty($bullets) && empty($specs) && empty($faq)) {
    return;
}
?>

<section class="product-sections">
  <?php if (!empty($bullets)): ?>
    <div class="product-section-card">
      <h3 class="product-section-card__title">Puntos clave</h3>
      <ul class="product-section-card__list">
        <?php foreach ($bullets as $bullet): ?>
          <?php $bullet = trim((string)$bullet); ?>
          <?php if ($bullet === '') continue; ?>
          <li class="product-section-card__item">
            <span class="product-section-card__icon">✓</span>
            <span class="product-section-card__text"><?= h($bullet) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if (!empty($specs)): ?>
    <div class="product-section-card">
      <h3 class="product-section-card__title">Especificaciones</h3>
      <div class="product-specs-table-wrap">
        <table class="product-specs-table">
          <tbody>
            <?php foreach ($specs as $row): ?>
              <?php
              $label = trim((string)($row['label'] ?? ''));
              $value = trim((string)($row['value'] ?? ''));
              if ($label === '' || $value === '') {
                  continue;
              }
              ?>
              <tr>
                <td class="product-specs-table__label"><?= h($label) ?></td>
                <td class="product-specs-table__value"><?= h($value) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($faq)): ?>
    <div class="product-section-card">
      <h3 class="product-section-card__title">Preguntas frecuentes</h3>
      <div class="product-faq-list">
        <?php foreach ($faq as $item): ?>
          <?php
          $question = trim((string)($item['q'] ?? ''));
          $answer = trim((string)($item['a'] ?? ''));
          if ($question === '' || $answer === '') {
              continue;
          }
          ?>
          <details class="product-faq-item">
            <summary class="product-faq-item__summary">
              <span class="product-faq-item__question"><?= h($question) ?></span>
              <span class="product-faq-item__chevron">⌄</span>
            </summary>
            <div class="product-faq-item__answer"><?= nl2br(h($answer)) ?></div>
          </details>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>
