<section class="av3-card av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Ejecutar envíos desde la web (Plan B)</h2>
  <p class="av3-type-body av3-text-var-muted">Si no tenés ejecución automática en el servidor, podés disparar el envío desde un enlace privado.</p>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input type="checkbox" name="web_runner_enabled" value="1" class="av3-space-top-1" <?= !empty($web_runner_enabled_checked) ? 'checked' : '' ?>>
    <div>
      <div class="av3-weight-medium">Habilitar ejecución por enlace privado</div>
      <p class="av3-type-body av3-text-var-muted">Permite ejecutar el procesamiento de correos desde un enlace privado. Tiene un límite para evitar abusos.</p>
    </div>
  </label>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Clave privada (no compartir)</div><input class="input" type="text" name="web_runner_token" value="<?= e((string)($cfg['web_runner_token'] ?? '')) ?>" readonly><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Se genera automáticamente al habilitar esta opción o al regenerar la clave. No la compartas.</p></label>
    <label class="av3-block"><div class="av3-type-body av3-weight-medium av3-space-bottom-1">Enlace privado (copiar)</div><input class="input" type="text" value="<?= e((string)($run_url ?? '')) ?>" readonly><p class="av3-type-caption av3-text-var-muted av3-space-top-1">Devuelve un resumen de los correos procesados.</p></label>
  </div>

  <div>
    <button type="submit" name="web_runner_regenerate" value="1" class="btn-secondary">Generar nueva clave</button>
    <p class="av3-type-caption av3-text-var-muted av3-space-top-2">Al generar una nueva clave, el enlace anterior deja de funcionar.</p>
  </div>
</section>
