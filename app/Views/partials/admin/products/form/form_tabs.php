<div class="av3-pad-4">
  <form id="product-form" method="post" action="<?= url('/admin/products/save') ?>" class="av3-flow-4" autocomplete="off">
    <?php csrf_field(); ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="next" value="<?= e($nextUrl) ?>">
    <input type="hidden" id="product-active-tab" name="__tab" value="<?= e($activeTab) ?>">

    <div class="tab-panel" id="tab-panel-basicos" role="tabpanel" data-tab="basicos" <?= $activeTab === 'basicos' ? '' : 'hidden' ?>>
      <?php section('partials/admin/products/tab_basicos', [
        'p' => $p,
        'brands' => $brands,
        'cats' => $cats,
        'suppliers' => ($suppliers ?? []),
        'id' => $id,
        'catalogTagGroups' => ($catalogTagGroups ?? []),
        'productTagIds' => ($productTagIds ?? []),
      ]); ?>
    </div>

    <div class="tab-panel" id="tab-panel-inventario" role="tabpanel" data-tab="inventario" <?= $activeTab === 'inventario' ? '' : 'hidden' ?>>
      <?php section('partials/admin/products/tab_venta', [
        'p' => $p,
        'inv' => $inv,
        'id' => $id,
        'variantsCount' => $variantsCount,
        'variantsCtx' => ($variantsCtx ?? []),
      ]); ?>
    </div>

    <div class="tab-panel" id="tab-panel-seo" role="tabpanel" data-tab="seo" <?= $activeTab === 'seo' ? '' : 'hidden' ?>>
      <?php section('partials/admin/products/tab_seo', [
        'p' => $p,
        'seoAudit' => $seoAudit ?? null,
        'seoSettings' => $seoSettings ?? [],
        'seoBlocks' => $seoBlocks ?? ['bullets' => [], 'specs' => [], 'faq' => []],
      ]); ?>
    </div>

    <div class="tab-panel" id="tab-panel-publicacion" role="tabpanel" data-tab="publicacion" <?= $activeTab === 'publicacion' ? '' : 'hidden' ?>>
      <?php section('partials/admin/products/tab_publicacion', [
        'p' => $p,
        'id' => $id,
        'publicationPreview' => ($publicationPreview ?? []),
        'variantsCount' => ($variantsCount ?? 0),
        'wizardStatus' => ($wizardStatus ?? null),
      ]); ?>
    </div>
  </form>

  <div class="tab-panel" id="tab-panel-variantes" role="tabpanel" data-tab="variantes" <?= $activeTab === 'variantes' ? '' : 'hidden' ?>>
    <?php section('partials/admin/products/tab_variantes', ['id' => $id]); ?>
  </div>

  <div class="tab-panel" id="tab-panel-imagenes" role="tabpanel" data-tab="imagenes" <?= $activeTab === 'imagenes' ? '' : 'hidden' ?>>
    <?php section('partials/admin/products/tab_imagenes', [
      'id' => $id,
      'imgs' => $imgs,
      'imgsImagesTab' => $imgsImagesTab ?? $imgs,
      'imgsImagesTabFiltered' => $imgsImagesTabFiltered ?? ($imgsImagesTab ?? $imgs),
      'imgFilterVariantId' => $imgFilterVariantId ?? null,
      'imgVariants' => $imgVariants ?? [],
      'imgVariantId' => $imgVariantId ?? 0,
      'imagesVariantSupported' => $imagesVariantSupported ?? false,
      'imgScopesImages' => $imgScopesImages ?? [0 => ($imgsImagesTab ?? $imgs ?? [])],
    ]); ?>
  </div>
</div>
