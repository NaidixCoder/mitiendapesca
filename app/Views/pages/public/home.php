<?php
// SEO por página
$pageTitle = 'Mi Pesca & Aventura — Equipos, cañas, reels y más';
$pageDesc  = 'Catálogo profesional de pesca deportiva. Envíos a todo el país. Ofertas, novedades y marcas líderes.';

// Mock de datos (luego vendrán del modelo/DB)
$featured = [
    ['title'=>'Reel Shimano Nexave 2500','price'=>'$ 185.000','img'=>'img/products/reel-shimano-nexave.jpg','slug'=>'reel-shimano-nexave-2500'],
    ['title'=>'Caña Okuma 2.10m Carbon','price'=>'$ 142.500','img'=>'img/products/cana-okuma-carbon.jpg','slug'=>'cana-okuma-210-carbon'],
    ['title'=>'Señuelos Rapala Pack x5','price'=>'$ 58.900','img'=>'img/products/rapala-pack.jpg','slug'=>'senuelos-rapala-pack-5'],
    ['title'=>'Línea Surfcasting Pro',  'price'=>'$ 22.300','img'=>'img/products/linea-surf-pro.jpg',   'slug'=>'linea-surfcasting-pro'],
];

$newArrivals = [
    ['title'=>'Reel Abu Garcia Max', 'price'=>'$ 210.000','img'=>'img/products/reel-abu-max.jpg','slug'=>'reel-abu-garcia-max'],
    ['title'=>'Caña Shimano FX 1.98','price'=>'$ 119.900','img'=>'img/products/cana-shimano-fx.jpg','slug'=>'cana-shimano-fx-198'],
    ['title'=>'Multifilamento 300m', 'price'=>'$ 34.700','img'=>'img/products/multifilamento-300.jpg','slug'=>'multifilamento-300m'],
    ['title'=>'Caja Tackle 2 Niveles','price'=>'$ 27.600','img'=>'img/products/caja-tackle-2lvl.jpg','slug'=>'caja-tackle-2-niveles'],
];

// Orquestación de secciones
section('/partials/home/hero');
section('/partials/home/trust-bar');
section('/partials/home/categorias-destacadas');
section('/partials/home/featured-grid',    ['items'=>$featured,    'title'=>'Destacados',     'badge'=>'En stock']);
section('/partials/home/cta-banner');
section('/partials/home/new-arrivals-grid',['items'=>$newArrivals,'title'=>'Nuevos ingresos','badge'=>'Nuevo']);
section('/partials/home/testimonials');
section('/partials/home/newsletter');
section('/partials/home/seo-info');
