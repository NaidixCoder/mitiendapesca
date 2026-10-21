<?php
$mobileSearchCategories = [];
try {
    $st = db()->prepare(
        "SELECT slug, name
         FROM categories
         WHERE is_active = 1
         ORDER BY sort ASC, name ASC
         LIMIT 8"
    );
    $st->execute();
    $mobileSearchCategories = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable) {
    $mobileSearchCategories = [];
}
?>

<div class="ph-search-overlay" data-ph-search-overlay hidden></div>

<section
  class="ph-search-sheet"
  data-ph-search-sheet
  role="dialog"
  aria-modal="true"
  aria-label="Busqueda de productos"
  hidden
>
  <div class="ph-search-sheet-head">
    <div class="ph-search-sheet-input-wrap">
      <?php $searchWrapClass = 'ph-search-form ph-search-form-sheet'; ?>
      <?php require BASE_PATH.'/app/Views/partials/header/_search.php'; ?>
    </div>
    <button
      type="button"
      class="ph-icon-btn ph-search-sheet-close"
      data-ph-search-close
      aria-label="Cerrar buscador"
      title="Cerrar"
    >
      <?= ui_icon('close', 'ph-icon', ['stroke-width' => '1.9']) ?>
    </button>
  </div>

  <div class="ph-search-sheet-body">
    <section class="ph-search-block" data-ph-search-recent-block hidden>
      <div class="ph-search-block-head">
        <h3 class="ph-search-block-title">Busquedas recientes</h3>
        <button type="button" class="ph-search-clear-btn" data-ph-search-recent-clear>Limpiar</button>
      </div>
      <div class="ph-search-chip-list" data-ph-search-recent-list></div>
    </section>

    <section class="ph-search-block">
      <h3 class="ph-search-block-title">Categorias</h3>
      <div class="ph-search-chip-list">
        <?php foreach ($mobileSearchCategories as $cat): ?>
          <?php
            $name = (string)($cat['name'] ?? '');
            $slug = (string)($cat['slug'] ?? '');
            if ($name === '' || $slug === '') continue;
          ?>
          <a class="ph-search-chip ph-search-chip-link" href="<?= e(base_url('/categoria?slug=' . urlencode($slug))) ?>">
            <?= e($name) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="ph-search-block">
      <h3 class="ph-search-block-title">Resultados</h3>
      <div class="ph-search-results" data-ph-search-results></div>
      <p class="ph-search-empty" data-ph-search-empty>Escribe al menos 2 caracteres para buscar.</p>
    </section>
  </div>
</section>
