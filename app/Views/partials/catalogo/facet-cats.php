<?php
/** @var array $filters */
/** @var array $facets */

$catActive = $filters['category_slug'] ?? ($filters['categoria'] ?? '');
$subActive = $filters['subcategory_slug'] ?? ($filters['sub'] ?? '');
$cats = $facets['categories'] ?? [];

function catalogFacetSummaryClass(bool $active, bool $parentActive = false): string
{
  if ($active) {
    return 'catalog-acc__summary is-active';
  }

  if ($parentActive) {
    return 'catalog-acc__summary is-parent-active';
  }

  return 'catalog-acc__summary';
}
?>

<section class="catalog-facet catalog-facet--cats">
  <div class="catalog-facet__body">
    <div>
      <h3 class="catalog-facet__title">Categorias</h3>
    </div>
    <ul class="catalog-facet__list">
      <li>
        <a
          href="<?= e(url('/productos')) ?>"
          class="<?= catalogFacetSummaryClass($catActive ==='' && $subActive === '') ?> catalog-facet__all"
          aria-current="<?= ($catActive === '' && $subActive === '') ? 'true' : 'false' ?>"
        >
          <span class="catalog-acc__label">Todas</span>
        </a>
      </li>

      <?php foreach ($cats as $cat): ?>
        <?php
        $cSlug = (string)($cat['slug'] ?? '');
        $cName = mb_convert_case((string)($cat['name'] ?? '-'), MB_CASE_TITLE, 'UTF-8');
        $cCount = (int)($cat['count'] ?? 0);
        $subs = $cat['children'] ?? [];
        $isCatActive = ($cSlug !== '' && $cSlug === $catActive);
        $isParentContext = $isCatActive && $subActive !== '';
        $hrefCat = url('/categoria') . qs_preserve([
          'slug' => $cSlug,
          'categoria' => null,
          'sub' => null,
          'page' => null,
        ]);
        ?>

        <li>
          <details class="js-facet-details catalog-acc" <?= $isCatActive ? 'open' : '' ?> data-cat="<?= e($cSlug) ?>">
            <summary class="<?= catalogFacetSummaryClass($isCatActive && $subActive ==='', $isParentContext) ?><?= empty($subs) ? ' catalog-acc__summary--leaf' : '' ?>">
              <a class="catalog-acc__link" href="<?= e($hrefCat) ?>" aria-current="<?= $isCatActive ? 'true' : 'false' ?>">
                <span class="catalog-acc__label site-truncate"><?= e($cName) ?></span>
              </a>

              <span class="catalog-acc__count"><?= $cCount ?></span>
              <span class="catalog-acc__chev" aria-hidden="true">⌄</span>
            </summary>

            <?php if (!empty($subs)): ?>
              <ul class="catalog-facet__sublist">
                <?php foreach ($subs as $sub): ?>
                  <?php
                  $sSlug = (string)($sub['slug'] ?? '');
                  $sName = mb_convert_case((string)($sub['name'] ?? '-'), MB_CASE_TITLE, 'UTF-8');
                  $sCount = (int)($sub['count'] ?? 0);
                  $isSubActive = ($sSlug !== '' && $sSlug === $subActive);
                  $hrefSub = url('/categoria') . qs_preserve([
                    'slug' => $cSlug,
                    'sub' => $sSlug,
                    'categoria' => null,
                    'page' => null,
                  ]);
                  ?>
                  <li>
                    <a
                      href="<?= e($hrefSub) ?>"
                      class="catalog-facet__subitem <?= $isSubActive ?'is-active' : '' ?>"
                      aria-current="<?= $isSubActive ? 'true' : 'false' ?>"
                    >
                      <span class="catalog-facet__subitem-label site-truncate"><?= e($sName) ?></span>
                      <span class="catalog-acc__count"><?= $sCount ?></span>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </details>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
