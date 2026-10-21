<?php if (!empty($fiscalView['has'])): ?>
  <section class="av3-card av3-border av3-border-slate-200">
    <header class="av3-flex av3-flex-col av3-md-flex-row av3-md-items-center av3-md-justify-between av3-gap-2 av3-space-bottom-3">
      <div class="av3-flex av3-items-center av3-gap-2">
        <h4 class="av3-weight-strong">Solicitud de Factura A</h4>
        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-pad-x2 av3-pad-yhalf av3-type-caption <?= e((string)($fiscalView['request_class'] ?? '')) ?>"><?= e((string)($fiscalView['request_label'] ?? '')) ?></span>
        <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-pad-x2 av3-pad-yhalf av3-type-caption <?= e((string)($fiscalView['padron_class'] ?? '')) ?>">Padron: <?= e((string)($fiscalView['padron_label'] ?? '')) ?></span>
      </div>
      <div class="av3-type-caption av3-text-slate-500">Operativa (Etapa 3/4). La emision de Factura A requiere aprobacion.</div>
    </header>

    <div class="av3-grid av3-md-grid-cols-3 av3-gap-3 av3-type-body">
      <div class="admin-surface av3-pad-3 av3-radius-control">
        <div class="av3-type-caption av3-text-slate-500">CUIT</div>
        <div class="av3-weight-medium av3-space-top-1"><?= e((string)(($fiscalView['cuit'] ?? '') !== '' ? $fiscalView['cuit'] : '—')) ?></div>
      </div>
      <div class="admin-surface av3-pad-3 av3-radius-control">
        <div class="av3-type-caption av3-text-slate-500">Razon social</div>
        <div class="av3-weight-medium av3-space-top-1"><?= e((string)(($fiscalView['legal_name'] ?? '') !== '' ? $fiscalView['legal_name'] : '—')) ?></div>
      </div>
      <div class="admin-surface av3-pad-3 av3-radius-control">
        <div class="av3-type-caption av3-text-slate-500">Nota</div>
        <div class="av3-space-top-1 av3-text-slate-700"><?= e((string)(($fiscalView['notes'] ?? '') !== '' ? $fiscalView['notes'] : '—')) ?></div>
      </div>
    </div>

    <div class="av3-space-top-4 av3-flex av3-flex-wrap av3-items-center av3-gap-2">
      <form method="post" action="<?= url('admin/fiscal/requests/revalidate') ?>" class="av3-inline-flex">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">
        <input type="hidden" name="user_id" value="<?= (int)($fiscalView['uid'] ?? 0) ?>">
        <button type="submit" class="btn-secondary"><span>Revalidar padron</span></button>
      </form>

      <form method="post" action="<?= url('admin/fiscal/requests/approve') ?>" class="av3-inline-flex">
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">
        <input type="hidden" name="user_id" value="<?= (int)($fiscalView['uid'] ?? 0) ?>">
        <button type="submit" class="btn-primary" <?= !empty($fiscalView['can_approve']) ? '' : 'disabled' ?>><span>Aprobar</span></button>
      </form>

      <details class="av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
        <summary class="av3-cursor-pointer av3-type-body av3-weight-strong av3-text-slate-800">Rechazar</summary>
        <form method="post" action="<?= url('admin/fiscal/requests/reject') ?>" class="av3-space-top-3 av3-grid av3-gap-2">
          <?= csrf_field() ?>
          <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">
          <input type="hidden" name="user_id" value="<?= (int)($fiscalView['uid'] ?? 0) ?>">
          <label class="label">Motivo (visible para operativa)</label>
          <textarea class="input" name="reason" rows="2" maxlength="255" placeholder="Ej: CUIT invalido / no es Responsable inscripto / datos inconsistentes"></textarea>
          <div class="av3-flex av3-items-center av3-gap-2">
            <button type="submit" class="btn-danger"><span>Confirmar rechazo</span></button>
          </div>
        </form>
      </details>

      <form method="post" action="<?= url('admin/fiscal/requests/emit-a') ?>" class="av3-inline-flex" <?= !empty($fiscalView['can_emit_a']) ? 'data-fiscal-emit-a' : '' ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="order_id" value="<?= (int)$orderView['id'] ?>">
        <button type="submit" class="btn-primary" <?= !empty($fiscalView['can_emit_a']) ? '' : 'disabled' ?>>
          <span>Emitir Factura A</span>
        </button>
      </form>

      <?php if (!empty($fiscalView['approve_hint'])): ?>
        <div class="av3-type-caption av3-text-slate-500"><?= e((string)$fiscalView['approve_hint']) ?></div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>
