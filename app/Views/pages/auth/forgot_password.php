<?php
$pageId    = 'auth-forgot';
$pageTitle = 'Recuperar contraseña - Tienda Pesca';
$pageDesc  = 'Te enviamos un link para restablecer tu contraseña.';
?>

<main class="auth-login auth-forgot">
  <div class="auth-login__inner">
    <section class="auth-login__card" aria-labelledby="auth-forgot-title">
      <header class="auth-login__card-head">
        <h1 id="auth-forgot-title" class="auth-login__title">¿Olvidaste tu contraseña?</h1>
        <p class="auth-login__subtitle">Ingresá tu email y, si existe en el sistema, te enviamos un link para restablecerla.</p>
      </header>

      <form method="post" action="<?= e(base_url('/olvide-clave')) ?>" class="auth-login__form" novalidate>
        <?= csrf_field() ?>
        <?= function_exists('antibot_fields') ? antibot_fields('forgot_password') : '' ?>

        <label class="auth-login__label" for="forgot-email">Email</label>
        <input
          id="forgot-email"
          name="email"
          type="email"
          required
          autocomplete="email"
          class="auth-login__input auth-login__input--plain"
          placeholder="tu@email.com">

        <button class="auth-login__submit" type="submit">Enviar link</button>

        <p class="auth-login__register">
          <a href="<?= e(base_url('/login')) ?>">Volver al login</a>
        </p>
      </form>
    </section>
  </div>
</main>
