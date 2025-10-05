<?php
$totalPages = max(1, (int)ceil($total / $perPage));
$cur = $page; $window = 2;
$start = max(1, $cur - $window);
$end   = min($totalPages, $cur + $window);
$preserve = function(array $extra=[]){
  $q = array_merge($_GET, $extra);
  return '?' . http_build_query(array_filter($q, fn($v)=>$v!==null && $v!==''));
};
?>
<div class="container mx-auto px-3 sm:px-4 lg:px-6">
  <nav class="flex items-center justify-between py-6" aria-label="Paginación">
    <p class="text-sm text-gray-500">Página <strong><?= $cur ?></strong> de <strong><?= $totalPages ?></strong> — <?= $perPage ?> por página</p>
    <ul class="flex items-center gap-1">
      <li><a class="px-3 py-2 rounded-lg border border-gray-200 <?= $cur<=1?'pointer-events-none opacity-40':'' ?>"
             href="<?= e($preserve(['page'=>max(1,$cur-1)])) ?>">Prev</a></li>
      <?php if ($start > 1): ?>
        <li><a class="px-3 py-2 rounded-lg border border-gray-200" href="<?= e($preserve(['page'=>1])) ?>">1</a></li>
        <?php if ($start > 2): ?><li class="px-2 text-gray-400">…</li><?php endif; ?>
      <?php endif; ?>
      <?php for($i=$start;$i<=$end;$i++): ?>
        <li><a class="px-3 py-2 rounded-lg border <?= $i===$cur?'bg-gray-900 text-white border-gray-900':'border-gray-200 hover:border-gray-300' ?>"
               href="<?= e($preserve(['page'=>$i])) ?>"><?= $i ?></a></li>
      <?php endfor; ?>
      <?php if ($end < $totalPages): ?>
        <?php if ($end < $totalPages-1): ?><li class="px-2 text-gray-400">…</li><?php endif; ?>
        <li><a class="px-3 py-2 rounded-lg border border-gray-200" href="<?= e($preserve(['page'=>$totalPages])) ?>"><?= $totalPages ?></a></li>
      <?php endif; ?>
      <li><a class="px-3 py-2 rounded-lg border border-gray-200 <?= $cur>=$totalPages?'pointer-events-none opacity-40':'' ?>"
             href="<?= e($preserve(['page'=>min($totalPages,$cur+1)])) ?>">Next</a></li>
    </ul>
  </nav>
</div>
