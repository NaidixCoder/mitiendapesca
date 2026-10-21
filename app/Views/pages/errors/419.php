<?php
http_response_code(419);
$pageTitle = '419 - Sesión expirada | Mi Pesca & Aventura';
$pageDesc  = 'Tu sesión de formulario expiró o el token de seguridad es inválido.';
$extraHead = '<meta name="robots" content="noindex,nofollow">';
?>

<main class="error-view">
  <section class="error-view__inner" aria-labelledby="error-title">
    <figure class="error-view__media">
      <img src="<?= e(asset('img/undraw_fixing-bugs_1ytu.svg')) ?>" alt="">
    </figure>
    <p class="error-view__code">Error 419</p>
    <h1 id="error-title" class="error-view__title">La sesión expiró</h1>
    <p class="error-view__copy">Por seguridad, el token del formulario ya no es válido. Recargá la página e intentá nuevamente.</p>
    <div class="error-view__actions">
      <button class="error-view__btn" type="button" onclick="window.location.reload()">Recargar página</button>
      <a class="error-view__btn error-view__btn--primary" href="<?= e(url('/login')) ?>">Ir al login</a>
    </div>
  </section>
</main>
