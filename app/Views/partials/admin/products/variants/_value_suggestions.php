<?php
/** @var array $suggestions */
$suggestions = $suggestions ?? [];
if (!is_array($suggestions) || count($suggestions) === 0) {
  echo '';
  return;
}
?>
<div class="av3-space-top-2 av3-flex av3-flex-wrap av3-gap-2" data-role="vs-suggest-list">
  <?php foreach ($suggestions as $s):
    $id = (int)($s['id'] ?? 0);
    $val = (string)($s['value'] ?? '');
    if (!$id || $val === '') continue;
  ?>
    <button type="button"
            class="av3-inline-flex av3-items-center av3-gap-1 av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y1 av3-type-caption av3-text-slate-700 av3-hover-border-slate-300 av3-hover-bg-slate-50"
            data-role="vs-suggest-use"
            data-value-id="<?= (int)$id ?>"
            data-value-label="<?= e($val) ?>">
      <?= e($val) ?>
      <span class="av3-type-micro av3-text-slate-400">+</span>
    </button>
  <?php endforeach; ?>
</div>
