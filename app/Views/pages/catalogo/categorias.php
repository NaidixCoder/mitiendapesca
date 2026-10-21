<?php
$pageId = 'directory-categories';
$cats = is_array($facets['categories'] ?? null) ? $facets['categories'] : [];
$items = [];

foreach ($cats as $category) {
    $slug = trim((string)($category['slug'] ?? ''));
    $name = trim((string)($category['name'] ?? ''));
    if ($slug === '' || $name === '') {
        continue;
    }

    $items[] = [
        'name' => $name,
        'count' => (int)($category['count'] ?? 0),
        'href' => url('/categoria') . '?slug=' . urlencode($slug),
        'image_url' => public_url((string)($category['cover_image'] ?? '')),
    ];
}

section('partials/directory/page', [
    'title' => 'Categorias',
    'lead' => 'Explora el catalogo por tipo de producto y encuentra mas rapido lo que necesitas para tu proxima salida de pesca.',
    'allProductsUrl' => url('/productos'),
    'allProductsLabel' => 'Ver todos los productos',
    'emptyTitle' => 'Todavia no hay categorias cargadas.',
    'emptyCopy' => 'Cuando tengamos categorias visibles en tienda, las vas a ver aca.',
    'items' => $items,
    'related' => $related ?? [],
]);
