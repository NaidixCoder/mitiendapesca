<?php
http_response_code(500);
$pageTitle = '500 - Error del servidor | Mi Pesca & Aventura';
$pageDesc  = 'Ocurrió un problema procesando tu solicitud.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<main class="error-view">
  <section class="error-view__inner" aria-labelledby="error-title">
    <figure class="error-view__media">
      <img src="<?= e(asset('img/undraw_fixing-bugs_1ytu.svg')) ?>" alt="">
    </figure>
    <p class="error-view__code">Error 500</p>
    <h1 id="error-title" class="error-view__title">Ocurrió un error inesperado</h1>
    <p class="error-view__copy">Tuvimos un problema procesando tu solicitud. Probá nuevamente en unos minutos.</p>
    <div class="error-view__actions">
      <a class="error-view__btn" href="<?= e(url('/')) ?>">Volver al inicio</a>
      <button class="error-view__btn error-view__btn--primary" type="button" onclick="window.location.reload()">Reintentar</button>
    </div>
  </section>
</main>
