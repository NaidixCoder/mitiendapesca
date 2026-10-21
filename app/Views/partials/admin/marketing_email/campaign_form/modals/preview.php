<div id="email-preview-modal" class="av3-email-composer__modal is-hidden" aria-hidden="true">
  <div class="av3-email-composer__backdrop" data-modal-close></div>
  <section class="av3-email-composer__dialog av3-email-composer__dialog--preview" role="dialog" aria-modal="true" aria-labelledby="email-preview-heading">
    <header class="av3-email-composer__dialog-head">
      <div><h2 id="email-preview-heading">Vista previa</h2><p>Revisá el contenido antes de programar el envío.</p></div>
      <div class="av3-email-composer__dialog-actions">
        <button type="button" class="av3-ui-btn" id="email-preview-refresh">Actualizar</button>
        <button type="button" class="av3-ui-btn" data-modal-close>Cerrar</button>
      </div>
    </header>
    <div class="av3-email-composer__preview-options">
      <div class="av3-email-composer__tabs" aria-label="Ancho de la vista previa">
        <button type="button" data-preview-size="desktop" class="is-active">Escritorio</button>
        <button type="button" data-preview-size="mobile">Móvil</button>
      </div>
      <span id="email-preview-status" role="status" aria-live="polite">—</span>
    </div>
    <div class="av3-email-composer__inbox">
      <strong>Simulación de bandeja de entrada</strong>
      <div class="av3-email-composer__inbox-message">
        <strong id="email-modal-inbox-subject">—</strong>
        <p id="email-modal-inbox-preheader">—</p>
      </div>
    </div>
    <div class="av3-email-composer__preview-body">
      <div id="email-preview-wrap" class="av3-email-composer__preview-wrap">
        <iframe id="email-preview" title="Vista previa email" sandbox="" referrerpolicy="no-referrer"></iframe>
      </div>
    </div>
  </section>
</div>
