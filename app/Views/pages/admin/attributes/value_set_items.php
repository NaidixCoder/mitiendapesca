<?php
/** @var array $set */
/** @var array $items */
$set = $set ?? [];
$items = $items ?? [];
?>
<div class="av3-flow-3">
  <div class="av3-flex av3-items-start av3-justify-between av3-gap-3">
    <div>
      <div class="av3-type-body av3-text-var-admin-muted">Plantilla</div>
      <div class="av3-type-base av3-weight-strong"><?= e($set['name'] ?? '') ?></div>
      <div class="av3-type-body av3-text-var-admin-muted">
        <?= e($set['category_name'] ?? '—') ?> · <?= e($set['brand_name'] ?? '—') ?>
      </div>
    </div>
  </div>

  <?php if (empty($items)): ?>
    <div class="av3-type-body av3-text-var-admin-muted">No hay items.</div>
  <?php else: ?>
    <div class="max-h-[50vh] av3-overflow-auto av3-pr-1 av3-flow-3">
      <?php foreach ($items as $g): ?>
        <div class="av3-radius-card av3-border av3-border-var-admin-border av3-surface-white av3-pad-3">
          <div class="av3-weight-medium"><?= e($g['attribute_name'] ?? '') ?></div>
          <div class="av3-space-top-2 av3-flex av3-flex-wrap av3-gap-2">
            <?php foreach (($g['values'] ?? []) as $v): ?>
              <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-var-admin-border av3-bg-gray-50 av3-pad-x2 av3-pad-y1 av3-type-caption">
                <?= e($v['value'] ?? '') ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
