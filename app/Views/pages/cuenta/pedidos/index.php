<?php
/** @var array<int, array<string,mixed>> $orders */
$orders = $orders ?? [];
$hasOrders = (bool)($hasOrders ?? false);

$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));

$statusTone = static function (string $status): string {
    return match ($status) {
        'delivered', 'paid' => 'ok',
        'processing', 'packed', 'shipped' => 'info',
        'pending_payment', 'pending', 'payment_failed', 'payment_expired' => 'warn',
        'cancelled', 'refunded', 'chargeback' => 'danger',
        default => 'neutral',
    };
};

$bucketByStatus = static function (string $status): string {
    return match ($status) {
        'pending_payment', 'pending', 'payment_failed', 'payment_expired' => 'pendientes',
        'paid', 'processing', 'packed' => 'preparando',
        'shipped' => 'enviados',
        'delivered' => 'entregados',
        default => 'otros',
    };
};

$counts = [
    'todos' => count($orders),
    'pendientes' => 0,
    'preparando' => 0,
    'enviados' => 0,
    'entregados' => 0,
];

foreach ($orders as $o) {
    $bucket = $bucketByStatus((string)($o['status'] ?? 'pending'));
    if (isset($counts[$bucket])) {
        $counts[$bucket]++;
    }
}

$activeTab = (string)($_GET['estado'] ?? 'todos');
if (!in_array($activeTab, ['todos', 'pendientes', 'preparando', 'enviados', 'entregados'], true)) {
    $activeTab = 'todos';
}

$filteredOrders = array_values(array_filter($orders, static function (array $o) use ($activeTab, $bucketByStatus): bool {
    if ($activeTab === 'todos') {
        return true;
    }
    return $bucketByStatus((string)($o['status'] ?? 'pending')) === $activeTab;
}));

$tabHref = static function (string $tab): string {
    return url('/cuenta/pedidos') . ($tab === 'todos' ? '' : ('?estado=' . rawurlencode($tab)));
};

$perPage = 5;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$totalFiltered = count($filteredOrders);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$pageStart = ($currentPage - 1) * $perPage;
$pagedOrders = array_slice($filteredOrders, $pageStart, $perPage);

$statusIcon = static function (string $status): string {
    return match ($status) {
        'pending_payment', 'pending', 'payment_failed', 'payment_expired' => 'cart-outline',
        'processing', 'packed' => 'box',
        'shipped' => 'truck-solid',
        'delivered' => 'circle-check-solid',
        default => 'box',
    };
};

$statusShort = static function (string $statusLabel): string {
    $label = trim($statusLabel);
    if (stripos($label, 'preparando') !== false) return 'Preparando';
    if (stripos($label, 'pendiente') !== false) return 'Pendiente';
    if (stripos($label, 'enviado') !== false) return 'Enviado';
    if (stripos($label, 'entregado') !== false) return 'Entregado';
    return $label;
};

$statusMessage = static function (string $status): string {
    return match ($status) {
        'processing', 'packed' => 'Tu pedido se esta preparando',
        'pending_payment', 'pending', 'payment_failed', 'payment_expired' => 'Estamos procesando tu pedido',
        'shipped' => 'Tu pedido ya fue despachado',
        'delivered' => 'Tu pedido fue entregado',
        default => 'Seguimos trabajando en tu pedido',
    };
};
?>

