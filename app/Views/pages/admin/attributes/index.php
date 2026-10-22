<?php
$pageTitle = 'Atributos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$indexView = is_array($attributes_index_view ?? null) ? $attributes_index_view : [];
extract($indexView, EXTR_SKIP);

// Estado navegable: sin JavaScript el servidor entrega únicamente el panel elegido.
$requestedTab = (string)($_GET['tab'] ?? '');
$activeTab = $requestedTab === 'templates' ? 'templates' : 'attributes';
if ($requestedTab === '' && (($sets_q ?? '') !== '' || (int)($sets_category_id ?? 0) > 0 || (int)($sets_brand_id ?? 0) > 0)) {
    $activeTab = 'templates'; // Compatibilidad de enlaces anteriores a las pestañas.
}
$tabParams = [
    'q' => (string)($q ?? ''),
    'sets_q' => (string)($sets_q ?? ''),
    'sets_category_id' => (int)($sets_category_id ?? 0),
    'sets_brand_id' => (int)($sets_brand_id ?? 0),
];
$tabUrl = static fn(string $tab): string => url('/admin/attributes') . '?' . http_build_query(['tab' => $tab] + $tabParams);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-attributes av3-attributes--index" data-active-tab="<?= e($activeTab) ?>">
    <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/flashes.php'; ?>
    <main class="av3-ui-card av3-attributes__board" aria-label="Administración de atributos">
      <nav class="av3-attributes__tabs" role="tablist" aria-label="Secciones de atributos" data-role="attributes-tabs">
        <a role="tab" id="av3-tab-attributes" href="<?= e($tabUrl('attributes')) ?>"
           aria-controls="atributos-listado" aria-selected="<?= $activeTab === 'attributes' ? 'true' : 'false' ?>"
           data-attr-tab="attributes">Atributos</a>
        <a role="tab" id="av3-tab-templates" href="<?= e($tabUrl('templates')) ?>"
           aria-controls="plantillas-listado" aria-selected="<?= $activeTab === 'templates' ? 'true' : 'false' ?>"
           data-attr-tab="templates">Plantillas de variantes</a>
      </nav>
      <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/attributes_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/attributes/index/sets_card.php'; ?>
    </main>
  </div>
</section>
