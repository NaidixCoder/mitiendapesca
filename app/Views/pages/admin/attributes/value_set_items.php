<?php
$set = is_array($set ?? null) ? $set : [];
$items = is_array($items ?? null) ? $items : [];
?>
<div class="av3-attributes__set-detail">
  <div>
    <div class="av3-attributes__set-title"><?= e($set['name'] ?? '') ?></div>
    <div class="av3-attributes__set-meta"><?= e($set['category_name'] ?? '—') ?> · <?= e($set['brand_name'] ?? '—') ?></div>
  </div>
  <?php if (empty($items)): ?>
    <div class="av3-attributes__notice">Esta plantilla no contiene valores.</div>
  <?php else: ?>
    <?php foreach ($items as $g): ?>
      <section class="av3-attributes__set-group">
        <h3><?= e($g['attribute_name'] ?? '') ?></h3>
        <div class="av3-attributes__chips">
          <?php foreach (($g['values'] ?? []) as $v): ?>
            <span class="av3-attributes__chip"><?= e($v['value'] ?? '') ?></span>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
