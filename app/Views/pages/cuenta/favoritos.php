<?php
$pageTitle = 'Mis favoritos - Mi Cuenta';
$pageDesc = 'Productos guardados en favoritos.';
$items = is_array($items ?? null) ? $items : [];
$cards = is_array($cards ?? null) ? $cards : [];
$categoryOptions = is_array($categoryOptions ?? null) ? $categoryOptions : [];
$selectedCategory = isset($selectedCategory) ? trim((string)$selectedCategory) : '';
$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
$favoritesTotal = count($cards);
$favoritesTotalAll = 0;
foreach ($categoryOptions as $categoryOption) {
    $favoritesTotalAll += (int)($categoryOption['count'] ?? 0);
}
?>

<main>
  <section class="acct-page account-favorites-page">
    <div class="acct-page__inner">
      <div class="acct-page__stack">
        <?php
        section('cuenta/hero', [
            'uname' => $uname,
            'email' => $email,
            'avatarUrl' => $avatarUrl,
            'isAdmin' => $isAdmin,
            'desc' => 'Gestioná tu cuenta y seguí tus compras.',
            'showActions' => true,
            'actionsClass' => 'account-orders-page__actions',
            'actions' => [
                ['href' => url('/cuenta'), 'label' => 'Volver a mi cuenta', 'class' => 'v2-btn v2-btn--ghost'],
                ['href' => url('/productos'), 'label' => 'Explorar productos', 'class' => 'v2-btn v2-btn--primary'],
            ],
        ]);
        ?>

        <div class="account-orders-layout">
          <div class="account-orders-layout__aside">
            <?php
            section('cuenta/aside', [
                'active' => 'favoritos',
                'uname' => $uname,
                'email' => $email,
                'avatarUrl' => $avatarUrl,
                'isAdmin' => $isAdmin,
                'whatsUrl' => $whatsUrl,
            ]);
            ?>
          </div>

          <div class="account-layout-main">
            <section class="account-favorites-header">
              <div class="account-favorites-header__top">
                <div class="account-favorites-header__title-wrap">
                  <span class="account-favorites-header__icon" aria-hidden="true"><?= ui_icon('heart') ?></span>
                  <h2 class="account-favorites-header__title">Tus productos favoritos</h2>
                </div>
                <span class="account-favorites-header__count">
                  <span><?= (int)$favoritesTotal ?> <?= $favoritesTotal === 1 ? 'producto guardado' : 'productos guardados' ?></span>
                </span>
              </div>

              <p class="account-favorites-header__copy">
                Guarda aqui los productos que mas te interesan para encontrarlos rapido, comparar opciones y volver a comprarlos cuando quieras.
              </p>

            </section>
            
            <section class="account-favorites-content">
              <form class="account-favorites-content__filters" method="get" action="<?= e(url('/cuenta/favoritos')) ?>">
                <label class="account-favorites-content__filter">
                  <select name="category" class="account-favorites-content__select" onchange="this.form.submit()">
                    <option value="">Todos (<?= (int)$favoritesTotalAll ?>)</option>
                    <?php foreach ($categoryOptions as $category): ?>
                      <?php
                        $categoryKey = isset($category['key']) ? (string)$category['key'] : '';
                        $categoryLabel = isset($category['label']) ? (string)$category['label'] : '';
                        $categoryCount = (int)($category['count'] ?? 0);
                      ?>
                      <?php if ($categoryKey !== '' && $categoryLabel !== ''): ?>
                        <option value="<?= e($categoryKey) ?>" <?= $selectedCategory === $categoryKey ? 'selected' : '' ?>>
                          <?= e($categoryLabel) ?> (<?= (int)$categoryCount ?>)
                        </option>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </select>
                </label>
              </form>

              <?php if ($cards === []): ?>
                <article class="acct-panel">
                  <div class="acct-panel__body">
                    <div class="acct-empty">
                      Todavia no tenes favoritos.
                      <a href="<?= e(url('/productos')) ?>" class="acct-empty__link">Explorar productos</a>
                    </div>
                  </div>
                </article>
              <?php else: ?>
                <?php section('partials/catalogo/product-grid', ['products' => $cards]); ?>
              <?php endif; ?>

            </section>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>
