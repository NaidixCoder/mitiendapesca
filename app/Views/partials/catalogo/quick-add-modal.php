<?php
/**
 * Modal de agregado rápido para productos con variantes.
 * Mantiene ids/data-* usados por JS.
 */
?>

<div id="quick-add-modal" class="catalog-quick-add ui-is-hidden" aria-hidden="true">
  <div class="catalog-quick-add__backdrop" data-quick-add-close></div>

  <div class="catalog-quick-add__dialog">
    <div class="catalog-quick-add__panel">
      <div class="catalog-quick-add__header">
        <div class="catalog-quick-add__eyebrow">Elegí tu variante</div>
        <button type="button" class="catalog-quick-add__close" aria-label="Cerrar" data-quick-add-close>
          <?= ui_icon('close') ?>
        </button>
      </div>

      <div id="quick-add-body" class="catalog-quick-add__body">
        <div class="catalog-quick-add__loading">Cargando...</div>
      </div>
    </div>
  </div>
</div>
