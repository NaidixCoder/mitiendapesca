<?php
/** @var array $groups */
/** @var string $title */

$groups = is_array($groups ?? null) ? $groups : [];
$title = trim((string)($title ?? 'Mas filtros'));

if ($groups === []) {
    return;
}
?>

<?php foreach ($groups as $group): ?>
  <?php
  $groupKey = trim((string)($group['key'] ?? 'grupo'));
  $groupLabel = trim((string)($group['label'] ?? 'Grupo'));
  $facets = is_array($group['facets'] ?? null) ? $group['facets'] : [];
  if ($facets === []) {
      continue;
  }
  ?>
  <details class="catalog-filter-group"<?= !empty($group['is_open']) ? ' open' : '' ?> data-filter-group>
    <summary class="catalog-filter-group__summary">
      <span class="catalog-filter-group__label"><?= e($groupLabel) ?></span>
      <?php if (!empty($group['selected_count'])): ?>
        <span class="catalog-filter-group__count"><?= (int)$group['selected_count'] ?></span>
      <?php endif; ?>
      <span class="catalog-filter-group__chev" aria-hidden="true">⌄</span>
    </summary>

    <div class="catalog-filter-group__body">
      <?php foreach ($facets as $facet): ?>
        <?php
        $groupSlug = trim((string)($facet['slug'] ?? ''));
        $groupName = trim((string)($facet['name'] ?? ''));
        $visibleOptions = is_array($facet['visible_options'] ?? null) ? $facet['visible_options'] : [];
        $overflowOptions = is_array($facet['overflow_options'] ?? null) ? $facet['overflow_options'] : [];
        if ($groupSlug === '' || $groupName === '' || ($visibleOptions === [] && $overflowOptions === [])) {
            continue;
        }
        ?>
        <details class="catalog-filter-subfacet"<?= !empty($facet['is_open']) ? ' open' : '' ?> data-filter-subfacet>
          <summary class="catalog-filter-subfacet__summary">
            <span class="catalog-filter-subfacet__label"><?= e($groupName) ?></span>
            <?php if (!empty($facet['selected_count'])): ?>
              <span class="catalog-filter-subfacet__count"><?= (int)$facet['selected_count'] ?></span>
            <?php endif; ?>
            <span class="catalog-filter-subfacet__chev" aria-hidden="true">⌄</span>
          </summary>

          <div class="catalog-filter-subfacet__body">
            <div class="catalog-brand-list" aria-label="<?= e('Filtrar por ' . $groupName) ?>">
              <?php foreach ($visibleOptions as $option): ?>
                <label class="catalog-brand-option<?= !empty($option['selected']) ? ' is-active' : '' ?>">
                  <input
                    class="catalog-brand-option__input"
                    type="checkbox"
                    name="tag[<?= e($groupSlug) ?>][]"
                    value="<?= e((string)($option['slug'] ?? '')) ?>"
                    <?= !empty($option['selected']) ? 'checked' : '' ?>
                  >
                  <span class="catalog-brand-option__box" aria-hidden="true"></span>
                  <span class="catalog-brand-option__label"><?= e((string)($option['name'] ?? '')) ?></span>
                  <span class="catalog-brand-option__count"><?= (int)($option['count'] ?? 0) ?></span>
                </label>
              <?php endforeach; ?>

              <?php if ($overflowOptions !== []): ?>
                <div class="catalog-filter-subfacet__overflow"<?= !empty($facet['overflow_open']) ? '' : ' hidden' ?> data-filter-overflow>
                  <?php foreach ($overflowOptions as $option): ?>
                    <label class="catalog-brand-option<?= !empty($option['selected']) ? ' is-active' : '' ?>">
                      <input
                        class="catalog-brand-option__input"
                        type="checkbox"
                        name="tag[<?= e($groupSlug) ?>][]"
                        value="<?= e((string)($option['slug'] ?? '')) ?>"
                        <?= !empty($option['selected']) ? 'checked' : '' ?>
                      >
                      <span class="catalog-brand-option__box" aria-hidden="true"></span>
                      <span class="catalog-brand-option__label"><?= e((string)($option['name'] ?? '')) ?></span>
                      <span class="catalog-brand-option__count"><?= (int)($option['count'] ?? 0) ?></span>
                    </label>
                  <?php endforeach; ?>
                </div>

                <button
                  type="button"
                  class="catalog-filter-subfacet__toggle"
                  data-filter-toggle-options
                  data-label-more="Ver mas"
                  data-label-less="Ver menos"
                ><?= !empty($facet['overflow_open']) ? 'Ver menos' : 'Ver mas' ?></button>
              <?php endif; ?>
            </div>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  </details>
<?php endforeach; ?>
