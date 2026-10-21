<?php if ($snapshotEnabled): ?>
  <div class="card" style="padding: 16px; margin-top: 14px;">
    <div style="font-size:14px; font-weight:700; margin-bottom:10px;">Estado actual de la entidad</div>

    <?php if ($snapshotState === 'not_found'): ?>
      <div style="color:#5a6b7b;">No se encontro la entidad (posible eliminacion).</div>
    <?php elseif ($snapshotState !== 'ok'): ?>
      <div style="color:#5a6b7b;">No hay informacion disponible.</div>
    <?php else: ?>
      <div class="audit-kv">
        <?php foreach ($snapshotRows as $row): ?>
          <div class="audit-kv__row">
            <div class="audit-kv__k"><?= htmlspecialchars((string)($row['field_label'] ?? '')) ?></div>
            <div class="audit-kv__v"><?= htmlspecialchars((string)($row['value'] ?? '')) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
