<?php
/**
 * Carrito vacío - Búsquedas recientes (localStorage)
 * - Se hidrata con public_html/assets/js/tracking/recent_searches.js
 * - Solo se muestra si hay historial
 */

$basePath = url('/productos');
?>

<section class="cart-recent-searches" data-recent-searches-wrapper hidden>
  <div class="cart-recent-searches__head">
    <div class="cart-recent-searches__title">Búsquedas recientes</div>
    <button type="button" class="cart-recent-searches__clear" data-recent-searches-clear>
      Borrar historial
    </button>
  </div>
  <div class="cart-recent-searches__list" data-recent-searches data-base-path="<?= e($basePath) ?>"></div>
</section>
