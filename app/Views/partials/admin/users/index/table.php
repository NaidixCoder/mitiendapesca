<?php
$me = (int)($_SESSION['uid'] ?? 0);
?>
<section class="av3-ui-card">
  <div class="av3-ui-card-body av3-users__table-wrap">
    <p class="av3-users__summary"><?= (int)$total ?> usuario(s) encontrados · página <?= (int)$page ?> de <?= (int)$pages ?></p>
    <table class="av3-ui-table av3-users__table">
      <thead><tr><th>Usuario</th><th>Rol</th><th>Verificado</th><th>Último acceso</th><th>Acciones</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <?php $id=(int)$r['id']; $self=$id===$me; $urlShow=url('/admin/users/show?id='.$id); ?>
        <tr>
          <td data-label="Usuario"><a href="<?= e($urlShow) ?>"><?= e((string)($r['name'] ?? 'Sin nombre')) ?></a><div class="av3-users__email"><?= e((string)($r['email'] ?? '')) ?></div><div class="av3-users__meta">#<?= $id ?></div></td>
          <td data-label="Rol"><span class="av3-ui-badge av3-ui-badge--neutral"><?= e((string)$r['role']) ?></span></td>
          <td data-label="Verificado"><?= !empty($r['email_verified']) ? 'Sí' : 'No' ?></td>
          <td data-label="Último acceso"><?= e((string)($r['last_login_at'] ?: 'Sin accesos')) ?></td>
          <td>
            <div class="av3-users__actions">
              <?php if (!$self): ?>
              <form method="post" action="<?= e(url('/admin/users/role')) ?>" onsubmit="return confirm('¿Confirmar cambio de rol?')">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="/admin/users">
                <input type="hidden" name="user_id" value="<?= $id ?>">
                <label class="av3-ui-field-label" for="user-role-<?= $id ?>">Rol</label>
                <select class="av3-ui-control" name="role" id="user-role-<?= $id ?>">
                  <?php foreach (array_unique(array_merge($roleOptions, [(string)$r['role']])) as $opt): ?>
                  <option value="<?= e($opt) ?>" <?= $opt===(string)$r['role']?'selected':'' ?>><?= e($opt) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="av3-ui-btn av3-ui-btn--small">Aplicar</button>
              </form>
              <form method="post" action="<?= e(url('/admin/users/force-logout')) ?>" onsubmit="return confirm('¿Invalidar todas las sesiones de este usuario?')">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="/admin/users">
                <input type="hidden" name="user_id" value="<?= $id ?>">
                <button type="submit" class="av3-ui-btn av3-ui-btn--small">Cerrar sesiones</button>
              </form>
              <?php else: ?><span class="av3-users__meta">Tu cuenta · acciones protegidas</span><?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="5" class="av3-ui-empty">No hay usuarios que coincidan con la búsqueda.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <nav class="av3-users__pagination" aria-label="Paginación de usuarios">
    <span class="av3-users__meta">Página <?= (int)$page ?> / <?= (int)$pages ?></span>
    <div class="av3-ui-actions">
      <?php if ($page > 1): ?><a class="av3-ui-btn" href="<?= e(url('/admin/users?page='.($page-1).'&q='.rawurlencode($q))) ?>">Anterior</a><?php endif; ?>
      <?php if ($page < $pages): ?><a class="av3-ui-btn" href="<?= e(url('/admin/users?page='.($page+1).'&q='.rawurlencode($q))) ?>">Siguiente</a><?php endif; ?>
    </div>
  </nav>
</section>
