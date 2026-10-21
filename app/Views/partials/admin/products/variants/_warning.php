<!-- Warning / Notice -->
<div class="av3-space-top-3 <?= $hasIncomplete ?'' : 'av3-hidden' ?> av3-radius-xl av3-border av3-border-amber-200 av3-bg-amber-50 av3-pad-4 av3-type-body av3-text-amber-950" data-role="vs-warning">
  <div class="av3-flex av3-items-start av3-gap-3">
    <div class="av3-mt-0_5 av3-flex av3-h-9 av3-w-9 av3-shrink-0 av3-items-center av3-justify-center av3-radius-full av3-border av3-border-amber-200 av3-bg-amber-100 av3-text-amber-800">
      ⚠️
    </div>

    <div class="av3-min-w-0 av3-flex-1">
      <div class="av3-flex av3-flex-col av3-gap-3 av3-sm-flex-row av3-sm-items-start av3-sm-justify-between">
        <div class="av3-min-w-0">
          <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
            <div class="av3-weight-strong av3-text-amber-950">Variantes incompletas</div>
            <span class="av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-amber-200 av3-surface-white av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-amber-900">
              <?= (int)count($incompleteIds) ?>
            </span>
          </div>
          <div class="av3-space-top-1 av3-type-caption av3-leading-relaxed av3-text-amber-900-80" data-role="vs-warning-text">
            Se detectaron variantes creadas antes de agregar opciones de variante requeridas y hoy no tienen todos los valores.
            Recomendado: <b>desactivarlas</b> o <b>eliminarlas</b>.
            <span class="av3-block av3-space-top-1">Si alguna tiene pedidos, por seguridad no se elimina: se <b>pausa</b> automáticamente.</span>
          </div>
        </div>

        <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
          <button type="button" class="btn-secondary" data-role="vs-pause-incomplete" data-ids="<?= e(implode(',', $incompleteIds)) ?>">Desactivar</button>
          <button type="button" class="btn-danger" data-role="vs-delete-incomplete">Eliminar</button>
          <button type="button" class="btn-ghost" data-role="vs-notice-close">Cerrar</button>
        </div>
      </div>
    </div>
  </div>
</div>


<!-- Sobrantes / Obsoletas -->
<div class="av3-space-top-3 <?= (!empty($hasObsolete) ?'' : 'av3-hidden') ?> av3-radius-xl av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-3 av3-type-body av3-text-slate-800" data-role="vs-obsolete-warning">
  <div class="av3-flex av3-flex-col av3-md-flex-row av3-md-items-center av3-md-justify-between av3-gap-3">
    <div>
      🧩 <span class="av3-weight-medium">Hay <?= (int)count((array)($obsoleteIds ?? [])) ?> variante(s) sobrante(s)</span>
      <span class="av3-text-var-muted"> (ya no corresponden a los valores actuales).</span>
      <?php if (!empty($obsoleteWithOrders)): ?>
        <span class="av3-ml-1 av3-inline-flex av3-items-center av3-radius-full av3-border av3-border-slate-200 av3-surface-white av3-pad-x2 av3-pad-yhalf av3-type-micro av3-text-slate-700">Con pedidos: <?= (int)$obsoleteWithOrders ?></span>
      <?php endif; ?>
      <div class="av3-space-top-1 av3-type-caption av3-text-var-muted">
        Recomendado: <b>pausar</b> las sobrantes. Si no tuvieron pedidos, también podés <b>eliminarlas</b>.
      </div>
    </div>

    <div class="av3-flex av3-flex-wrap av3-items-center av3-gap-2">
      <button type="button" class="btn-secondary" data-role="vs-pause-obsolete">Pausar sobrantes</button>
      <button type="button" class="btn-danger" data-role="vs-delete-obsolete">Eliminar sobrantes</button>
      <button type="button" class="btn-secondary" data-role="vs-notice-close">Cerrar</button>
    </div>
  </div>
</div>
