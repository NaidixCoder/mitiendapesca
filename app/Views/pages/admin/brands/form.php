<?php /** @var array $brand */ $b = $brand ?? ['id'=>0,'name'=>'','slug'=>'']; $id=(int)($b['id']??0); ?>
<section class="max-w-3xl mx-auto p-6 space-y-4">
  <header class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold"><?= $id ? 'Editar marca' : 'Nueva marca' ?></h1>
    <div class="flex gap-2">
      <a class="btn-secondary" href="<?= url('/admin/brands') ?>">Cancelar</a>
      <button form="brand-form" class="btn-primary"><?= $id ? 'Guardar' : 'Crear' ?></button>
    </div>
  </header>

  <?php if ($m = flash('error')): ?>
    <div class="card p-3" style="border-left:4px solid var(--danger)"><?= e($m) ?></div>
  <?php endif; ?>

  <form id="brand-form" method="post" action="<?= url('/admin/brands/save') ?>" class="card p-4 space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <label class="block">Nombre
      <input id="brand-name" class="input w-full" name="name" value="<?= e($b['name'] ?? '') ?>" required>
    </label>

    <label class="block">Slug
      <input id="brand-slug" class="input w-full" name="slug" value="<?= e($b['slug'] ?? '') ?>" placeholder="auto desde nombre si lo dejás vacío">
    </label>
  </form>
</section>

<script>
(function(){
  const name = document.getElementById('brand-name');
  const slug = document.getElementById('brand-slug');
  if(!name || !slug) return;
  const slugify = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
  name.addEventListener('blur', () => { if(!slug.value.trim()) slug.value = slugify(name.value); });
})();
</script>
