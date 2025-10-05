<?php
// Espera: $query,$categoria,$min,$max,$orden,$categories
$preserve = function(array $extra=[]){
  $q = array_merge($_GET, $extra);
  return '?' . http_build_query(array_filter($q, fn($v)=>$v!==null && $v!==''));
};
?>
<div class="sticky top-[60px] z-30 bg-white/80 backdrop-blur border-b border-gray-100">
  <div class="container mx-auto px-3 sm:px-4 lg:px-6 py-3 flex flex-col lg:flex-row gap-3">
    <form method="get" action="<?= e(url('/productos')) ?>" class="flex-1 flex gap-2">
      <input type="search" name="q" value="<?= e($query) ?>" placeholder="Buscar cañas, reels, señuelos…"
        class="flex-1 rounded-xl border border-gray-200 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-brand/30"/>
      <button class="rounded-xl px-4 py-2 border border-brand text-brand hover:bg-brand hover:text-white transition">Buscar</button>
      <?php foreach(['categoria'=>$categoria,'min'=>$min,'max'=>$max,'orden'=>$orden] as $k=>$v): if($v!==''): ?>
        <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; endforeach; ?>
    </form>

    <nav class="flex items-center gap-2 overflow-x-auto scrollbar-thin py-1">
      <a href="<?= e(url('/productos')) ?>" class="px-3 py-2 rounded-full border <?= $categoria===''?'bg-gray-900 text-white':'border-gray-200' ?>">Todas</a>
      <?php foreach($categories as $cat): ?>
        <a href="<?= e(url('/productos').$preserve(['categoria'=>$cat['slug'],'page'=>1])) ?>"
           class="px-3 py-2 rounded-full border whitespace-nowrap <?= $categoria===$cat['slug']?'bg-gray-900 text-white':'border-gray-200 hover:border-gray-300' ?>">
           <?= e($cat['name']) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="flex items-center gap-2 ml-auto">
      <form method="get" action="<?= e(url('/productos')) ?>" class="flex items-center gap-2">
        <?php foreach(['q'=>$query,'categoria'=>$categoria,'min'=>$min,'max'=>$max] as $k=>$v){ if($v!==''){echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';}} ?>
        <label class="text-sm text-gray-500">Orden</label>
        <select name="orden" class="rounded-lg border-gray-200" onchange="this.form.submit()">
          <option value="relevancia"     <?= $orden==='relevancia'?'selected':'' ?>>Relevancia</option>
          <option value="precio_asc"     <?= $orden==='precio_asc'?'selected':'' ?>>Precio: menor a mayor</option>
          <option value="precio_desc"    <?= $orden==='precio_desc'?'selected':'' ?>>Precio: mayor a menor</option>
          <option value="novedades"      <?= $orden==='novedades'?'selected':'' ?>>Novedades</option>
          <option value="mejor_valorado" <?= $orden==='mejor_valorado'?'selected':'' ?>>Mejor valorado</option>
        </select>
      </form>

      <!-- Toggle densidad (sin inline JS) -->
      <div class="hidden sm:flex items-center gap-1" role="group" aria-label="Cambiar vista">
        <button type="button" class="px-2 py-2 rounded-lg border border-gray-200"
                data-density="dense" aria-pressed="false" title="Grid denso">▦</button>
        <button type="button" class="px-2 py-2 rounded-lg border border-gray-200"
                data-density="comfy" aria-pressed="false" title="Grid amplio">▤</button>
      </div>
    </div>
  </div>

  <div class="container mx-auto px-3 sm:px-4 lg:px-6 pb-3">
    <form method="get" action="<?= e(url('/productos')) ?>" class="flex items-center gap-3">
      <?php foreach(['q'=>$query,'categoria'=>$categoria,'orden'=>$orden] as $k=>$v){ if($v!==''){echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">';}} ?>
      <div class="flex items-center gap-2">
        <label class="text-sm text-gray-500">$ Mín</label>
        <input type="number" name="min" value="<?= e($min) ?>" class="w-28 rounded-lg border-gray-200" min="0" step="500"/>
      </div>
      <div class="flex items-center gap-2">
        <label class="text-sm text-gray-500">$ Máx</label>
        <input type="number" name="max" value="<?= e($max) ?>" class="w-28 rounded-lg border-gray-200" min="0" step="500"/>
      </div>
      <button class="ml-auto px-4 py-2 rounded-xl border border-gray-200 hover:border-gray-300">Aplicar</button>
      <a href="<?= e(url('/productos')) ?>" class="text-sm text-gray-500 hover:text-gray-700">Limpiar</a>
    </form>
  </div>
</div>
