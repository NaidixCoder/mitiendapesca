<?php /** @var array $checks */ ?>
<div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
  <div class="flex items-center justify-between">
    <h2 class="text-slate-100 font-semibold">Salud del sistema</h2>
    <a href="<?= url('/admin/settings') ?>" class="text-sky-300 text-sm hover:underline">Ajustes</a>
  </div>
  <div class="mt-3 grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
    <?php foreach (($checks ?? []) as $c): ?>
      <div class="rounded-xl border border-slate-700/60 p-3 bg-slate-800/50">
        <div class="text-slate-400 text-xs"><?= e($c['label']) ?></div>
        <div class="mt-1 text-slate-100 text-sm"><?= e($c['value']) ?></div>
        <div class="mt-1 text-xs <?= !empty($c['ok'])?'text-emerald-400':'text-amber-300' ?>">
          <?= !empty($c['ok'])?'OK':'Revisar' ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
