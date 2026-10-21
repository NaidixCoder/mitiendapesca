<?php
$pageId = 'directory-brands';
$items = [];

foreach ((array)$brands as $brand) {
    $slug = trim((string)($brand['slug'] ?? ''));
    $name = trim((string)($brand['name'] ?? ''));
    if ($slug === '' || $name === '') {
        continue;
    }

    $items[] = [
        'name' => $name,
        'count' => (int)($brand['product_count'] ?? 0),
        'href' => url('/marca') . '?slug=' . urlencode($slug),
        'image_url' => public_url((string)($brand['cover_image'] ?? '')),
    ];
}

section('partials/directory/page', [
    'title' => 'Marcas',
    'lead' => 'Explorá las mejores marcas y encontrá todos sus productos en un solo lugar.',
    'allProductsUrl' => url('/productos'),
    'allProductsLabel' => 'Ver todos los productos',
    'emptyTitle' => 'Todavia no hay marcas cargadas.',
    'emptyCopy' => 'Cuando sumemos marcas visibles en tienda, las vas a ver aca.',
    'items' => $items,
    'related' => $related ?? [],
    'cardVariant' => 'brand',
]);
