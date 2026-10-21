<?php
http_response_code(400);
$pageTitle = '400 - Solicitud inválida | Mi Pesca & Aventura';
$pageDesc  = 'La solicitud no pudo procesarse.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<main class="error-view">
  <section class="error-view__inner" aria-labelledby="error-title">
    <figure class="error-view__media">
      <img src="<?= e(asset('img/undraw_fixing-bugs_1ytu.svg')) ?>" alt="">
    </figure>
    <p class="error-view__code">Error 400</p>
    <h1 id="error-title" class="error-view__title">Solicitud inválida</h1>
    <p class="error-view__copy">No pudimos procesar la solicitud. Revisá los datos e intentá nuevamente.</p>
    <div class="error-view__actions">
      <a class="error-view__btn" href="<?= e(url('/')) ?>">Volver al inicio</a>
      <button class="error-view__btn error-view__btn--primary" type="button" onclick="window.history.back()">Volver atrás</button>
    </div>
  </section>
</main>
