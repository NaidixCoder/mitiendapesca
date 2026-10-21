<?php
$pageTitle = (string)($pageTitle ?? 'Mi Pesca & Aventura - Equipos, canas, reels y mas');
$pageDesc = (string)($pageDesc ?? 'Catalogo profesional de pesca deportiva. Envios a todo el pais. Ofertas, novedades y marcas lideres.');
$heroSlides = is_array($heroSlides ?? null) ? $heroSlides : [];
$heroTitle = (string)($heroTitle ?? 'Equipá tu próxima pesca con productos de calidad');
$heroDescription = (string)($heroDescription ?? home_hero_description_default());
$homeBrands = is_array($homeBrands ?? null) ? $homeBrands : [];
$homeCategories = is_array($homeCategories ?? null) ? $homeCategories : [];
$renderableSections = is_array($renderableSections ?? null) ? $renderableSections : [];
$youtube = is_array($youtube ?? null) ? $youtube : [];

section('/partials/home/hero_v2', [
    'heroSlides' => $heroSlides,
    'heroTitle' => $heroTitle,
    'heroDescription' => $heroDescription,
    'homeCategories' => $homeCategories,
]);
section('/partials/home/pejerrey-promo');
// section('/partials/home/highlights');
// section('/partials/home/payments');
section('/partials/home/trust-bar', ['homeBrands' => $homeBrands]);
// section('/partials/home/categorias-destacadas', ['homeCategories' => $homeCategories]);

if ($renderableSections !== []) {
    foreach ($renderableSections as $index => $sectionData) {
        section('/partials/home/featured-grid', [
            'items' => (array)($sectionData['items'] ?? []),
            'title' => (string)($sectionData['title'] ?? 'Seccion'),
            'subtitle' => (string)($sectionData['subtitle'] ?? 'Seleccionados para vos'),
            'badge' => (string)($sectionData['badge'] ?? 'En stock'),
        ]);
    }
}

section('/partials/home/seo-info');
section('/partials/home/youtube-channel', ['youtube' => $youtube]);
section('/partials/home/newsletter');
section('/partials/home/testimonials');
