<section class="av3-card av3-p-0 av3-overflow-hidden">
  <div class="av3-overflow-x-auto">
    <table class="av3-table av3-min-w-full av3-type-body">
      <thead>
        <tr>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">ID</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Nombre</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Email</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Rol</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Verificado</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Alta</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">�ltimo acceso</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Estado</th>
          <th class="av3-pad-x4 av3-pad-y3 av3-text-left">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <?php $showUrl = url('/admin/users/show') . '?id=' . (int)$r['id']; ?>
          <tr class="av3-hover-bg-slate-50">
            <td class="av3-px-0 av3-py-0"><a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><?= (int)$r['id'] ?></a></td>
            <td class="av3-px-0 av3-py-0 av3-weight-medium"><a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><?= e($r['name'] ?? '') ?></a></td>
            <td class="av3-px-0 av3-py-0"><a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><?= e($r['email'] ?? '') ?></a></td>
            <td class="av3-px-0 av3-py-0"><a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><span class="av3-badge neutral"><?= e($r['role'] ?? '') ?></span></a></td>
            <td class="av3-px-0 av3-py-0">
              <?php $v = !empty($r['email_verified_at']); ?>
              <a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><span class="av3-badge <?= $v ?'success' : 'warning' ?>"><?= $v ? 'S�' : 'No' ?></span></a>
            </td>
            <td class="av3-px-0 av3-py-0"><a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><?= e($r['created_short'] ?? $r['created_at'] ?? '') ?></a></td>
            <td class="av3-px-0 av3-py-0"><a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>"><?= e($r['last_login_short'] ?? $r['last_login_at'] ?? '') ?></a></td>
            <td class="av3-px-0 av3-py-0">
              <?php
                $ts = null;
                if (!empty($r['last_activity_at'])) { $ts = strtotime((string)$r['last_activity_at']); }
                elseif (!empty($r['last_login_at'])) { $ts = strtotime((string)$r['last_login_at']); }
                $isOnline = $ts ? (($ts >= time() - 300)) : false;
              ?>
              <a class="av3-block av3-pad-x4 av3-pad-y3" href="<?= e($showUrl) ?>">
                <?php if ($isOnline): ?>
                  <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-bg-emerald-50 av3-border av3-border-emerald-200 av3-text-emerald-700 av3-type-caption">Online</span>
                <?php else: ?>
                  <span class="av3-pad-x2 av3-pad-y1 av3-radius-full av3-bg-slate-50 av3-border av3-border-slate-200 av3-text-slate-600 av3-type-caption">Offline</span>
                <?php endif; ?>
              </a>
            </td>
            <td class="av3-pad-x4 av3-pad-y3">
              <form class="av3-flex av3-items-center av3-gap-2" method="post" action="<?= url('/admin/users/role') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= url('/admin/users') ?>">
                <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                <select class="input" name="role">
                  <?php foreach (($roleOptions ?? ['customer','admin']) as $opt): ?>
                    <option value="<?= e($opt) ?>" <?= (($r['role'] ?? '') === $opt) ? 'selected' : '' ?>><?= e($opt) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn-secondary">Aplicar</button>
              </form>
              <form class="av3-inline-flex av3-items-center" method="post" action="<?= url('/admin/users/force-logout') ?>" onsubmit="return confirm('�Cerrar la sesi�n de este usuario?')">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= url('/admin/users') ?>">
                <input type="hidden" name="user_id" value="<?= (int)$r['id'] ?>">
                <button class="btn-secondary" type="submit">Cerrar sesi�n</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="av3-pad-x4 av3-py-8 av3-text-center av3-text-var-muted">Sin resultados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
