<?php /** @var array $p */ ?>
<div class="grid grid-cols-1 gap-4">
  <label class="block">Meta Title
    <input class="input w-full" name="meta_title" maxlength="70" value="<?= e($p['meta_title'] ?? '') ?>">
  </label>
  <label class="block">Meta Description
    <textarea class="input w-full min-h-[100px]" name="meta_description" maxlength="160"><?= e($p['meta_description'] ?? '') ?></textarea>
  </label>
  <p class="text-sm text-[color:var(--muted)]">La URL pública usará el <strong>slug</strong>.</p>
</div>
