<?php
http_response_code(429);

$reason = (string)($reason ?? ($_GET['reason'] ?? ''));
$retry  = (int)($retry ?? ($_GET['retry'] ?? 0));

$title = 'Demasiados intentos';
$desc  = 'Para proteger tu cuenta, pausamos los intentos por un momento. Probá nuevamente en unos minutos.';

if ($reason === 'fast_submit') {
  $title = 'Un toque más...';
  $desc  = 'El formulario se envió demasiado rápido. Esperá un instante y volvé a intentarlo.';
} elseif ($reason === 'email_resend_limit') {
  $title = 'Te pedimos una pausa';
  $desc  = 'Ya reenviaste el email de verificación varias veces. Probá de nuevo en unos minutos.';
} elseif ($reason === 'password_reset_limit') {
  $title = 'Te pedimos una pausa';
  $desc  = 'Ya pediste varios reseteos en poco tiempo. Probá nuevamente en unos minutos.';
} elseif ($reason === 'password_change_limit') {
  $title = 'Demasiados intentos';
  $desc  = 'Pausamos los cambios de contraseña por un momento. Probá nuevamente en unos minutos.';
}

if ($retry > 0) {
  $desc .= ' Tiempo estimado de espera: ' . (int)ceil($retry / 60) . ' min.';
}

$pageTitle = '429 - Demasiados intentos | Mi Pesca & Aventura';
$pageDesc  = $desc;
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<main class="error-view">
  <section class="error-view__inner" aria-labelledby="error-title">
    <figure class="error-view__media">
      <img src="<?= e(asset('img/undraw_fixing-bugs_1ytu.svg')) ?>" alt="">
    </figure>
    <p class="error-view__code">Error 429</p>
    <h1 id="error-title" class="error-view__title"><?= e($title) ?></h1>
    <p class="error-view__copy"><?= e($desc) ?></p>
    <div class="error-view__actions">
      <a class="error-view__btn" href="<?= e(url('/')) ?>">Ir al inicio</a>
      <a class="error-view__btn error-view__btn--primary" href="<?= e(url('/login')) ?>">Volver al login</a>
    </div>
  </section>
</main>
