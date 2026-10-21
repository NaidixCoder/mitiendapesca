<?php
$pageId    = 'auth-register';
$pageTitle = 'Crear cuenta - Mi Tienda Pesca';
$pageDesc  = 'Registrate para comprar más rápido y ver tu historial.';

$errors = (array)(flash('form_errors') ?? []);
?>

<main class="auth-login auth-register">
  <div class="auth-login__inner">
    <section class="auth-login__card" aria-labelledby="auth-register-title">
      <header class="auth-login__card-head">
        <h1 id="auth-register-title" class="auth-login__title">Crear cuenta</h1>
        <p class="auth-login__subtitle">Completá tus datos para empezar.</p>
      </header>

      <?php if ($msg = flash('ok')): ?>
        <div class="auth-register__alert auth-register__alert--ok"><?= e($msg) ?></div>
      <?php endif; ?>

      <div id="register-errors" class="auth-register__alert auth-register__alert--error ui-is-hidden"></div>

      <?php if (!empty($errors)): ?>
        <div class="auth-register__alert auth-register__alert--error">
          <ul class="auth-register__error-list">
            <?php foreach ($errors as $e): ?>
              <li><?= e($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="register-form" method="post" action="<?= url('/registro') ?>" class="auth-login__form" novalidate>
        <?= csrf_field() ?>
        <?= function_exists('antibot_fields') ? antibot_fields('register') : '' ?>

        <label class="auth-login__label" for="reg-name">Nombre y apellido</label>
        <input id="reg-name" class="auth-login__input auth-login__input--plain" type="text" name="name" value="<?= e(old('name')) ?>" autocomplete="name" required autofocus>
        <p id="err-name" class="auth-register__field-error ui-is-hidden"></p>

        <label class="auth-login__label" for="reg-email">Email</label>
        <input id="reg-email" class="auth-login__input auth-login__input--plain" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required>
        <p id="err-email" class="auth-register__field-error ui-is-hidden"></p>

        <label class="auth-login__label" for="reg-phone">Teléfono <span class="auth-register__optional">(opcional)</span></label>
        <input id="reg-phone" class="auth-login__input auth-login__input--plain" type="tel" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" inputmode="tel">

        <label class="auth-login__label" for="reg-birthdate">Fecha de nacimiento <span class="auth-register__optional">(opcional)</span></label>
        <input id="reg-birthdate" class="auth-login__input auth-login__input--plain" type="date" name="birthdate" value="<?= e(old('birthdate')) ?>" autocomplete="bday">

        <label class="auth-login__label" for="reg-password">Clave</label>
        <input id="reg-password" class="auth-login__input auth-login__input--plain" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Mín. 8 caracteres">
        <p id="err-password" class="auth-register__field-error ui-is-hidden"></p>

        <label class="auth-login__label" for="reg-password-confirm">Repetir clave</label>
        <input id="reg-password-confirm" class="auth-login__input auth-login__input--plain" type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        <p id="err-password_confirm" class="auth-register__field-error ui-is-hidden"></p>

        <label class="auth-register__checkline" for="terms">
          <input id="terms" type="checkbox" name="terms" required <?= old('terms') ? 'checked' : '' ?>>
          <span>Acepto los <a href="<?= url('/terminos') ?>">Términos</a> y la <a href="<?= url('/privacidad') ?>">Privacidad</a>.</span>
        </label>

        <label class="auth-register__checkline" for="newsletter-optin">
          <input type="hidden" name="newsletter_optin" value="0">
          <input id="newsletter-optin" type="checkbox" name="newsletter_optin" value="1" <?= old('newsletter_optin', '1') === '1' ? 'checked' : '' ?>>
          <span>Quiero recibir ofertas y novedades por email.</span>
        </label>

        <p id="err-terms" class="auth-register__field-error ui-is-hidden"></p>

        <button class="auth-login__submit" type="submit">Crear cuenta</button>

        <p class="auth-login__register">¿Ya tenés cuenta? <a href="<?= url('/login') ?>">Iniciá sesión</a></p>
      </form>
    </section>
  </div>
</main>
