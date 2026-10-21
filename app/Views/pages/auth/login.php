<?php
$pageId    = 'auth-google'; // Para <body data-page="auth-google">
$pageTitle = 'Iniciar sesion - Tienda Pesca';
$pageDesc  = 'Accede a tu cuenta para ver pedidos y comprar mas rapido.';
?>

<main class="auth-login">
  <div class="auth-login__inner">
    <section class="auth-login__card" aria-labelledby="auth-login-title">
      <header class="auth-login__card-head">
        <h1 id="auth-login-title" class="auth-login__title">Iniciá sesión</h1>
        <p class="auth-login__subtitle">Usá Google para entrar rápido y seguir con tu compra.</p>
      </header>

      <div class="auth-login__google-wrap">
        <div
          id="google-btn-container"
          data-google-client-id="<?= e(env('GOOGLE_CLIENT_ID', '')) ?>"
          style="min-height:48px">
        </div>
      </div>

      <div class="auth-login__divider" aria-hidden="true">
        <span></span>
        <small>o ingresá con email</small>
        <span></span>
      </div>

      <form method="post" class="auth-login__form" novalidate>
        <?= csrf_field() ?>
        <?= function_exists('antibot_fields') ? antibot_fields('login') : '' ?>

        <?php $next = (string)($next ?? ($_GET['next'] ?? '')); $next = sanitize_next_url($next, ''); ?>
        <?php if ($next !== ''): ?>
          <input type="hidden" name="next" value="<?= e($next) ?>">
        <?php endif; ?>

        <label class="auth-login__label" for="login-email">Email</label>
        <div class="auth-login__field-wrap">
          <span class="auth-login__field-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h15A1.5 1.5 0 0 1 21 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 16.5v-9z"></path>
              <path d="m4 8 8 6 8-6"></path>
            </svg>
          </span>
          <input
            id="login-email"
            class="auth-login__input"
            type="email"
            name="email"
            value="<?= old('email') ?>"
            placeholder="tu@email.com"
            autocomplete="email"
            required
            autofocus>
        </div>

        <label class="auth-login__label" for="login-password">Clave</label>
        <div class="auth-login__field-wrap">
          <span class="auth-login__field-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
              <rect x="4" y="10" width="16" height="10" rx="2"></rect>
              <path d="M8 10V7a4 4 0 1 1 8 0v3"></path>
              <circle cx="12" cy="15" r="1.2"></circle>
            </svg>
          </span>
          <input
            id="login-password"
            class="auth-login__input auth-login__input--password"
            type="password"
            name="password"
            placeholder="********"
            autocomplete="current-password"
            required>
          <button
            class="auth-login__field-icon auth-login__field-icon--end auth-login__toggle-pass"
            type="button"
            data-auth-toggle-password
            data-target="#login-password"
            aria-label="Mostrar contraseña"
            aria-pressed="false">
            <span class="auth-login__eye-open" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"></path>
                <circle cx="12" cy="12" r="2.8"></circle>
              </svg>
            </span>
            <span class="auth-login__eye-closed" aria-hidden="true">
              <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M45.3,22.1C43.2,19.5,35.4,11,24,11a23.4,23.4,0,0,0-3.8.3L39.9,31.1a30.1,30.1,0,0,0,5.4-5.2A3,3,0,0,0,45.3,22.1Z" fill="currentColor"/>
                <path d="M41.1,38.3,29.4,26.6A5.9,5.9,0,0,1,24,30a6,6,0,0,1-6-6,5.9,5.9,0,0,1,3.4-5.4L9.7,6.9A2,2,0,0,0,6.9,9.7l4.8,4.8a31.4,31.4,0,0,0-9,7.6,3,3,0,0,0,0,3.8C4.8,28.5,12.6,37,24,37a25.2,25.2,0,0,0,8.5-1.6l5.8,5.7a2,2,0,1,0,2.8-2.8Z" fill="currentColor"/>
              </svg>
            </span>
          </button>
        </div>

        <div class="auth-login__meta-row">
          <label class="auth-login__remember">
            <input type="checkbox" name="remember" value="1">
            <span>Recordarme</span>
          </label>
          <a class="auth-login__forgot" href="<?= e(base_url('/olvide-clave')) ?>">¿Olvidaste tu clave?</a>
        </div>

        <button class="auth-login__submit" type="submit">Ingresar</button>
      </form>

      <p class="auth-login__register">
        ¿No tenés cuenta?
        <a href="<?= url('/registro') ?>">Crear una</a>
      </p>
    </section>

  </div>
</main>
