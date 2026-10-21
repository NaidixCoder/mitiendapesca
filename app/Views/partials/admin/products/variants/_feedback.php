<?php
// Contenedor de feedback persistente + modal confirm reutilizable.
// Reglas: sin JS inline; JS solo setea textos y muestra/oculta.
?>

<!-- Banner persistente (para warnings/errores importantes) -->
<div class="av3-space-top-3 av3-hidden pv-alert pv-alert--danger" data-role="vs-feedback" role="status" aria-live="polite">
  <div class="pv-alert__icon" aria-hidden="true">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
      <path d="M12 9v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      <path d="M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="2"/>
    </svg>
  </div>

  <div class="av3-min-w-0 av3-flex-1">
    <div class="pv-alert__title" data-role="vs-feedback-title">Aviso</div>
    <div class="pv-alert__text" data-role="vs-feedback-text"></div>
  </div>

  <button type="button" class="btn-outline-muted" data-role="vs-feedback-close">Cerrar</button>
</div>

<!-- Modal confirm (bloqueante) -->
<div class="av3-hidden av3-fixed inset-0 av3-z--10000" data-role="vs-confirm" aria-hidden="true">
  <div class="av3-absolute inset-0 av3-bg-black-40" data-role="vs-confirm-backdrop"></div>

  <div class="av3-relative av3-mx-auto av3-flex min-h-full av3-max-w--560px av3-items-center av3-justify-center av3-pad-4">
    <div class="av3-width-full av3-radius-xl av3-border av3-border-slate-200 av3-surface-white av3-pad-4 av3-shadow-xl">
      <div class="av3-type-base av3-weight-strong av3-text-slate-900" data-role="vs-confirm-title">Confirmar</div>
      <div class="av3-space-top-1 av3-type-body av3-text-var-muted" data-role="vs-confirm-text"></div>

      <div class="av3-space-top-3 av3-hidden" data-role="vs-confirm-input-wrap">
        <label class="av3-type-caption av3-weight-medium av3-text-slate-700" data-role="vs-confirm-input-label">Confirmación</label>
        <input class="av3-space-top-1 av3-width-full av3-radius-card av3-border av3-border-slate-200 av3-surface-white av3-pad-x3 av3-pad-y2 av3-type-body"
               data-role="vs-confirm-input" placeholder="" autocomplete="off" />
        <div class="av3-space-top-1 av3-type-caption av3-text-var-muted" data-role="vs-confirm-input-hint"></div>
      </div>

      <div class="av3-space-top-4 av3-flex av3-flex-wrap av3-items-center av3-justify-end av3-gap-2">
        <button type="button" class="btn-secondary" data-role="vs-confirm-cancel">Cancelar</button>
        <button type="button" class="btn-danger" data-role="vs-confirm-ok">Confirmar</button>
      </div>
    </div>
  </div>
</div>
