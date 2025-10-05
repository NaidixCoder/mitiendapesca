<?php /** @var array $kpis */ ?>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
  <?php foreach (($kpis ?? []) as $k): ?>
    <div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
      <div class="flex items-start justify-between">
        <div>
          <div class="text-slate-400 text-xs uppercase tracking-wide"><?= e($k['label']) ?></div>
          <div class="mt-2 text-2xl font-semibold text-slate-100"><?= e($k['value']) ?></div>
          <div class="text-slate-500 text-xs mt-1"><?= e($k['sub']) ?></div>
        </div>
        <div class="h-10 w-10 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center">
          <span class="text-slate-300 text-xs font-semibold">KPI</span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
