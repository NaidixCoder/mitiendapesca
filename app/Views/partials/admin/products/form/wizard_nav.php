<div class="admin-wizard-nav" id="wizard-nav">
  <button type="button" class="btn-secondary" data-wizard="prev">← Anterior</button>
  <div class="av3-flex-1"></div>
  <?php if ($activeTab !== 'publicacion'): ?>
    <button type="button" class="btn-primary" data-wizard="next">Siguiente →</button>
  <?php else: ?>
    <button type="button" class="btn-primary" data-wizard="next" hidden aria-hidden="true">Siguiente →</button>
  <?php endif; ?>
</div>
