<section id="ajustes-runner" class="av3-ui-card av3-settings-detail__panel av3-flow-3">
  <h2 class="av3-type-base av3-weight-strong">Ejecutar envíos desde la web (Plan B)</h2>
  <p class="av3-type-body av3-text-var-muted">Si no tenés ejecución automática en el servidor, podés disparar el envío desde un enlace privado.</p>

  <label class="av3-flex av3-items-start av3-gap-3">
    <input type="checkbox" name="web_runner_enabled" value="1" class="av3-space-top-1" <?= !empty($web_runner_enabled_checked) ? 'checked' : '' ?>>
    <div>
      <div class="av3-weight-medium">Habilitar ejecución por enlace privado</div>
      <p class="av3-type-body av3-text-var-muted">Permite ejecutar el procesamiento de correos desde un enlace privado. Tiene un límite para evitar abusos.</p>
    </div>
  </label>

  <div class="av3-ui-card-body av3-flow-2">
    <p class="av3-type-body">Estado de clave: <strong><?= !empty($web_runner_configured) ? 'Configurada en el servidor' : 'Pendiente de configurar' ?></strong></p>
    <p class="av3-type-body av3-text-var-muted">Ruta sin credenciales: <code><?= e((string)($run_url ?? '')) ?></code>. La clave y la URL firmada no se imprimen en el navegador, registros ni respuestas HTTP.</p>
    <p class="av3-type-body av3-text-var-muted">Generá o rotá la clave desde la consola del servidor con el aprovisionador privado. Conservá el archivo resultante fuera de la carpeta pública y no lo compartas.</p>
  </div>
</section>
