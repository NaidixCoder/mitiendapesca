<?php /** @var ?array $job */ ?>
<div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
  <div class="flex items-center justify-between">
    <h2 class="text-slate-100 font-semibold">Último import</h2>
    <a class="text-sky-300 text-sm hover:underline" href="<?= url('/admin/imports') ?>">Historial</a>
  </div>
  <?php
    $type     = $job['job_type']    ?? ($job['type']        ?? '—');
    $status   = $job['status']      ?? ($job['state']       ?? '—');
    $file     = $job['file_name']   ?? ($job['filename']    ?? null);
    $total    = $job['total_rows']  ?? ($job['total']       ?? null);
    $ok       = $job['ok_rows']     ?? ($job['success']     ?? null);
    $err      = $job['err_rows']    ?? ($job['errors']      ?? null);
    $created  = $job['created_at']  ?? ($job['created']     ?? null);
    $finished = $job['finished_at'] ?? ($job['finished']    ?? null);

    $badge = ['class'=>'bg-slate-700/30 text-slate-300','text'=>$status];
    if (is_string($status)) {
      $s = strtolower($status);
      if (in_array($s,['queued','pending']))    $badge=['class'=>'bg-amber-500/20 text-amber-300','text'=>$status];
      if (in_array($s,['running','processing']))$badge=['class'=>'bg-sky-500/20 text-sky-300','text'=>$status];
      if (in_array($s,['done','completed','ok']))$badge=['class'=>'bg-emerald-600/20 text-emerald-300','text'=>$status];
      if (in_array($s,['failed','error']))      $badge=['class'=>'bg-rose-500/20 text-rose-300','text'=>$status];
    }
  ?>
  <?php if ($job): ?>
    <div class="mt-2 text-sm text-slate-300 space-y-1">
      <div><span class="text-slate-400">Tipo:</span> <?= e($type) ?></div>
      <?php if ($file): ?><div><span class="text-slate-400">Archivo:</span> <?= e($file) ?></div><?php endif; ?>
      <div>
        <span class="text-slate-400">Estado:</span>
        <span class="px-2 py-0.5 rounded <?= e($badge['class']) ?> text-xs"><?= e($badge['text']) ?></span>
      </div>
      <div>
        <span class="text-slate-400">Filas:</span>
        <?php if ($total!==null): ?>
          <?= (int)$ok ?> OK · <?= (int)$err ?> ERR / <?= (int)$total ?> total
        <?php else: ?>—<?php endif; ?>
      </div>
      <div class="text-slate-500 text-xs">
        <?= $created ? 'Creado: '.e($created) : '' ?>
        <?= $finished ? ' · Fin: '.e($finished) : '' ?>
      </div>
    </div>
    <div class="mt-3 flex gap-2">
      <a href="<?= url('/admin/imports') ?>" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs">Ver historial</a>
      <?php if (!empty($job['id'])): ?>
        <a href="<?= url('/admin/imports?id='.(int)$job['id']) ?>" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs">Ver detalle</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="mt-2 text-sm text-slate-400">Sin imports todavía.</div>
    <div class="mt-3">
      <a href="<?= url('/admin/imports/upload') ?>" class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 text-xs">Nuevo import</a>
    </div>
  <?php endif; ?>
</div>
