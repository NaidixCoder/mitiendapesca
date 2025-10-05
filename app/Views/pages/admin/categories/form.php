<?php
/** @var array $cat */
/** @var array $parents */
$c  = $cat ?? ['id'=>0,'name'=>'','slug'=>'','parent_id'=>null,'sort'=>0,'is_active'=>1];
$id = (int)($c['id'] ?? 0);
?>
<section class="max-w-4xl mx-auto p-6 space-y-4">
  <header class="flex items-center justify-between">
    <h1 class="text-2xl font-semibold"><?= $id ? 'Editar categoría' : 'Nueva categoría' ?></h1>
    <div class="flex gap-2">
      <a class="btn-secondary" href="<?= url('/admin/categories') ?>">Cancelar</a>
      <button form="cat-form" class="btn-primary"><?= $id ? 'Guardar' : 'Crear' ?></button>
    </div>
  </header>

  <?php if ($m = flash('error')): ?>
    <div class="card p-3" style="border-left:4px solid var(--danger)"><?= e($m) ?></div>
  <?php endif; ?>

  <form id="cat-form" method="post" action="<?= url('/admin/categories/save') ?>" class="card p-4 space-y-4">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <label class="block">Nombre
        <input id="cat-name" class="input w-full" name="name" value="<?= e($c['name'] ?? '') ?>" required>
      </label>

      <label class="block">Slug
        <input id="cat-slug" class="input w-full" name="slug" value="<?= e($c['slug'] ?? '') ?>" placeholder="auto desde nombre si lo dejás vacío">
      </label>

      <label class="block">Padre
        <select class="input w-full" name="parent_id">
          <option value="">—</option>
          <?php foreach (($parents ?? []) as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((int)($c['parent_id'] ?? 0)===(int)$p['id'])?'selected':'' ?>>
              <?= e($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label class="block">Orden
        <input class="input w-full" type="number" name="sort" value="<?= (int)($c['sort'] ?? 0) ?>">
      </label>

      <label class="inline-flex items-center gap-2 md:col-span-2">
        <input type="checkbox" name="is_active" <?= !empty($c['is_active']) ? 'checked' : '' ?>>
        <span>Activo</span>
      </label>
    </div>
  </form>
</section>

<script>
(function(){
  const name = document.getElementById('cat-name');
  const slug = document.getElementById('cat-slug');
  if(!name || !slug) return;
  const slugify = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
  name.addEventListener('blur', () => { if(!slug.value.trim()) slug.value = slugify(name.value); });
})();
</script>
