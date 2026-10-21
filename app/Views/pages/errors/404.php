<?php
http_response_code(404);
$pageTitle = '404 - Página no encontrada | Mi Pesca & Aventura';
$pageDesc  = 'La página que buscás no existe o fue movida.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<main class="error-view">
  <section class="error-view__inner" aria-labelledby="error-title">
    <figure class="error-view__media">
      <img src="<?= e(asset('img/undraw_fixing-bugs_1ytu.svg')) ?>" alt="">
    </figure>
    <p class="error-view__code">Error 404</p>
    <h1 id="error-title" class="error-view__title">Página no encontrada</h1>
    <p class="error-view__copy">El enlace puede estar roto o la página fue movida.</p>
    <div class="error-view__actions">
      <a class="error-view__btn" href="<?= e(url('/')) ?>">Volver al inicio</a>
      <a class="error-view__btn error-view__btn--primary" href="<?= e(url('/productos')) ?>">Ver productos</a>
    </div>
  </section>
</main>
