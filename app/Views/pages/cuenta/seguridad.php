<?php
$pageId = 'account-security';
$pageTitle = 'Seguridad - Tienda Pesca';
$pageDesc = 'Cambia tu contrasena de forma segura.';

$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
$showVerifyBanner = !empty($_SESSION['uid']) && empty($_SESSION['email_verified']);
$securityMeta = is_array($securityMeta ?? null) ? $securityMeta : [];
$canChangePassword = !empty($securityMeta['can_change_password']);
$accessLabel = isset($securityMeta['access_label']) ? (string)$securityMeta['access_label'] : 'Email y contrasena';
$accessHint = isset($securityMeta['access_hint']) ? (string)$securityMeta['access_hint'] : 'Podes cambiar tu contrasena desde esta seccion.';
$passwordChangedAtRaw = isset($securityMeta['password_changed_at']) ? (string)$securityMeta['password_changed_at'] : '';
$passwordChangedAt = $passwordChangedAtRaw !== '' ? date('d/m/Y H:i', strtotime($passwordChangedAtRaw)) : 'Sin registro';
?>

<section class="acct-page account-security-page">
  <div class="acct-page__inner">
    <div class="acct-page__stack">
      <?php
      section('cuenta/hero', [
          'uname' => $uname,
          'email' => $email,
          'avatarUrl' => $avatarUrl,
          'isAdmin' => $isAdmin,
          'desc' => 'Gestiona tu cuenta y segui tus compras.',
          'showVerifyBanner' => $showVerifyBanner,
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
              'active' => 'seguridad',
              'uname' => $uname,
              'email' => $email,
              'avatarUrl' => $avatarUrl,
              'isAdmin' => $isAdmin,
              'whatsUrl' => $whatsUrl,
          ]);
          ?>
        </div>

        <div class="account-layout-main">
          <section class="account-security-card">
            <header class="account-security-card__head">
              <div class="account-security-card__head-copy">
                <h2 class="account-security-card__title">Cambiar contrasena</h2>
                <p class="account-security-card__intro"><?= e($accessHint) ?></p>
              </div>
            </header>

            <div class="account-security-card__body">
              <div class="account-security-form__field">
                <label class="account-security-form__label">Metodo de acceso</label>
                <div class="account-security-form__control account-security-form__readonly"><?= e($accessLabel) ?></div>
              </div>

              <div class="account-security-form__field">
                <label class="account-security-form__label">Ultimo cambio de contrasena</label>
                <div class="account-security-form__control account-security-form__readonly"><?= e($passwordChangedAt) ?></div>
              </div>

              <?php if ($canChangePassword): ?>
                <form method="post" action="<?= e(base_url('/cuenta/seguridad/cambiar-clave')) ?>" class="account-security-form">
                  <?= csrf_field() ?>
                  <?= function_exists('antibot_fields') ? antibot_fields('change_password') : '' ?>

                  <div class="account-security-form__field">
                    <label class="account-security-form__label" for="current_password">Contrasena actual</label>
                    <input
                      id="current_password"
                      name="current_password"
                      type="password"
                      required
                      class="account-security-form__control"
                      autocomplete="current-password"
                    >
                  </div>

                  <div class="account-security-form__field">
                    <label class="account-security-form__label" for="password">Nueva contrasena</label>
                    <input
                      id="password"
                      name="password"
                      type="password"
                      required
                      minlength="8"
                      class="account-security-form__control"
                      autocomplete="new-password"
                      placeholder="Minimo 8 caracteres"
                    >
                  </div>

                  <div class="account-security-form__field">
                    <label class="account-security-form__label" for="password2">Repetir nueva contrasena</label>
                    <input
                      id="password2"
                      name="password2"
                      type="password"
                      required
                      minlength="8"
                      class="account-security-form__control"
                      autocomplete="new-password"
                    >
                  </div>

                  <div class="account-security-form__actions">
                    <a class="v2-btn v2-btn--ghost" href="<?= e(base_url('/cuenta')) ?>">Volver</a>
                    <button class="v2-btn v2-btn--primary" type="submit">Cambiar contrasena</button>
                  </div>
                </form>
              <?php else: ?>
                <div class="account-security-form">
                  <p class="account-security-card__intro">Esta cuenta no tiene contraseña local. Podes gestionar la seguridad desde Google.</p>
                  <div class="account-security-form__actions">
                    <a class="v2-btn v2-btn--primary" href="https://myaccount.google.com/security" target="_blank" rel="noopener noreferrer">Abrir seguridad de Google</a>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </section>

          <section class="account-security-card">
            <header class="account-security-card__head">
              <div class="account-security-card__head-copy">
                <h2 class="account-security-card__title">Sesiones activas</h2>
                <p class="account-security-card__intro">Si detectas actividad extrana, cerra sesion en otros dispositivos.</p>
              </div>
            </header>

            <div class="account-security-card__body">
              <form method="post" action="<?= e(base_url('/cuenta/sessions/clear')) ?>" class="account-security-form">
                <?= csrf_field() ?>
                <div class="account-security-form__actions">
                  <button class="v2-btn v2-btn--ghost" type="submit" onclick="return confirm('Esto cerrara tus otras sesiones activas. �Continuar?');">
                    Cerrar sesion en otros dispositivos
                  </button>
                </div>
              </form>
            </div>
          </section>
        </div>
      </div>

    </div>
  </div>
</section>
