<?php
http_response_code(403);
$pageTitle = '403 - Acceso denegado | Mi Pesca & Aventura';
$pageDesc  = 'No tenés permisos para acceder a esta sección.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<main class="error-view">
  <section class="error-view__inner" aria-labelledby="error-title">
    <figure class="error-view__media">
      <img src="<?= e(asset('img/undraw_fixing-bugs_1ytu.svg')) ?>" alt="">
    </figure>
    <p class="error-view__code">Error 403</p>
    <h1 id="error-title" class="error-view__title">Acceso denegado</h1>
    <p class="error-view__copy">No tenés permisos para ver esta página con tu cuenta actual.</p>
    <div class="error-view__actions">
      <a class="error-view__btn" href="<?= e(url('/')) ?>">Ir al inicio</a>
      <a class="error-view__btn error-view__btn--primary" href="<?= e(url('/login')) ?>">Iniciar sesión</a>
    </div>
  </section>
</main>
