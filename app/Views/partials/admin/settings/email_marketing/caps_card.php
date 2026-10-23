<section id="ajustes-frecuencia" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Evitar molestias y medir interés</h2>
  <p class="av3-type-body av3-text-var-muted">Evita saturar a los clientes y, si querés, registra las aperturas de forma aproximada.</p>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input type="checkbox" name="open_tracking" value="1" class="av3-space-top-1" <?= !empty($open_tracking_checked) ? 'checked' : '' ?>>
    <div>
      <div class="av3-weight-medium">Contar aperturas (aprox.)</div>
      <p class="av3-type-body av3-text-var-muted">Cuenta una apertura cuando el cliente carga imágenes. Algunos clientes de correo bloquean las imágenes, por lo que la medición es aproximada.</p>
    </div>
  </label>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Máximo de correos por usuario cada 24 horas</div><input class="input" type="number" name="cap_per_day" min="0" max="20" value="<?= e((string)($cfg['cap_per_day'] ?? '')) ?>" placeholder="Ej.: 2"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">0 = sin límite (no recomendado).</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Máximo de correos por usuario cada 7 días</div><input class="input" type="number" name="cap_per_week" min="0" max="100" value="<?= e((string)($cfg['cap_per_week'] ?? '')) ?>" placeholder="Ej.: 6"><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Se aplica a boletines, automatizaciones y campañas.</p></label>
  </div>
</section>
