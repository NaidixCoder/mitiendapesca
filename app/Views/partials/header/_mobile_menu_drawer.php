<?php
$isAuth = !empty($_SESSION['uid']);
$role = (string)($_SESSION['urole'] ?? ($_SESSION['role'] ?? ''));
$isAdmin = ($role === 'admin') || !empty($_SESSION['is_admin']);
$userName = trim((string)($_SESSION['uname'] ?? ''));
$avatarUrl = trim((string)($_SESSION['uavatar'] ?? ($_SESSION['avatar_url'] ?? '')));
$avatarFallbackUrl = (string)asset('img/account/avatar-profile.jpg');
$initials = strtoupper((string)(function_exists('mb_substr') ? mb_substr($userName, 0, 1, 'UTF-8') : substr($userName, 0, 1)));
if ($initials === '') {
  $initials = 'U';
}
$uriPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
$basePrefix = rtrim((string)base_url(''), '/');
if ($basePrefix !== '' && str_starts_with($uriPath, $basePrefix)) {
  $uriPath = substr($uriPath, strlen($basePrefix));
}
$uriPath = '/' . ltrim($uriPath, '/');
$isPath = static function(string $prefix) use ($uriPath): bool {
  return $uriPath === $prefix || str_starts_with($uriPath, $prefix . '/');
};
$linkClass = static function(bool $active): string {
  return 'ph-menu-link' . ($active ? ' is-active' : '');
};
?>
<div class="ph-menu-overlay" data-ph-menu-overlay hidden></div>

<aside id="ph-mobile-menu-drawer" class="ph-menu-drawer" data-ph-menu-drawer hidden aria-hidden="true" aria-label="Menu principal">
  <div class="ph-menu-head">
    
    <!-- LOGO -->
    <img src="<?= asset('img/branding/miPescaYaventura-leyenda-black.png') ?>" alt="Mi Pesca & Aventura" class="ph-isologo" />
    
    <button type="button" class="ph-icon-btn ph-menu-close" data-ph-menu-close aria-label="Cerrar menu" title="Cerrar">
      <?= ui_icon('close', 'ph-icon') ?>
    </button>

  </div>

  <?php if ($isAuth): ?>
    <section class="ph-menu-user">
      <div class="ph-menu-user-avatar">
        <?php if ($avatarUrl !== ''): ?>
          <img src="<?= e($avatarUrl) ?>" alt="avatar" width="36" height="36" class="ph-avatar-img" referrerpolicy="no-referrer" />
        <?php else: ?>
          <img src="<?= e($avatarFallbackUrl) ?>" alt="avatar por defecto" width="36" height="36" class="ph-avatar-img" />
        <?php endif; ?>
      </div>
      <div class="ph-menu-user-main">
        <p class="ph-menu-user-greeting">Hola, <?= e($userName !== '' ? $userName : 'pescador') ?></p>
        <a class="ph-menu-user-link" href="<?= base_url('/cuenta') ?>">Mi cuenta</a>
      </div>
    </section>
  <?php else: ?>
    <section class="ph-menu-auth-cta">
      <a class="ph-menu-auth-btn" href="<?= base_url('/login') ?>">Ingresar</a>
      <a class="ph-menu-auth-btn is-secondary" href="<?= base_url('/registro') ?>">Crear cuenta</a>
    </section>
  <?php endif; ?>

  <section class="ph-menu-search">
    <form method="get" action="<?= base_url('/productos') ?>" class="ph-menu-search-form">
      <input type="text" name="q" class="ph-menu-search-input" placeholder="Buscar productos..." autocomplete="off" />
      <button type="submit" class="ph-menu-search-btn" aria-label="Buscar">
        <?= ui_icon('search', 'ph-icon', ['stroke-width' => '1.7']) ?>
      </button>
    </form>
  </section>

  <div class="ph-menu-sep"></div>

  <section class="ph-menu-group">
    <h3 class="ph-menu-group-title">Catalogo</h3>
    <nav class="ph-menu-nav" aria-label="Navegacion catalogo movil">
      <a class="<?= e($linkClass($isPath('/productos'))) ?>" href="<?= base_url('/productos') ?>">Productos</a>
      <a class="<?= e($linkClass($isPath('/categorias'))) ?>" href="<?= base_url('/categorias') ?>">Categorias</a>
      <a class="<?= e($linkClass($isPath('/marcas'))) ?>" href="<?= base_url('/marcas') ?>">Marcas</a>
    </nav>
  </section>

  <div class="ph-menu-sep"></div>

  <section class="ph-menu-group">
    <h3 class="ph-menu-group-title">Soporte</h3>
    <nav class="ph-menu-nav" aria-label="Navegacion soporte movil">
      <a class="<?= e($linkClass($isPath('/ayuda'))) ?>" href="<?= base_url('/ayuda') ?>">Ayuda</a>
      <a class="<?= e($linkClass($isPath('/contacto'))) ?>" href="<?= base_url('/ayuda') ?>">Contacto</a>
    </nav>
  </section>

  <div class="ph-menu-grow"></div>

  <section class="ph-menu-group">
    <h3 class="ph-menu-group-title">Cuenta</h3>
    <nav class="ph-menu-nav" aria-label="Navegacion de cuenta movil">
    <?php if ($isAuth): ?>
      <a class="<?= e($linkClass($isPath('/cuenta'))) ?>" href="<?= base_url('/cuenta') ?>">Mi cuenta</a>
      <a class="<?= e($linkClass($isPath('/cuenta/favoritos'))) ?>" href="<?= base_url('/cuenta/favoritos') ?>">Mis favoritos</a>
      <a class="<?= e($linkClass($isPath('/cuenta/pedidos'))) ?>" href="<?= base_url('/cuenta/pedidos') ?>">Mis pedidos</a>
      <a class="<?= e($linkClass($isPath('/carrito'))) ?>" href="<?= base_url('/carrito') ?>">Carrito</a>
      <?php if ($isAdmin): ?>
        <a class="<?= e($linkClass($isPath('/admin'))) ?>" href="<?= base_url('/admin') ?>">Admin</a>
      <?php endif; ?>
      <form method="post" action="<?= base_url('/logout') ?>" class="ph-menu-form">
        <?= csrf_field() ?>
        <button type="submit" class="ph-menu-link ph-menu-link-btn is-danger">Cerrar sesion</button>
      </form>
    <?php else: ?>
      <a class="<?= e($linkClass($isPath('/login'))) ?>" href="<?= base_url('/login') ?>">Ingresar</a>
      <a class="<?= e($linkClass($isPath('/registro'))) ?>" href="<?= base_url('/registro') ?>">Crear cuenta</a>
    <?php endif; ?>
    </nav>
  </section>
</aside>
