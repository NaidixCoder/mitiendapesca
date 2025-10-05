<?php http_response_code(429); ?>
<main class="max-w-md mx-auto p-8 text-center">
    <h1 class="text-2xl font-bold mb-2">Demasiados intentos</h1>
    <p class="opacity-80">Has superado el límite temporal de intentos. Probá nuevamente en unos minutos.</p>
    <a href="<?= base_url('/login') ?>" class="inline-block mt-6 underline">Volver al login</a>
</main>
