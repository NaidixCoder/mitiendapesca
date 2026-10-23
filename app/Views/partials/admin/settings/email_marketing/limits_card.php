<section id="ajustes-limites" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Cuánto envía por tanda</h2>
  <p class="av3-type-body av3-text-var-muted">Controla cuántos emails se preparan y se envían por tanda. Si lo dejás vacío, se usa el valor del <code>.env</code>.</p>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Máximo a preparar por tanda</div><input class="input" type="number" name="sched_limit" min="10" max="2000" value="<?= e((string)($cfg['sched_limit'] ?? '')) ?>" placeholder="Ej: 200"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Límite para preparar envíos.</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Máximo de envíos masivos por tanda</div><input class="input" type="number" name="broadcast_limit" min="10" max="2000" value="<?= e((string)($cfg['broadcast_limit'] ?? '')) ?>" placeholder="Ej: 200"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Límite específico para envíos masivos.</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Máximo a enviar por tanda</div><input class="input" type="number" name="work_limit" min="1" max="500" value="<?= e((string)($cfg['work_limit'] ?? '')) ?>" placeholder="Ej: 25"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Límite de envíos por tanda.</p></label>
  </div>
</section>
