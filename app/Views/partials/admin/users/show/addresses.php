<?php
$addr = is_array($show['addresses'] ?? null) ? $show['addresses'] : ['rows' => []];
$addressesRows = is_array($addr['rows'] ?? null) ? $addr['rows'] : [];
?>
<div class="av3-card">
  <div class="av3-card__head">
    <h2 class="av3-card__title">Direcciones</h2>
    <div class="av3-card__meta">Ultimas 5</div>
  </div>

  <?php if (!empty($addressesRows)): ?>
    <div class="addr-list">
      <?php foreach ($addressesRows as $a): ?>
        <?php $a = is_array($a) ? $a : []; ?>
        <div class="addr">
          <div class="addr__top">
            <div class="addr__title">
              <?= e((string)($a['label'] ?? 'Direccion')) ?>
              <?php if (!empty($a['is_default'])): ?><span class="badge badge-ok">Predeterminada</span><?php endif; ?>
            </div>
            <div class="addr__meta"><?= e((string)($a['recipient_name'] ?? '')) ?></div>
          </div>
          <div class="addr__lines">
            <div><?= e((string)($a['address_line1'] ?? '')) ?><?= !empty($a['address_line2']) ? ' - '.e((string)$a['address_line2']) : '' ?></div>
            <div class="muted"><?= e((string)($a['city'] ?? '')) ?><?= !empty($a['province']) ? ', '.e((string)$a['province']) : '' ?><?= !empty($a['postal_code']) ? ' ('.e((string)$a['postal_code']).')' : '' ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-mini"><p>No hay direcciones registradas.</p></div>
  <?php endif; ?>
</div>
