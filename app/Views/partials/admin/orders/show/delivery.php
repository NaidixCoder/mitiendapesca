<section class="av3-card">
  <header class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
    <h4 class="av3-weight-strong">Cliente y entrega</h4>
    <div class="av3-type-caption av3-text-slate-500">Snapshot del checkout</div>
  </header>

  <div class="av3-grid av3-gap-4 av3-md-grid-cols-2">
    <div>
      <div class="av3-type-caption av3-text-slate-500 av3-space-bottom-1">Cliente</div>
      <div class="av3-flow-2">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
          <div class="av3-min-w-0">
            <div class="av3-type-body av3-weight-strong av3-truncate"><?= e($customerLabel !== '' ? $customerLabel : '—') ?></div>
            <div class="av3-type-caption av3-text-slate-500">Nombre</div>
          </div>
          <button class="btn-ghost" type="button" data-copy="<?= e($customerLabel) ?>">Copiar</button>
        </div>

        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
          <div class="av3-min-w-0">
            <div class="av3-type-body av3-weight-strong av3-truncate"><?= e($shipEmail !== '' ? $shipEmail : '—') ?></div>
            <div class="av3-type-caption av3-text-slate-500">Email</div>
          </div>
          <button class="btn-ghost" type="button" data-copy="<?= e($shipEmail) ?>" <?= $shipEmail !== '' ? '' : 'disabled' ?>>Copiar</button>
        </div>

        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
          <div class="av3-min-w-0">
            <div class="av3-type-body av3-weight-strong av3-truncate"><?= e($shipPhone !== '' ? $shipPhone : '—') ?></div>
            <div class="av3-type-caption av3-text-slate-500">Telefono</div>
          </div>
          <button class="btn-ghost" type="button" data-copy="<?= e($shipPhone) ?>" <?= $shipPhone !== '' ? '' : 'disabled' ?>>Copiar</button>
        </div>
      </div>
    </div>

    <div>
      <div class="av3-type-caption av3-text-slate-500 av3-space-bottom-1">Entrega</div>
      <div class="av3-flow-2">
        <div class="av3-flex av3-items-center av3-justify-between av3-gap-2 av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
          <div class="av3-min-w-0">
            <div class="av3-type-body av3-weight-strong"><?= e($shipAddress !== '' ? $shipAddress : '—') ?></div>
            <div class="av3-type-caption av3-text-slate-500">Direccion</div>
          </div>
          <button class="btn-ghost" type="button" data-copy="<?= e($shipAddress) ?>" <?= $shipAddress !== '' ? '' : 'disabled' ?>>Copiar</button>
        </div>

        <div class="av3-radius-control av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2">
          <div class="av3-type-caption av3-text-slate-500 av3-space-bottom-1">Observaciones</div>
          <div class="av3-type-body"><?= e($shipNotes !== '' ? $shipNotes : '—') ?></div>
        </div>

        <?php if (!$hasDeliveryData): ?>
          <div class="av3-radius-control av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-x3 av3-pad-y2 av3-type-body av3-text-amber-800">
            Datos de entrega incompletos en el pedido.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
