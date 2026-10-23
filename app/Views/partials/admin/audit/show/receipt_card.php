<?php if (!empty($receipt['show'])): ?>
  <div class="av3-ui-card av3-audit-detail">
    <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
      <div>
        <div style="font-size:14px; font-weight:700;">Comprobante adjunto</div>
        <div style="margin-top:4px; color:#5a6b7b; font-size:13px;">
          <?= !empty($receipt['file']) ? htmlspecialchars((string)$receipt['file']) : '-' ?>
          <?php if (!empty($receipt['size_kb'])): ?> · <?= htmlspecialchars((string)$receipt['size_kb']) ?> KB<?php endif; ?>
          <?php if (!empty($receipt['at'])): ?> · <?= htmlspecialchars((string)$receipt['at']) ?><?php endif; ?>
        </div>
      </div>
      <?php if (!empty($receipt['download_url'])): ?>
        <div style="display:flex; gap:8px;">
          <a class="btn btn--sm" href="<?= htmlspecialchars((string)$receipt['download_url']) ?>">Descargar</a>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($receipt['preview_url'])): ?>
      <div style="margin-top:12px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#fff;">
        <?php if (!empty($receipt['is_pdf'])): ?>
          <iframe title="Vista previa del comprobante" loading="lazy" src="<?= htmlspecialchars((string)$receipt['preview_url']) ?>" style="width:100%; height:520px; border:0;"></iframe>
        <?php else: ?>
          <img src="<?= htmlspecialchars((string)$receipt['preview_url']) ?>" alt="Comprobante" style="width:100%; display:block;" />
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div style="margin-top:10px; color:#5a6b7b;">No se pudo previsualizar el comprobante.</div>
    <?php endif; ?>
  </div>
<?php endif; ?>
