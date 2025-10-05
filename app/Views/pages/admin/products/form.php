<?php
/** @var array $product */
/** @var array $brands */
/** @var array $cats */
/** @var array $imgs */
/** @var array $inv */
$p   = $product ?? []; $id = (int)($p['id'] ?? 0);
$err = flash('error');
?>
<section class="max-w-6xl mx-auto p-6 space-y-5">
  <header class="flex items-center justify-between">
    <div>
      <h1 class="text-2xl font-semibold"><?= $id ? 'Editar producto' : 'Nuevo producto' ?></h1>
      <p class="text-[color:var(--muted)] text-sm"><?= $id ? 'ID #'.$id : 'Completa los datos y guarda' ?></p>
    </div>
    <div class="flex gap-2">
      <a class="btn-secondary" href="<?= url('/admin/products') ?>">Cancelar</a>
      <button form="product-form" class="btn-primary"><?= $id ? 'Guardar cambios' : 'Crear producto' ?></button>
    </div>
  </header>

  <?php if ($err): ?>
    <div class="card p-3" style="border-left:4px solid var(--danger)"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="card p-0 overflow-hidden">
    <!-- Tabs -->
    <nav class="flex gap-1 px-3 pt-3" id="tabs-nav">
      <button class="tab-btn btn-secondary" data-tab="basicos">Básicos</button>
      <button class="tab-btn btn-secondary" data-tab="imagenes">Imágenes</button>
      <button class="tab-btn btn-secondary" data-tab="inventario">Inventario</button>
      <button class="tab-btn btn-secondary" data-tab="seo">SEO</button>
      <button class="tab-btn btn-secondary" data-tab="rel">Relaciones</button>
    </nav>

    <div class="p-4">
      <!-- FORM principal -->
      <form id="product-form" method="post" action="<?= url('/admin/products/save') ?>" class="space-y-4" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <input type="hidden" name="next" value="<?= e($_GET['next'] ?? '/admin/products') ?>">

        <!-- TAB: Básicos -->
        <div class="tab" data-tab="basicos">
          <?php section('admin/products/tab_basicos', [
            'p'=>$p, 'brands'=>$brands, 'cats'=>$cats
          ]); ?>
        </div>

        <!-- TAB: Inventario -->
        <div class="tab hidden" data-tab="inventario">
          <?php section('admin/products/tab_inventario', ['inv'=>$inv]); ?>
        </div>

        <!-- TAB: SEO -->
        <div class="tab hidden" data-tab="seo">
          <?php section('admin/products/tab_seo', ['p'=>$p]); ?>
        </div>

        <!-- TAB: Relaciones (placeholder) -->
        <div class="tab hidden" data-tab="rel">
          <?php section('admin/products/tab_rel'); ?>
        </div>
      </form>

      <!-- TAB: Imágenes (fuera del form principal por enctype/ajax) -->
      <div class="tab hidden" data-tab="imagenes">
        <?php section('admin/products/tab_imagenes', ['id'=>$id, 'imgs'=>$imgs]); ?>
      </div>
    </div>
  </div>
</section>

<script>
(function(){
  // Tabs
  const tabs = document.querySelectorAll('.tab');
  const btns = document.querySelectorAll('.tab-btn');
  function showTab(name){
    tabs.forEach(t => t.classList.toggle('hidden', t.dataset.tab !== name));
    btns.forEach(b => b.classList.toggle('bg-brand', b.dataset.tab === name));
    // Persistencia simple en hash
    if (name) location.hash = 'tab=' + name;
  }
  btns.forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));
  const initial = (location.hash.match(/tab=([a-z]+)/)||[])[1] || 'basicos';
  showTab(initial);

  // Slug autogenerado si está vacío
  const $name = document.getElementById('product-name');
  const $slug = document.getElementById('product-slug');
  if ($name && $slug) {
    const slugify = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    $name.addEventListener('blur', () => {
      if (($slug.value||'').trim() === '') $slug.value = slugify($name.value);
    });
  }
})();
</script>
