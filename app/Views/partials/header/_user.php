<?php
$avatarFallbackUrl = (string)asset('img/account/avatar-profile.jpg');
?>
<?php if (!empty($isAuth)): ?>
  <div class="ph-auth-root" x-auth-menu>
    <button
      class="ph-auth-trigger"
      type="button"
      data-auth-trigger
      aria-haspopup="menu"
      aria-expanded="false"
      aria-label="Abrir menu de usuario"
      title="Mi cuenta"
    >
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
      <?= ui_icon('chevron-down', 'ph-auth-caret') ?>
    </button>

    <div class="ph-auth-menu" data-auth-menu role="menu" aria-label="Menu de usuario" hidden>
      <div class="ph-auth-greeting">Hola, <?= e($userName !== '' ? $userName : 'Usuario') ?></div>

      <div class="ph-auth-divider" aria-hidden="true"></div>

      <a class="ph-auth-item" href="<?= base_url('cuenta') ?>" role="menuitem">Mi cuenta</a>
      <a class="ph-auth-item" href="<?= base_url('cuenta/favoritos') ?>" role="menuitem">Mis favoritos</a>
      <a class="ph-auth-item" href="<?= base_url('cuenta/pedidos') ?>" role="menuitem">Mis pedidos</a>
      <a class="ph-auth-item" href="<?= base_url('cuenta/lealtad') ?>" role="menuitem">Programa de lealtad</a>

      <div class="ph-auth-divider" aria-hidden="true"></div>

      <a class="ph-auth-item" href="<?= base_url('ayuda') ?>" role="menuitem">Ayuda</a>

      <?php if (!empty($_SESSION['is_admin']) || (($_SESSION['urole'] ?? '') === 'admin')): ?>
        <div class="ph-auth-divider" aria-hidden="true"></div>
        <a class="ph-auth-item" href="<?= base_url('admin') ?>" role="menuitem">Admin</a>
      <?php endif; ?>

      <div class="ph-auth-divider" aria-hidden="true"></div>

      <form action="<?= base_url('logout') ?>" method="post" class="ph-auth-logout-form">
        <?= csrf_field() ?>
        <button type="submit" class="ph-auth-item ph-auth-item-danger" role="menuitem">Cerrar sesion</button>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="ph-auth-guest">
    <a href="<?= base_url('login') ?>" class="ph-auth-link">Ingresar</a>
    <a href="<?= base_url('registro') ?>" class="ph-auth-link">Crear cuenta</a>
  </div>
<?php endif; ?>
