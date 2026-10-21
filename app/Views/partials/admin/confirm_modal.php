<?php
/**
 * Modal de confirmación (reusable) para acciones sensibles en Admin.
 *
 * Se controla 100% desde JS (admin/core/confirm_modal.js).
 */
?>

<div id="confirm-modal" class="av3-fixed inset-0 av3-z--80 av3-hidden" aria-hidden="true">
  <div class="av3-absolute inset-0 av3-bg-black-40"></div>

  <div class="av3-absolute inset-0 av3-flex av3-items-center av3-justify-center av3-pad-4">
    <div class="av3-width-full av3-width-max-lg av3-radius-xl av3-surface-white av3-shadow-xl av3-border av3-border-slate-200">
      <div class="av3-pad-5">
        <div class="av3-flex av3-items-start av3-justify-between av3-gap-3">
          <div class="av3-min-w-0">
            <h3 id="confirm-modal-title" class="av3-type-base av3-weight-strong av3-text-slate-900">Confirmar acción</h3>
            <p id="confirm-modal-subtitle" class="av3-space-top-1 av3-type-body av3-text-slate-600">—</p>
          </div>
          <button type="button" id="confirm-modal-x" class="av3-radius-control av3-pad-2 av3-text-slate-400 av3-hover-text-slate-700 av3-hover-bg-slate-50" aria-label="Cerrar">
            ✕
          </button>
        </div>

        <div class="av3-space-top-4 av3-radius-card av3-border av3-border-slate-200 av3-bg-slate-50 av3-pad-x4 av3-pad-y3">
          <div id="confirm-modal-body" class="av3-type-body av3-text-slate-800 av3-leading-6">—</div>
        </div>

        <div class="av3-space-top-5 av3-flex av3-flex-col-reverse av3-sm-flex-row av3-sm-items-center av3-sm-justify-end av3-gap-2">
          <button type="button" id="confirm-modal-cancel" class="btn-secondary">Cancelar</button>
          <button type="button" id="confirm-modal-alt" class="btn-ghost av3-hidden">Solo aplicar cambios</button>
          <button type="button" id="confirm-modal-ok" class="btn-primary">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
</div>