<section class="acct-page account-orders-page">
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
              'active' => 'pedidos',
              'uname' => $uname,
              'email' => $email,
              'avatarUrl' => $avatarUrl,
              'isAdmin' => $isAdmin,
              'whatsUrl' => $whatsUrl,
          ]);
          ?>
        </div>

        <section class="account-orders-block">
          <header class="account-orders-block__head">
            <h2 class="account-orders-block__title">Mis pedidos</h2>
            <p class="account-orders-block__intro">Revisa el estado de tus compras</p>
          </header>

          <div class="account-orders-toolbar">
            <form class="account-orders-filter" method="get" action="<?= e(url('/cuenta/pedidos')) ?>">
              <label class="account-orders-filter__label" for="orders_estado">Filtrar estado</label>
              <div class="account-orders-filter__control-wrap">
                <select id="orders_estado" name="estado" class="account-orders-filter__control" onchange="this.form.submit()">
                  <option value="todos" <?= $activeTab === 'todos' ? 'selected' : '' ?>>Todos (<?= (int)$counts['todos'] ?>)</option>
                  <option value="pendientes" <?= $activeTab === 'pendientes' ? 'selected' : '' ?>>Pendientes (<?= (int)$counts['pendientes'] ?>)</option>
                  <option value="preparando" <?= $activeTab === 'preparando' ? 'selected' : '' ?>>Preparando (<?= (int)$counts['preparando'] ?>)</option>
                  <option value="enviados" <?= $activeTab === 'enviados' ? 'selected' : '' ?>>Enviados (<?= (int)$counts['enviados'] ?>)</option>
                  <option value="entregados" <?= $activeTab === 'entregados' ? 'selected' : '' ?>>Entregados (<?= (int)$counts['entregados'] ?>)</option>
                </select>
                <span class="account-orders-filter__icon" aria-hidden="true"><?= ui_icon('chevron-down-solid') ?></span>
              </div>
            </form>

            <div class="account-orders-sort">
              <div class="account-orders-sort__control-wrap">
                <select id="orders_sort" class="account-orders-sort__control">
                  <option selected>Mas recientes</option>
                  <option>Mas antiguos</option>
                </select>
                <span class="account-orders-sort__icon" aria-hidden="true"><?= ui_icon('chevron-down-solid') ?></span>
              </div>
            </div>

            <nav class="account-orders-tabs" aria-label="Filtros de pedidos">
              <a class="account-orders-tabs__item<?= $activeTab ==='todos' ? ' is-active' : '' ?>" href="<?= e($tabHref('todos')) ?>">Todos (<?= (int)$counts['todos'] ?>)</a>
              <a class="account-orders-tabs__item<?= $activeTab ==='pendientes' ? ' is-active' : '' ?>" href="<?= e($tabHref('pendientes')) ?>">Pendientes (<?= (int)$counts['pendientes'] ?>)</a>
              <a class="account-orders-tabs__item<?= $activeTab ==='preparando' ? ' is-active' : '' ?>" href="<?= e($tabHref('preparando')) ?>">Preparando (<?= (int)$counts['preparando'] ?>)</a>
              <a class="account-orders-tabs__item<?= $activeTab ==='enviados' ? ' is-active' : '' ?>" href="<?= e($tabHref('enviados')) ?>">Enviados (<?= (int)$counts['enviados'] ?>)</a>
              <a class="account-orders-tabs__item<?= $activeTab ==='entregados' ? ' is-active' : '' ?>" href="<?= e($tabHref('entregados')) ?>">Entregados (<?= (int)$counts['entregados'] ?>)</a>
            </nav>
          </div>

        <?php if (!$hasOrders): ?>
          <div class="account-orders-empty account-orders-empty--no-orders">
            <figure class="account-orders-empty__media">
              <img src="<?= e(asset('img/account/img-empty-cart.jpg')) ?>" alt="Todavia no hay pedidos" loading="lazy" decoding="async">
            </figure>
            <h3 class="account-orders-empty__title">Todavia no hiciste ningun pedido</h3>
            <p class="account-orders-empty__copy account-orders-empty__copy--no-orders">
              Cuando realices tu primera compra, vas a poder seguirla desde acá.
            </p>
          </div>
        <?php elseif (count($filteredOrders) <= 0): ?>
          <div class="account-orders-empty">
            <div class="account-orders-empty__copy">
              Aún no hay pedidos para este filtro. Cuando hagas compras, acá vas a poder seguir su estado.
            </div>
          </div>
        <?php else: ?>
          <div class="account-orders-list">
            <?php foreach ($pagedOrders as $o): ?>
              <?php
                $status = (string)($o['status'] ?? 'pending');
                $tone = $statusTone($status);
                $itemsCount = (int)($o['items_count'] ?? 0);
                $statusPill = $statusShort((string)($o['status_label'] ?? $status));
                $statusText = $statusMessage($status);
                $createdAt = trim((string)($o['created_at'] ?? ''));
              ?>
              <article class="account-order-item">
                <div class="account-order-item__icon account-order-item__icon--<?= e($tone) ?>">
                  <?= ui_icon($statusIcon($status)) ?>
                </div>

                <div class="account-order-item__main">
                  <div class="account-order-item__head-row">
                    <h3 class="account-order-item__title">Pedido #<?= (int)($o['id'] ?? 0) ?></h3>
                    <?php if ($createdAt !== ''): ?>
                      <p class="account-order-item__meta">Realizado el <?= e($createdAt) ?></p>
                    <?php endif; ?>
                  </div>

                  <p class="account-order-item__line account-order-item__line--top">
                    <span><?= $itemsCount > 0 ? $itemsCount . ' producto' . ($itemsCount > 1 ? 's' : '') : 'Productos' ?></span>
                    <span class="account-order-item__value">Total: <?= e((string)($o['total_fmt'] ?? '-')) ?></span>
                  </p>

                  <p class="account-order-item__status">
                    <span class="account-orders-badge account-orders-badge--<?= e($tone) ?>">
                      <?= e($statusPill) ?>
                    </span>
                    <span class="account-order-item__status-text"><?= e($statusText) ?></span>
                  </p>

                  <div class="account-order-item__actions">
                    <a class="account-order-item__btn" href="<?= e((string)($o['href'] ?? url('/cuenta/pedidos'))) ?>">
                      <span aria-hidden="true"><?= ui_icon('document') ?></span>
                      <span>Ver detalle</span>
                    </a>
                  </div>
                </div>
              </article>
            <?php endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
            <?php
            section('partials/catalogo/pagination', [
                'total' => $totalFiltered,
                'perPage' => $perPage,
                'page' => $currentPage,
                'basePath' => '/cuenta/pedidos',
            ]);
            ?>
          <?php endif; ?>
        <?php endif; ?>
      </section>
      </div>

    </div>
  </div>
</section>

