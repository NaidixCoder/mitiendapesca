<?php /** @var array $events */ ?>
<div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
  <div class="flex items-center justify-between">
    <h2 class="text-slate-100 font-semibold">Actividad reciente</h2>
    <a class="text-sky-300 text-sm hover:underline" href="<?= url('/admin/settings') ?>">Ver todo</a>
  </div>
  <ul class="mt-3 space-y-2 text-sm">
    <?php if (!empty($events)): foreach ($events as $ev):
      $meta = []; if (!empty($ev['metadata'])) { $decoded = json_decode($ev['metadata'], true); if (is_array($decoded)) $meta = $decoded; }
      $subtitle = '';
      if (isset($meta['new_role']))             $subtitle = 'nuevo rol: '.$meta['new_role'];
      elseif (isset($meta['is_active']))        $subtitle = 'is_active: '.((int)$meta['is_active']);
      elseif (isset($meta['from'],$meta['to'])) $subtitle = "de {$meta['from']} a {$meta['to']}";
      elseif (isset($meta['fields']))           $subtitle = 'campos: '.implode(', ', array_keys($meta['fields']));
    ?>
      <li class="border-b border-slate-800 pb-2">
        <div class="text-slate-300">
          <?= e($ev['action']) ?>
          <?php if (!empty($ev['entity_type'])): ?> · <?= e($ev['entity_type']) ?>#<?= (int)$ev['entity_id'] ?><?php endif; ?>
        </div>
        <div class="text-slate-500 text-xs">
          <?= e($ev['created_at']) ?><?= $subtitle ? ' · '.e($subtitle) : '' ?>
        </div>
      </li>
    <?php endforeach; else: ?>
      <li class="text-slate-400">Sin eventos</li>
    <?php endif; ?>
  </ul>
</div>
