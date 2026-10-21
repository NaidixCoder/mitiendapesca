<?php
$pageId    = 'auth-reset';
$pageTitle = 'Restablecer contraseña — Tienda Pesca';
$pageDesc  = 'Elegí una nueva contraseña para tu cuenta.';
$token = (string)($token ?? ($_GET['token'] ?? ''));
$email = (string)($email ?? '');
?>

<main class="site-reset">
  <div class="site-reset__inner">
    <div class="card site-reset__card">
      <header class="site-reset__heading">
        <h1 class="site-reset__title">Restablecer contraseña</h1>
        <?php if ($email): ?>
          <p class="site-reset__hint">
            Cuenta: <span class="site-reset__account"><?= e($email) ?></span>
          </p>
        <?php endif; ?>
      </header>

      <form method="POST" action="<?= e(base_url('/reset-clave')) ?>" class="site-reset__form">
        <?= csrf_field() ?>
        <?= function_exists('antibot_fields') ? antibot_fields('reset_password') : '' ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div>
          <label class="label" for="password">Nueva contraseña</label>
          <input id="password" name="password" type="password" required minlength="8"
                 class="input site-reset__field" autocomplete="new-password" placeholder="Mínimo 8 caracteres">
        </div>

        <div>
          <label class="label" for="password2">Repetir contraseña</label>
          <input id="password2" name="password2" type="password" required minlength="8"
                 class="input site-reset__field" autocomplete="new-password" placeholder="Repetir contraseña">
        </div>

        <button class="btn btn-brand site-reset__submit" type="submit">Actualizar contraseña</button>

        <div class="site-reset__return">
          <a class="link" href="<?= e(base_url('/login')) ?>">Volver al login</a>
        </div>
      </form>
    </div>
  </div>
</main>
