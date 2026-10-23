<div class="av3-ui-card av3-p-0 av3-space-bottom-4">
  <div class="av3-tabs">
    <a class="av3-tab" href="<?= base_url('/admin/audit') ?>">Actividad</a>
    <a class="av3-tab active" href="<?= base_url('/admin/audit/logins') ?>">Logins</a>
  </div>

  <form class="av3-filters" method="get" action="<?= base_url('/admin/audit/logins') ?>">
    <div class="av3-filters__row">
      <label class="av3-field">
        <span>Evento</span>
        <select name="event">
          <option value="todos" <?= $event==='todos'?'selected':'' ?>>Todos</option>
          <option value="login_success" <?= $event==='login_success'?'selected':'' ?>>Login exitoso</option>
          <option value="login_failed" <?= $event==='login_failed'?'selected':'' ?>>Login fallido</option>
          <option value="logout" <?= $event==='logout'?'selected':'' ?>>Logout</option>
        </select>
      </label>

      <label class="av3-field">
        <span>Metodo</span>
        <select name="method">
          <option value="todos" <?= $method==='todos'?'selected':'' ?>>Todos</option>
          <option value="password" <?= $method==='password'?'selected':'' ?>>Contrasena</option>
          <option value="google" <?= $method==='google'?'selected':'' ?>>Google</option>
        </select>
      </label>

      <label class="av3-field">
        <span>Rango</span>
        <select name="range">
          <option value="7" <?= $range==='7'?'selected':'' ?>>Ultimos 7 dias</option>
          <option value="30" <?= $range==='30'?'selected':'' ?>>Ultimos 30 dias</option>
          <option value="90" <?= $range==='90'?'selected':'' ?>>Ultimos 90 dias</option>
          <option value="all" <?= $range==='all'?'selected':'' ?>>Todo</option>
        </select>
      </label>

      <label class="av3-field av3-field--grow">
        <span>Buscar</span>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Email / nombre / IP / motivo..." />
      </label>

      <label class="av3-field">
        <span>Por pagina</span>
        <select name="per_page">
          <?php foreach ([25,50,100] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage===$n?'selected':'' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="av3-field av3-field--actions">
        <span>&nbsp;</span>
        <button class="btn btn-primary" type="submit">Filtrar</button>
        <a class="btn btn-ghost" href="<?= base_url('/admin/audit/logins') ?>">Limpiar</a>
      </div>
    </div>
  </form>

  <div class="av3-filters av3-audit-maintenance">
    <div class="av3-filters__row">
      <div class="av3-field av3-field--grow">
        <span>Mantenimiento</span>
        <div class="muted small">
          Retencion actual: login exitoso <?= (int)$retSuccess ?>d • login fallido <?= (int)$retFailed ?>d • logout <?= (int)$retLogout ?>d
        </div>
      </div>

      <form class="av3-audit-cleanup" method="post" action="<?= base_url('/admin/audit/logins/cleanup') ?>">
        <?php csrf_field(); ?>
        <input type="hidden" name="_back" value="<?= h($currentUri) ?>">
        <div class="av3-field">
          <span>&nbsp;</span>
          <label class="muted">
            <input type="checkbox" name="confirm" value="1" required>
            Confirmo la eliminación irreversible de eventos de acceso anteriores a los plazos de retención
          </label>
        </div>
        <button class="btn btn-danger" type="submit">Ejecutar limpieza</button>
      </form>
    </div>
  </div>
</div>
