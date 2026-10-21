<?php
$isAuth    = !empty($_SESSION['uid']);
$userName  = $_SESSION['uname'] ?? '';
$initials  = strtoupper(mb_substr($userName, 0, 1, 'UTF-8') ?: 'U');
$cartQty   = (int)($_SESSION['cart_count'] ?? 0);
$avatarUrl = $_SESSION['uavatar'] ?? ($_SESSION['avatar_url'] ?? '');

if ($avatarUrl && str_contains(parse_url($avatarUrl, PHP_URL_HOST) ?? '', 'lh3.googleusercontent.com')) {
    if (!preg_match('/=s\d+-c$/', $avatarUrl)) {
        $avatarUrl .= '=s96-c'; // tamano estandar
    }
}
?>

<header class="ph-header" data-ph-header-root>

    <!-- Mobile: menu/logo/lupa/carrito/avatar -->
    <div class="ph-mobile">
      <div class="ph-mobile-row">
        <?php require BASE_PATH.'/app/Views/partials/header/_menu_mobile.php'; ?>

        <?php require BASE_PATH.'/app/Views/partials/header/_logo_mobile.php'; ?>

        <div class="ph-mobile-actions" aria-label="Accesos rapidos">
          <button
            type="button"
            class="ph-icon-btn"
            data-ph-search-open
            aria-label="Buscar"
            title="Buscar"
          >
            <?= ui_icon('search', 'ph-icon', ['stroke-width' => '1.7']) ?>
          </button>
          <?php $cartIconContext = 'mobile'; ?>
          <?php require BASE_PATH.'/app/Views/partials/header/_cart.php'; ?>
          <?php require BASE_PATH.'/app/Views/partials/header/_user_mobile.php'; ?>
        </div>
      </div>
    </div>

    <?php require BASE_PATH.'/app/Views/partials/header/_mobile_search_sheet.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/header/_mobile_menu_drawer.php'; ?>

    <!-- Desktop -->
    <div class="ph-desktop">
      <?php $logoImgClass = 'ph-logo-img'; ?>
      <?php require BASE_PATH.'/app/Views/partials/header/_logo.php'; ?>

      <?php $searchWrapClass = 'ph-search-form ph-search-form-desktop'; ?>
      <?php $searchSheetTrigger = true; ?>
      <?php require BASE_PATH.'/app/Views/partials/header/_search.php'; ?>

      <nav class="ph-desktop-nav" aria-label="Accesos rapidos">
        <?php $cartIconContext = 'desktop'; ?>
        <?php require BASE_PATH.'/app/Views/partials/header/_cart.php'; ?>
        <?php require BASE_PATH.'/app/Views/partials/header/_user.php'; ?>
      </nav>
    </div>

</header>
