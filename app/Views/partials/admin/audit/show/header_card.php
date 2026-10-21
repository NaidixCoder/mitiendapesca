<div class="card" style="padding: 16px;">
  <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap;">
    <div>
      <div style="font-size:18px; font-weight:700; margin-bottom:6px;"><?= htmlspecialchars($title) ?></div>
      <div style="color:#5a6b7b;"><?= htmlspecialchars((string)($entity['label'] ?? '-')) ?> · por <strong><?= htmlspecialchars($actor) ?></strong></div>
      <?php if ($from !== '' && $to !== ''): ?>
        <div style="margin-top:6px; color:#5a6b7b;"><?= htmlspecialchars($from) ?> <span style="opacity:.6;">→</span> <?= htmlspecialchars($to) ?></div>
      <?php else: ?>
        <div style="margin-top:6px; color:#5a6b7b; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"><?= htmlspecialchars($aKey) ?></div>
      <?php endif; ?>
    </div>
    <div style="text-align:right; color:#5a6b7b;">
      <div style="font-weight:600;">#<?= (int)($item['id'] ?? 0) ?></div>
      <div><?= htmlspecialchars($dt) ?></div>
      <?php if (!empty($entity['href'])): ?>
        <div style="margin-top:8px;"><a class="btn btn--sm" href="<?= htmlspecialchars((string)$entity['href']) ?>">Ir a la entidad</a></div>
      <?php endif; ?>
    </div>
  </div>
</div>
