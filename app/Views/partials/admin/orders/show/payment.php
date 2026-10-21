<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h4 class="av3-weight-strong">Pago</h4>
  </header>

  <div class="av3-grid av3-md-grid-cols-3 av3-gap-3 av3-type-body">
    <div class="admin-surface av3-pad-3 av3-radius-control">
      <div class="av3-type-caption av3-text-slate-500">Metodo</div>
      <div class="av3-weight-medium av3-space-top-1"><?= e($paymentMethodLabel) ?></div>
    </div>

    <div class="admin-surface av3-pad-3 av3-radius-control">
      <div class="av3-type-caption av3-text-slate-500">Estado</div>
      <div class="av3-weight-medium av3-space-top-1"><?= e((string)($statusLabel !== '' ? $statusLabel : $status)) ?></div>
    </div>

    <div class="admin-surface av3-pad-3 av3-radius-control av3-flex av3-items-center av3-justify-between av3-gap-3">
      <div>
        <div class="av3-type-caption av3-text-slate-500">Accion</div>
        <div class="av3-type-caption av3-text-var-fg av3-space-top-1">
          <?= $isBankTransfer ? 'Confirmacion manual' : '—' ?>
        </div>
      </div>

      <?php if ($status === 'pending_payment' && $isBankTransfer): ?>
        <div id="mark-paid-wrap">
          <form id="mark-paid-form" method="post" action="<?= url('/admin/orders/mark-paid') ?>" class="av3-inline">
            <?php csrf_field(); ?>
            <input type="hidden" name="id" value="<?= (int)$orderView['id'] ?>">
            <input type="hidden" name="return_to" value="show">
            <button type="submit" class="btn-primary">Marcar pagado</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($payment): ?>
    <div class="av3-space-top-3 av3-type-body">
      <div class="av3-type-caption av3-text-slate-500 av3-space-bottom-1">Proveedor</div>
      <div class="av3-flex av3-flex-wrap av3-gap-3">
        <div><span class="av3-text-slate-500">Proveedor:</span> <?= e((string)($payment['provider'] ?? '—')) ?></div>
        <div><span class="av3-text-slate-500">Payment ID:</span> <?= e((string)($payment['provider_payment_id'] ?? '—')) ?></div>
        <div><span class="av3-text-slate-500">Estado proveedor:</span> <?= e((string)($payment['status'] ?? '—')) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($isBankTransfer && !empty($receiptRows)): ?>
    <div class="av3-space-top-4">
      <div class="av3-type-caption av3-text-slate-500 av3-space-bottom-1">Comprobantes adjuntos</div>
      <div class="av3-flow-2">
        <?php foreach ($receiptRows as $row): ?>
          <div class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
            <div class="av3-min-w-0">
              <div class="av3-type-body av3-weight-strong av3-truncate"><?= e((string)($row['name'] ?? 'comprobante')) ?></div>
              <div class="av3-type-caption av3-text-slate-500"><?= e((string)($row['created_at'] ?? '')) ?> · <?= (int)($row['size_kb'] ?? 0) ?> KB</div>
            </div>
            <div class="av3-flex av3-items-center av3-gap-2">
              <a class="btn-ghost" target="_blank" rel="noopener" href="<?= e((string)($row['view_url'] ?? '#')) ?>">Ver</a>
              <a class="btn-ghost" href="<?= e((string)($row['download_url'] ?? '#')) ?>">Descargar</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($receiptPreview['show'])): ?>
        <details class="av3-space-top-3 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
          <summary class="av3-cursor-pointer av3-type-body av3-weight-strong av3-text-slate-800">Previsualizar ultimo comprobante</summary>
          <div class="av3-space-top-3">
            <?php if (!empty($receiptPreview['is_img'])): ?>
              <a target="_blank" rel="noopener" href="<?= e((string)$receiptPreview['open_url']) ?>">
                <img class="max-h-[520px] av3-width-full av3-radius-control av3-border av3-border-slate-200 av3-object-contain av3-bg-slate-50" src="<?= e((string)$receiptPreview['img_url']) ?>" alt="Comprobante">
              </a>
            <?php elseif (!empty($receiptPreview['is_pdf'])): ?>
              <div class="av3-type-body av3-text-slate-700">PDF adjunto.</div>
              <a class="btn-ghost av3-space-top-2 av3-inline-flex" target="_blank" rel="noopener" href="<?= e((string)$receiptPreview['open_url']) ?>">Abrir PDF</a>
            <?php endif; ?>
          </div>
        </details>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
