<?php
$u=is_array($show['user']??null)?$show['user']:[];
$uid=(int)($u['id']??0);
$self=$uid===(int)($_SESSION['uid']??0);
$links=is_array($show['links']??null)?$show['links']:[];
?>
<section class="av3-ui-card">
<div class="av3-ui-card-header"><h2 class="av3-ui-card-title">Acciones administrativas</h2></div>
<div class="av3-ui-card-body av3-users__stack">
  <a class="av3-ui-btn" href="<?= e($links['orders_by_user']??url('/admin/orders?user_id='.$uid)) ?>">Ver pedidos</a>
  <a class="av3-ui-btn" href="<?= e($links['audit_by_user']??url('/admin/audit?tipo=user&q=%23'.$uid)) ?>">Ver auditoría</a>
  <form method="post" action="<?= e(url('/admin/users/rebuild-stats')) ?>" onsubmit="return confirm('¿Recalcular las estadísticas de este usuario?')">
    <?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= $uid ?>">
    <button class="av3-ui-btn" type="submit">Recalcular estadísticas</button>
  </form>
  <?php if (!$self): ?>
    <form method="post" action="<?= e(url('/admin/users/force-logout')) ?>" onsubmit="return confirm('¿Invalidar todas las sesiones de esta cuenta?')">
      <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $uid ?>"><input type="hidden" name="next" value="/admin/users/show?id=<?= $uid ?>">
      <button type="submit" class="av3-ui-btn av3-ui-btn--danger">Cerrar sesiones</button>
    </form>
  <?php else: ?><p class="av3-users__meta">Las acciones sobre tu propia cuenta están protegidas.</p><?php endif; ?>
</div>
</section>
