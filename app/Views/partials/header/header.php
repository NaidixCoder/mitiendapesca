<?php
$isAuth    = !empty($_SESSION['uid']);
$userName  = $_SESSION['uname'] ?? '';
$initials  = strtoupper(mb_substr($userName, 0, 1, 'UTF-8') ?: 'U');
$cartQty   = (int)($_SESSION['cart_count'] ?? 0);
$avatarUrl = $_SESSION['uavatar'] ?? ($_SESSION['avatar_url'] ?? '');
if ($avatarUrl && str_contains(parse_url($avatarUrl, PHP_URL_HOST) ?? '', 'lh3.googleusercontent.com')) {
    if (!preg_match('/=s\d+-c$/', $avatarUrl)) {
        $avatarUrl .= '=s96-c'; // tamaño estándar
    }
}

?>

<header class="bg-brand text-white sticky top-0 z-50 shadow-md border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 md:px-8 py-4 flex flex-col md:flex-row items-center justify-between gap-4">

    <!-- Logo -->
    <?php require BASE_PATH.'/app/Views/partials/header/_logo.php'; ?>

    <!-- Buscador -->
    <?php require BASE_PATH.'/app/Views/partials/header/_search.php'; ?>

    <!-- Accesos rápidos -->
    <nav class="flex items-center gap-4" aria-label="Accesos rápidos">
      <?php require BASE_PATH.'/app/Views/partials/header/_cart.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/header/_user.php'; ?>
    </nav>

  </div>
</header>
