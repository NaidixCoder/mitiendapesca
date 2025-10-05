<?php
namespace App\Controllers\Catalog;

use App\Repositories\ProductRepository;
use PDO;

class ProductController
{
    public function show(): void
    {
        $slug = (string)($_GET['slug'] ?? '');
        if ($slug === '') { http_response_code(400); echo 'Falta slug'; return; }

        $db   = db();
        $repo = new ProductRepository($db);

        $product = $repo->findBySlug($slug);
        if (!$product) { http_response_code(404); echo 'Producto no encontrado'; return; }

        // SEO
        $title = $product['title'] ?? 'Producto';
        $pageTitle = $title . ' | Mi Pesca & Aventura';
        $pageDesc  = $product['short'] ?? 'Ficha, precio y envío a todo el país.';
        $firstImg  = ($product['images'][0] ?? null);
        $ogImage   = $firstImg ? public_url($firstImg) : asset('img/branding/og-default.jpg');

        // JSON-LD
        $trail = [
            ['label'=>'Inicio','url'=>'/'],
            ['label'=>'Productos','url'=>'/productos'],
        ];
        if (!empty($product['category'])) {
            $trail[] = ['label'=>ucfirst($product['category']), 'url'=>'/productos?categoria='.$product['category']];
        }
        $trail[] = ['label'=>$title, 'url'=>null];

        $items = [];
        foreach ($trail as $i => $t) {
            $items[] = [
                '@type'=>'ListItem','position'=>$i+1,'name'=>$t['label']
            ] + (!empty($t['url']) ? ['item'=>url(ltrim($t['url'],'/'))] : []);
        }
        $ldBreadcrumbs = ['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$items];

        $inStock  = ((int)($product['stock'] ?? 0) > 0);
        $ldProduct = [
          '@context'=>'https://schema.org','@type'=>'Product',
          'name'=>$title,'sku'=>$product['sku'] ?? null,
          'brand'=>!empty($product['brand']) ? ['@type'=>'Brand','name'=>$product['brand']] : null,
          'image'=>array_map(fn($p)=>public_url($p), $product['images'] ?: [asset('img/branding/og-default.jpg')]),
          'description'=>$product['short'] ?? '',
          'aggregateRating'=>(!empty($product['reviews']) ? [
              '@type'=>'AggregateRating','ratingValue'=>(float)($product['rating'] ?? 0),'reviewCount'=>(int)$product['reviews']
          ] : null),
          'offers'=>[
            '@type'=>'Offer','url'=>url('producto?slug='.$product['slug']),
            'priceCurrency'=>'ARS','price'=>(int)$product['price'],
            'availability'=>'https://schema.org/'.($inStock ? 'InStock' : 'OutOfStock'),
            'itemCondition'=>'https://schema.org/NewCondition'
          ]
        ];

        $extraHead = ''
          . '<meta property="og:image" content="'.e($ogImage).'">'
          . '<meta name="twitter:image" content="'.e($ogImage).'">'
          . '<script type="application/ld+json">'.json_encode($ldBreadcrumbs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>'
          . '<script type="application/ld+json">'.json_encode($ldProduct, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';

        // Relacionados
        $related = $repo->related($product, 8);

        render_page('catalogo/producto', compact('product','pageTitle','pageDesc','extraHead','related','trail'));
    }
}
