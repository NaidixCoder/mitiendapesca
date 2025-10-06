<?php
use App\Repositories\ProductRepository;


$repo = new ProductRepository(db());

// Parámetros de UI
$q = [
  'q'         => $_GET['q'] ?? '',
  'categoria' => $_GET['categoria'] ?? '',
  'min'       => $_GET['min'] ?? '',
  'max'       => $_GET['max'] ?? '',
  'orden'     => $_GET['orden'] ?? 'relevancia',
  'page'      => max(1, (int)($_GET['page'] ?? 1)),
  'perPage'   => 12,
];

// Datos
$pager = $repo->paginate($q);
$products = $pager['items'];
$total    = $pager['total'];
$page     = $pager['page'];
$perPage  = $pager['perPage'];

// Categorías (lista simple para filtros)
$categories = [];
$stmt = db()->query("SELECT slug, name FROM categories WHERE is_active=1 ORDER BY sort ASC, name ASC");
foreach ($stmt->fetchAll() as $c) $categories[] = ['slug'=>$c['slug'],'name'=>$c['name']];

// Orquestación (reutiliza tus partials)
$query = $q['q']; $categoria = $q['categoria']; $min = $q['min']; $max = $q['max']; $orden = $q['orden'];

section('catalogo/filter-bar', compact('query','categoria','min','max','orden','categories'));
section('catalogo/active-filters', compact('query','categoria','min','max'));

if (empty($products)) {
  section('catalogo/empty-state');
} else {
  section('catalogo/product-grid', compact('products'));
  section('catalogo/pagination', compact('total','perPage','page'));
  section('catalogo/seo-text');
}
