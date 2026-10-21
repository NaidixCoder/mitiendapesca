<?php
/**
 * Overlay de proceso (Admin) para operaciones que demoran (ARCA emisión/NC, etc.).
 *
 * Se controla desde JS: public_html/assets/js/admin/core/processing_overlay.js
 */
?>

<div id="admin-processing-overlay" class="av3-hidden av3-fixed inset-0 av3-z--90 av3-bg-black-30" aria-hidden="true">
  <div class="av3-absolute inset-0 av3-flex av3-items-center av3-justify-center av3-pad-4">
    <div class="av3-width-full av3-max-w-md av3-radius-xl av3-surface-white av3-pad-6 av3-shadow-lg av3-border av3-border-gray-100">
      <div class="av3-flex av3-items-start av3-gap-3">
        <div id="admin-processing-spinner" class="av3-space-top-1 av3-size-h-5 av3-size-w-5 av3-radius-full av3-border-2 av3-border-gray-300 av3-border-t-gray-900 av3-animate-spin"></div>
        <div id="admin-processing-icon" class="av3-hidden av3-space-top-1 av3-size-h-5 av3-size-w-5 av3-radius-full av3-flex av3-items-center av3-justify-center av3-type-caption av3-font-bold av3-border"></div>
        <div>
          <div id="admin-processing-title" class="av3-type-base av3-weight-strong av3-text-gray-900">Procesando...</div>
          <div id="admin-processing-text" class="av3-type-body av3-text-gray-600 av3-space-top-1">No cierres esta ventana.</div>
        </div>
      </div>
      <div id="admin-processing-hint" class="av3-space-top-4 av3-type-caption av3-text-gray-500">
        Esto puede demorar unos segundos, dependiendo de ARCA.
      </div>

      <div id="admin-processing-actions" class="av3-hidden av3-space-top-5 av3-flex av3-justify-end">
        <button type="button" id="admin-processing-ok" class="av3-inline-flex av3-items-center av3-justify-center av3-radius-card av3-bg-gray-900 av3-pad-x4 av3-pad-y2 av3-type-body av3-weight-strong av3-text-white av3-hover-bg-gray-800">
          OK
        </button>
      </div>
    </div>
  </div>
</div>
