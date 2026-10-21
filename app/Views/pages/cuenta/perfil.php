<?php
$pageId    = 'account-profile';
$pageTitle = 'Mis datos - Tienda Pesca';
$pageDesc  = 'Completá tu perfil para beneficios y una mejor experiencia.';

$user = $user ?? [];
$profile = $profile ?? [];
$fiscalProfile = is_array($fiscalProfile ?? null) ? $fiscalProfile : [];
$uname = $_SESSION['uname'] ?? 'Usuario';
$email = $_SESSION['email'] ?? '';
$avatarUrl = $_SESSION['uavatar'] ?? '';
if ($avatarUrl && (parse_url($avatarUrl, PHP_URL_HOST) ?? '') === 'lh3.googleusercontent.com'
    && !preg_match('/=s\d+-c$/', $avatarUrl)) {
    $avatarUrl .= '=s96-c';
}
$isAdmin = function_exists('is_admin') && is_admin();
$whatsUrl = trim((string)app_setting('social_whatsapp_url', env('CONTACT_WHATSAPP_URL', '')));
$errors = $errors ?? [];

function profile_value(array $data, string $key): string {
  $value = $data[$key] ?? '';
  return is_string($value) ? $value : '';
}

function profile_error(array $errorsBag, string $key): string {
  return isset($errorsBag[$key]) ? (string)$errorsBag[$key] : '';
}

$phone = profile_value($profile, 'phone');
$birth = profile_value($profile, 'birth_date');
$emailOpt = !empty($profile['marketing_email_opt_in']);
$waOpt = !empty($profile['marketing_whatsapp_opt_in']);

$name = isset($user['name']) && is_string($user['name']) ? $user['name'] : '';
$email = isset($user['email']) && is_string($user['email']) ? $user['email'] : '';
$emailVerified = !empty($user['email_verified']);

$completeCount = 0;
if (trim($name) !== '') {
  $completeCount++;
}
if (trim($phone) !== '') {
  $completeCount++;
}
if ($emailVerified) {
  $completeCount++;
}
$totalCount = 3;
$pct = (int) round(($completeCount / max(1, $totalCount)) * 100);

$fTax = isset($fiscalProfile['tax_condition']) && is_string($fiscalProfile['tax_condition']) ? $fiscalProfile['tax_condition'] : 'consumidor_final';
$fCuit = isset($fiscalProfile['cuit']) && is_string($fiscalProfile['cuit']) ? $fiscalProfile['cuit'] : '';
$fLegal = isset($fiscalProfile['legal_name']) && is_string($fiscalProfile['legal_name']) ? $fiscalProfile['legal_name'] : '';
$fStatus = isset($fiscalProfile['validation_status']) && is_string($fiscalProfile['validation_status']) ? $fiscalProfile['validation_status'] : 'unverified';
$fErr = isset($fiscalProfile['validation_error']) && is_string($fiscalProfile['validation_error']) ? $fiscalProfile['validation_error'] : '';

$statusLabel = match ($fStatus) {
  'valid' => 'Validado',
  'pending' => 'Pendiente',
  'invalid' => 'No validado',
  default => 'Sin validar',
};

$statusTone = match ($fStatus) {
  'valid' => 'ok',
  'pending' => 'warn',
  'invalid' => 'danger',
  default => 'neutral',
};

$profileTone = ($completeCount === $totalCount) ? 'ok' : 'warn';
$profileBadgeHint = isset($profileBadgeHint) && is_string($profileBadgeHint) ? $profileBadgeHint : (($completeCount === $totalCount) ? 'Completo' : 'Incompleto');
?>

<section class="acct-page account-profile-page">
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
              'active' => 'perfil',
              'uname' => $uname,
              'email' => $email,
              'avatarUrl' => $avatarUrl,
              'isAdmin' => $isAdmin,
              'whatsUrl' => $whatsUrl,
          ]);
          ?>
        </div>

        <div class="account-layout-main">
