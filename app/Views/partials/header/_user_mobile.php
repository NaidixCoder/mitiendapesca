<?php
$avatarFallbackUrl = (string)asset('img/account/avatar-profile.jpg');
?>
<?php if (!empty($isAuth)): ?>
  <a href="<?= base_url('cuenta') ?>" class="ph-icon-btn ph-avatar" aria-label="Mi cuenta" title="Mi cuenta">
    <?php if (!empty($avatarUrl)): ?>
      <img
        src="<?= e($avatarUrl) ?>"
        alt="avatar"
        width="36"
        height="36"
        referrerpolicy="no-referrer"
        class="ph-avatar-img"
      />
    <?php else: ?>
      <img
        src="<?= e($avatarFallbackUrl) ?>"
        alt="avatar por defecto"
        width="36"
        height="36"
        class="ph-avatar-img"
      />
    <?php endif; ?>
  </a>
<?php else: ?>
  <a href="<?= base_url('login') ?>" class="ph-icon-btn" aria-label="Ingresar" title="Ingresar">
    <?= ui_icon('user', 'ph-icon') ?>
  </a>
<?php endif; ?>
