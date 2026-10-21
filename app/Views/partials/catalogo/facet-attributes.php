<?php
/** @var array $filterAside */

$filterAside = is_array($filterAside ?? null) ? $filterAside : [];
$moreFilters = is_array($filterAside['more_filters'] ?? null) ? $filterAside['more_filters'] : [];
$groups = is_array($filterAside['tag_groups'] ?? null) ? $filterAside['tag_groups'] : [];
$flatFacets = is_array($moreFilters['flat_facets'] ?? null) ? $moreFilters['flat_facets'] : [];

if (empty($moreFilters['has_groups']) || ($groups === [] && $flatFacets === [])) {
    return;
}
?>

<section class="catalog-facet catalog-facet--more-filters">
  <details class="catalog-more-filters"<?= !empty($moreFilters['is_open']) ? ' open' : '' ?> data-more-filters>
    <summary class="catalog-more-filters__summary">
      <span class="catalog-facet__title"><?= e((string)($moreFilters['label'] ?? 'Mas filtros')) ?></span>
      <span class="catalog-more-filters__chev" aria-hidden="true">⌄</span>
    </summary>

    <div class="catalog-more-filters__body">
      <?php if (!empty($moreFilters['use_flat_facets']) && $flatFacets !== []): ?>
        <?php foreach ($flatFacets as $facet): ?>
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
      <?php else: ?>
        <?php section('partials/catalogo/facet-attribute-groups', [
          'groups' => $groups,
          'title' => (string)($moreFilters['label'] ?? 'Mas filtros'),
        ]); ?>
      <?php endif; ?>
    </div>
  </details>
</section>
