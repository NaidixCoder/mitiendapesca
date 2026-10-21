<?php
// Permite ajustar tamaños desde el layout sin duplicar vistas.
$logoImgClass  = $logoImgClass ?? 'ph-logo-img';
$logoLinkClass = $logoLinkClass ?? 'ph-logo-link';
?>
<a href="<?= base_url('/') ?>" class="<?= e($logoLinkClass) ?>" aria-label="Ir al inicio">
    <img src="<?= asset('img/branding/miPescaYaventura-leyenda.png') ?>" alt="Mi Pesca & Aventura" class="<?= e($logoImgClass) ?>" />
</a>
