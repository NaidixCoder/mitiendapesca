<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$paths = [
    'index' => '/app/Views/pages/admin/attributes/index.php',
    'attrPartial' => '/app/Views/partials/admin/attributes/index/attributes_card.php',
    'form' => '/app/Views/pages/admin/attributes/form.php',
    'sets' => '/app/Views/partials/admin/attributes/index/sets_card.php',
    'edit' => '/app/Views/pages/admin/attributes/value_set_edit.php',
    'css' => '/public_html/assets/css/admin-v3/pages/attributes.css',
    'js' => '/public_html/assets/js/admin/attributes/index.js',
    'catalog' => '/app/Controllers/Admin/Concerns/AttributesCatalogActions.php',
    'actions' => '/app/Controllers/Admin/Concerns/AttributesValueSetActions.php',
];
$files = [];
foreach ($paths as $key => $suffix) {
    $files[$key] = @file_get_contents($root . $suffix);
    if ($files[$key] === false) throw new RuntimeException("Atributos: falta {$key}");
}
$required = [
    'index' => ['av3-attributes--index', 'role="tablist"', 'role="tab"', 'aria-selected=', 'attributes_card.php', 'sets_card.php'],
    'attrPartial' => ['role="tabpanel"', 'av3-attributes__mobile-list', 'av3-attributes__list-table', 'name="tab" value="attributes"'],
    'form' => ['av3-attributes--form', 'name="name"', 'name="code"', 'name="slug"', 'name="type"', 'name="unit"', 'name="value"', 'name="sort"', 'name="value_id"', 'data-confirm-value-delete'],
    'sets' => ['data-role="set-view"', 'data-role="set-edit"', 'data-role="set-delete"', 'data-role="set-modal"', 'data-role="set-modal-body"', 'role="dialog"', 'aria-modal="true"', 'name="tab" value="templates"', 'av3-attributes__mobile-list', 'av3-attributes__advanced'],
    'edit' => ['data-role="set-edit-form"', 'name="category_id"', 'name="brand_id"', 'name="values[', 'data-role="set-add-values"', 'data-role="set-add-attr"', 'data-role="set-save"'],
    'css' => ['body[data-page="admin-attributes"]', 'container-type:inline-size', '@container (min-width:510px)', '@container (min-width:780px)', '@media (min-width:768px)', '.av3-attributes__modal[hidden]', '.av3-attributes__mobile-list'],
    'js' => ['export function init()', 'document.body.appendChild(modal)', 'requestToken', 'event.key === \'Escape\'', 'new FormData(form)', 'aria-busy', 'history.pushState', 'popstate', 'ArrowRight', 'aria-selected'],
    'catalog' => [':q_name', ':q_slug', "'extraHead'"],
    'actions' => ['verify_csrf_or_abort()', 'is_post()', "require base_path('app/Views/pages/admin/attributes/value_set_edit.php')"],
];
foreach ($required as $key => $terms) {
    foreach ($terms as $term) {
        if (!str_contains($files[$key], $term)) throw new RuntimeException("Contrato Atributos: {$key} sin {$term}");
    }
}
foreach (['max-h-[420px]', 'text-[color:var(', 'av3-lg-grid-cols-2', 'name="is_active"'] as $legacy) {
    if (str_contains($files['index'] . $files['form'] . $files['sets'] . $files['edit'], $legacy)) {
        throw new RuntimeException("Contrato Atributos: clase/campo legacy {$legacy}");
    }
}
echo "[atributos-v3] OK: rutas conservadas, hooks AJAX, formulario, modal, CSS scoped y responsive\n";