<section class="account-profile-card">
        <header class="account-profile-card__head">
          <div class="account-profile-card__head-copy">
            <h2 class="account-profile-card__title">Perfil personal</h2>
            <p class="account-profile-card__intro">Nombre y teléfono te ayudan a comprar más rápido. La fecha de nacimiento es opcional.</p>
          </div>
          <span class="account-profile-badge account-profile-badge--<?= e($profileTone) ?>">
            <?= e($profileBadgeHint) ?>
          </span>
        </header>

        <div class="account-profile-card__body">
          <section class="account-profile-progress" aria-label="Progreso del perfil">
            <div class="account-profile-progress__meta">
              <span class="account-profile-progress__label">Progreso del perfil</span>
              <span class="account-profile-progress__value"><strong><?= (int) $completeCount ?></strong>/<?= (int) $totalCount ?></span>
            </div>
            <div class="account-profile-progress__track" aria-hidden="true">
              <div class="account-profile-progress__bar" style="width: <?= (int) $pct ?>%"></div>
            </div>
          </section>

          <form method="post" action="<?= e(base_url('/cuenta/perfil')) ?>" class="account-profile-form">
            <?= csrf_field() ?>

            <div class="account-profile-form__field account-profile-form__field--wide">
              <label class="account-profile-form__label" for="name">Nombre</label>
              <input
                id="name"
                name="name"
                type="text"
                class="account-profile-form__control<?= profile_error($errors,'name') !== '' ? ' is-error' : '' ?>"
                placeholder="Ej: Matías Infante"
                value="<?= e($name) ?>"
              >
              <?php if (profile_error($errors, 'name') !== ''): ?>
                <p class="account-profile-form__error"><?= e(profile_error($errors, 'name')) ?></p>
              <?php endif; ?>
              <p class="account-profile-form__hint">Este nombre se muestra en tu cuenta y se usa como referencia en compras.</p>
            </div>

            <div class="account-profile-form__field account-profile-form__field--wide">
              <label class="account-profile-form__label" for="email_view">Email</label>
              <div class="account-profile-form__readonly">
                <input id="email_view" type="text" class="account-profile-form__control" value="<?= e($email) ?>" disabled>
                <span class="account-profile-badge account-profile-badge--<?= $emailVerified ?'ok' : 'warn' ?>">
                  <?= $emailVerified ? 'Verificado' : 'Sin verificar' ?>
                </span>
              </div>
              <?php if (!$emailVerified): ?>
                <p class="account-profile-form__hint">Para mayor seguridad, verificá tu email desde “Mi cuenta”.</p>
              <?php endif; ?>
            </div>

            <div class="account-profile-form__field">
              <label class="account-profile-form__label" for="phone">Teléfono</label>
              <input
                id="phone"
                name="phone"
                type="text"
                class="account-profile-form__control<?= profile_error($errors,'phone') !== '' ? ' is-error' : '' ?>"
                placeholder="Ej: +54 9 341 123 4567"
                value="<?= e($phone) ?>"
              >
              <?php if (profile_error($errors, 'phone') !== ''): ?>
                <p class="account-profile-form__error"><?= e(profile_error($errors, 'phone')) ?></p>
              <?php endif; ?>
            </div>

            <div class="account-profile-form__field">
              <label class="account-profile-form__label" for="birth_date">Fecha de nacimiento (opcional)</label>
              <input
                id="birth_date"
                name="birth_date"
                type="date"
                class="account-profile-form__control<?= profile_error($errors,'birth_date') !== '' ? ' is-error' : '' ?>"
                value="<?= e($birth) ?>"
              >
              <?php if (profile_error($errors, 'birth_date') !== ''): ?>
                <p class="account-profile-form__error"><?= e(profile_error($errors, 'birth_date')) ?></p>
              <?php endif; ?>
            </div>

            <div class="account-profile-form__field account-profile-form__field--full">
              <div class="account-profile-form__choices">
                <label class="account-profile-checkbox">
                  <input type="checkbox" name="marketing_email_opt_in" value="1" <?= $emailOpt ? 'checked' : '' ?>>
                  <span class="account-profile-checkbox__copy">Quiero recibir novedades y promociones por <strong>email</strong>.</span>
                </label>

                <label class="account-profile-checkbox">
                  <input type="checkbox" name="marketing_whatsapp_opt_in" value="1" <?= $waOpt ? 'checked' : '' ?>>
                  <span class="account-profile-checkbox__copy">Quiero recibir novedades y promociones por <strong>WhatsApp</strong>.</span>
                </label>
              </div>
            </div>

            <div class="account-profile-form__actions">
              <a class="v2-btn v2-btn--ghost" href="<?= e(base_url('/cuenta')) ?>">Volver</a>
              <button class="v2-btn v2-btn--primary" type="submit">Guardar cambios</button>
            </div>
          </form>
        </div>
      </section>

      <!-- <section class="account-profile-card account-profile-card--soft">
        <header class="account-profile-card__head">
          <div class="account-profile-card__head-copy">
            <h2 class="account-profile-card__title">Facturación</h2>
            <p class="account-profile-card__intro">Tu cuenta fiscal actual se usa como referencia para futuras mejoras del flujo de compra.</p>
          </div>
          <span class="account-profile-badge account-profile-badge--<?= e($statusTone) ?>"><?= e($statusLabel) ?></span>
        </header>

        <div class="account-profile-card__body">
          <dl class="account-profile-fiscal">
            <div class="account-profile-fiscal__row">
              <dt>Condición fiscal</dt>
              <dd><?= e($fTax !== '' ? $fTax : 'Consumidor final') ?></dd>
            </div>
            <div class="account-profile-fiscal__row">
              <dt>Razón social</dt>
              <dd><?= e($fLegal !== '' ? $fLegal : 'Sin informar') ?></dd>
            </div>
            <div class="account-profile-fiscal__row">
              <dt>CUIT</dt>
              <dd><?= e($fCuit !== '' ? $fCuit : 'Sin informar') ?></dd>
            </div>
          </dl>

          <?php if ($fErr !== ''): ?>
            <p class="account-profile-note account-profile-note--danger"><?= e($fErr) ?></p>
          <?php endif; ?>

          <p class="account-profile-note">
            Emitimos la factura correspondiente según la configuración fiscal vigente. Si más adelante ampliamos opciones B2B, vas a poder completar esos datos desde esta sección.
          </p>
        </div>
      </section> -->
        </div>
      </div>

    </div>
  </div>
</section>



