<?php
/** Métrica de admin: elemento presentacional, acepta únicamente datos ya calculados. */
$metric = is_array($metric ?? null) ? $metric : [];
$isLink = !empty($metric['is_link']) && !empty($metric['href']);
$tag = $isLink ? 'a' : 'div';
$href = $isLink ? ' href="' . e((string)$metric['href']) . '"' : '';
?>
<<?= $tag ?> class="dashboard-kpi-card<?= $isLink ?' is-link' : '' ?>"<?= $href ?>>
  <p ><?= e((string)($metric['label'] ?? '')) ?></p>
  <p ><?= e((string)($metric['value'] ?? '0')) ?></p>
  <?php if (!empty($metric['sub'])): ?><p ><?= e((string)$metric['sub']) ?></p><?php endif; ?>
</<?= $tag ?>>
