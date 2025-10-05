<?php
$any = ($query!=='' || $categoria!=='' || $min!=='' || $max!=='');
$preserve = function(array $extra=[]){
  $q = array_merge($_GET, $extra);
  return '?' . http_build_query(array_filter($q, fn($v)=>$v!==null && $v!==''));
};
?>
<?php if ($any): ?>
<div class="container mx-auto px-3 sm:px-4 lg:px-6 py-3 text-sm flex items-center gap-2 flex-wrap">
  <span class="text-gray-500">Filtros:</span>
  <?php if ($query!==''): ?>
    <a href="<?= e(url('/productos').$preserve(['q'=>null,'page'=>1])) ?>" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200">Búsqueda: "<?= e($query) ?>" ×</a>
  <?php endif; ?>
  <?php if ($categoria!==''): ?>
    <a href="<?= e(url('/productos').$preserve(['categoria'=>null,'page'=>1])) ?>" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200">Categoría: <?= e($categoria) ?> ×</a>
  <?php endif; ?>
  <?php if ($min!==''): ?>
    <a href="<?= e(url('/productos').$preserve(['min'=>null,'page'=>1])) ?>" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200">Mín: $<?= number_format((float)$min,0,',','.') ?> ×</a>
  <?php endif; ?>
  <?php if ($max!==''): ?>
    <a href="<?= e(url('/productos').$preserve(['max'=>null,'page'=>1])) ?>" class="px-3 py-1 rounded-full bg-gray-100 hover:bg-gray-200">Máx: $<?= number_format((float)$max,0,',','.') ?> ×</a>
  <?php endif; ?>
  <a href="<?= e(url('/productos')) ?>" class="ml-auto text-gray-500 hover:text-gray-700">Quitar todos</a>
</div>
<?php endif; ?>
