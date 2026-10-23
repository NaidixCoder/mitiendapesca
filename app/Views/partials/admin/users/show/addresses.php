<?php
$addr=is_array($show['addresses']??null)?$show['addresses']:[];
$addressesRows=is_array($addr['rows']??null)?$addr['rows']:[];
?>
<section class="av3-ui-card">
 <div class="av3-ui-card-header"><h2 class="av3-ui-card-title">Direcciones</h2><span class="av3-users__meta">Últimas cinco</span></div>
 <div class="av3-ui-card-body av3-users__stack">
  <?php if (!$addressesRows): ?><p class="av3-ui-empty">Sin direcciones registradas.</p><?php endif; ?>
  <?php foreach ($addressesRows as $a): ?>
   <?php if (!is_array($a)) continue; ?>
   <div class="av3-users__address">
    <strong><?= e((string)($a['label']??'Dirección')) ?></strong>
    <?php if (!empty($a['is_default'])): ?><span class="av3-ui-badge av3-ui-badge--neutral">Principal</span><?php endif; ?>
    <div class="av3-users__meta"><?= e((string)($a['recipient_name']??'')) ?></div>
    <div class="av3-users__info"><?= e((string)($a['address_line1']??'')) ?> <?= e((string)($a['address_line2']??'')) ?></div>
    <div class="av3-users__meta"><?= e((string)($a['city']??'')) ?>, <?= e((string)($a['province']??'')) ?> <?= e((string)($a['postal_code']??'')) ?></div>
   </div>
  <?php endforeach; ?>
 </div>
</section>
