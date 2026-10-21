<?php
$pageTitle = 'Mi cuenta - Tienda Pesca';
$pageDesc = 'Resumen de tu cuenta, pedidos y datos.';

$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';

if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}

$userMeta = is_array($userMeta ?? null) ? $userMeta : [];
if (!$avatarUrl && !empty($userMeta['avatar_url'])) {
    $avatarUrl = (string)$userMeta['avatar_url'];
}

$cta = is_array($cta ?? null) ? $cta : null;
$accountMissing = is_array($accountMissing ?? null) ? $accountMissing : [];
$accountMissingCount = (int)($accountMissingCount ?? count($accountMissing));
$isAdmin = function_exists('is_admin') && is_admin();

$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));

$bannerStatus = 'Todo listo para comprar';
if ($accountMissingCount > 0) {
    $firstMissing = (string)($accountMissing[0] ?? 'Completar perfil');
    if (str_starts_with($firstMissing, 'Agregar ')) {
        $firstMissing = substr($firstMissing, 8);
    } elseif (str_starts_with($firstMissing, 'Verificar ')) {
        $firstMissing = 'verificar ' . substr($firstMissing, 10);
    }
    $bannerStatus = 'Falta ' . $firstMissing;
}

$hintHref = ($accountMissingCount > 0 && $cta && !empty($cta['href']))
    ? (string)$cta['href']
    : (string)url('/productos');
$hintShowSuccess = $accountMissingCount <= 0;
$showVerifyBanner = !empty($_SESSION['uid']) && empty($_SESSION['email_verified']);

$latestOrderId = (int)($latestOrder['id'] ?? 0);
$latestOrderStatus = trim((string)($latestOrder['status_label'] ?? ''));
$latestOrderStatus = $latestOrderStatus !== '' ? $latestOrderStatus : 'Sin datos';
$addressHeadline = $addressCount === 1 ? '1 dirección guardada' : ((int)$addressCount . ' direcciones guardadas');
$favoritesHeadline = $favoritesCount === 1 ? '1 producto guardado' : ((int)$favoritesCount . ' productos guardados');
$dashSentinels = ['—', '-', ''];
$profileBirthDesktop = in_array(trim((string)$profileBirthFmt), $dashSentinels, true) ? 'Sin cargar' : (string)$profileBirthFmt;
$loyaltyPoints = (int)($loyalty['points'] ?? 0);
$loyaltyNextTier = (string)($loyalty['next_tier'] ?? 'Plata');
$loyaltyProgressPct = max(0, min(100, (int)($loyalty['progress_pct'] ?? 0)));
$loyaltyToNext = (int)($loyalty['to_next_tier'] ?? 0);
$securityEmailOk = !empty($_SESSION['email_verified']);
?>

<section class="acct-page">
  <div class="acct-page__inner">
    <div class="acct-page__stack">
      <?php
      section('cuenta/hero', [
          'uname' => $uname,
          'email' => $email,
          'avatarUrl' => $avatarUrl,
          'isAdmin' => $isAdmin,
          'desc' => 'Gestioná tu cuenta y seguí tus compras.',
          'showHint' => true,
          'hintHref' => $hintHref,
          'hintShowSuccess' => $hintShowSuccess,
          'bannerStatus' => $bannerStatus,
          'showVerifyBanner' => $showVerifyBanner,
          'showQuick' => true,
          'quickItems' => [
              [
                  'href' => url('/cuenta/pedidos'),
                  'icon' => 'box',
                  'label' => 'Mis pedidos',
                  'copy' => 'Seguí tus compras y repetí pedidos fácilmente.',
                  'cta' => 'Ver pedidos',
                  'desktop_meta' => [
                      'type' => 'pair',
                      'left_label' => 'Último pedido',
                      'left_value' => $latestOrderId > 0 ? ('#' . $latestOrderId) : 'Sin pedidos',
                      'right_label' => 'Estado',
                      'right_value' => $latestOrderStatus,
                      'right_badge' => true,
                  ],
              ],
              [
                  'href' => url('/cuenta/direcciones'),
                  'icon' => 'house',
                  'label' => 'Direcciones',
                  'copy' => 'Gestioná tus direcciones para comprar más rápido.',
                  'cta' => 'Administrar',
                  'desktop_meta' => [
                      'type' => 'summary',
                      'title' => $addressHeadline,
                      'text' => in_array(trim((string)$addressSummary), $dashSentinels, true) ? 'Sin dirección principal' : (string)$addressSummary,
                  ],
              ],
              [
                  'href' => url('/cuenta/favoritos'),
                  'icon' => 'heart',
                  'label' => 'Favoritos',
                  'copy' => 'Guarda productos para encontrarlos al instante.',
                  'cta' => 'Ver lista',
                  'desktop_meta' => [
                      'type' => 'summary',
                      'title' => $favoritesHeadline,
                      'text' => 'Accedé rápido a los productos que guardaste.',
                  ],
              ],
              [
                  'href' => url('/cuenta/perfil'),
                  'icon' => 'user',
                  'label' => 'Datos personales',
                  'copy' => 'Mantené tu perfil actualizado para una experiencia más ágil.',
                  'cta' => 'Editar',
                  'desktop_meta' => [
                      'type' => 'pair',
                      'left_label' => 'Teléfono',
                      'left_value' => in_array(trim((string)$profilePhone), $dashSentinels, true) ? 'Sin cargar' : (string)$profilePhone,
                      'right_label' => 'Nacimiento',
                      'right_value' => $profileBirthDesktop,
                      'right_badge' => $profileBirthDesktop === 'Sin cargar',
                  ],
              ],
              [
                  'href' => url('/cuenta/lealtad'),
                  'icon' => 'star-solid',
                  'label' => 'Programa lealtad',
                  'copy' => 'Suma puntos y desbloquea beneficios.',
                  'cta' => 'Ver programa',
                  'desktop_meta' => [
                      'type' => 'progress',
                      'points' => $loyaltyPoints,
                      'next_tier' => $loyaltyNextTier,
                      'progress_pct' => $loyaltyProgressPct,
                      'to_next_tier' => $loyaltyToNext,
                  ],
              ],
              [
                  'href' => url('/cuenta/seguridad'),
                  'icon' => 'shield-solid',
                  'label' => 'Seguridad',
                  'copy' => 'Cambiá tu contraseña y reforzá el acceso a tu cuenta.',
                  'cta' => 'Configurar',
                  'desktop_meta' => [
                      'type' => 'pair',
                      'left_label' => 'Email',
                      'left_value' => $securityEmailOk ? 'Email verificado' : 'Email pendiente',
                      'left_badge' => true,
                      'left_badge_tone' => $securityEmailOk ? 'ok' : 'warn',
                      'right_label' => '',
                      'right_value' => '',
                      'right_badge' => false,
                  ],
              ],
          ],
      ]);
      ?>

      <article class="acct-support">
        <div class="acct-support__icon"><?= ui_icon('phone') ?></div>
        <div class="acct-support__copy">
          <h2 class="acct-support__title">¿Necesitás ayuda?</h2>
          <p class="acct-support__text">Nuestro equipo está para ayudarte.</p>
        </div>
        <a
          href="<?= e($whatsUrl !== '' ? $whatsUrl : url('/ayuda')) ?>"
          class="acct-support__cta"
          <?= $whatsUrl !== '' ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
        >
          <span class="acct-support__cta-icon" aria-hidden="true"><?= ui_icon('whatsapp-brand') ?></span>
          <span>Contactar soporte</span>
        </a>
      </article>

    </div>
  </div>
</section>




