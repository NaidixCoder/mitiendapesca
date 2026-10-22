<?php
$warnList = is_array($show['warn_list'] ?? null) ? $show['warn_list'] : [];
if (!$warnList) { return; }
?>
<section class="av3-ui-card av3-imports__block" aria-labelledby="imports-warnings-heading">
  <header class="av3-imports__block-head"><h3 id="imports-warnings-heading">Advertencias</h3><span><?= count($warnList) ?> avisos</span></header>
  <ul class="av3-imports__log">
    <?php foreach ($warnList as $w): ?>
      <li class="av3-imports__log-item"><strong>Fila <?= (int)($w['row'] ?? 0) ?></strong><p><?= e((string)($w['warning'] ?? '')) ?></p></li>
    <?php endforeach; ?>
  </ul>
</section>
