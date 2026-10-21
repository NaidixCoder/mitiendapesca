<?php
// Permite ajustar el ancho desde el layout (mobile vs desktop)
$searchWrapClass = $searchWrapClass ?? 'ph-search-form ph-search-form-desktop';
$searchSheetTrigger = !empty($searchSheetTrigger);
?>
<form
    class="<?= e($searchWrapClass) ?>"
    method="get"
    action="<?= base_url('productos') ?>"
    role="search"
    aria-label="Buscar productos"
    data-search-form="productos"
>
    <input
        type="text"
        name="q"
        placeholder="Buscar canas, reels, senuelos..."
        data-search-input
        <?= $searchSheetTrigger ? 'data-ph-search-open-input' : '' ?>
        class="ph-search-input" />
    <button type="submit"
        class="ph-search-btn"
        aria-label="Buscar" title="Buscar">
        <?= ui_icon('search', 'ph-search-icon') ?>
    </button>
</form>
