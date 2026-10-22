<?php
/** @var array<string,mixed> $r */
$active = (int)($r['is_active'] ?? 0) === 1;
$affected = (int)($r['child_count'] ?? 0);
$confirm = $active
  ? '¿Desactivar esta categoría?'.($affected > 0 ? ' También se desactivarán '.$affected.' subcategorías directas.' : '').' Los productos seguirán asociados.'
  : '¿Activar esta categoría? Sus subcategorías permanecerán con su estado actual.';
?>
<form class="av3-categories__toggle" method="post" action="<?= url('/admin/categories/toggle') ?>" data-categories-confirm="<?= e($confirm) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
  <input type="hidden" name="next" value="<?= e($nextReturn ?? url('/admin/categories')) ?>">
  <button class="av3-ui-btn <?= $active ? 'av3-ui-btn--danger' : '' ?>" type="submit"><?= $active ? 'Desactivar' : 'Activar' ?></button>
</form>
