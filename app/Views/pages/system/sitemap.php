<?php
header('Content-Type: application/xml; charset=utf-8');

$now = gmdate('Y-m-d\TH:i:s\Z');
$base = rtrim(base_url('/'), '/');

// Categorías
$cats = db()->query("SELECT slug, GREATEST(COALESCE(MAX(p.created_at),'1970-01-01'), CURRENT_TIMESTAMP) AS lm
                    FROM categories c
                    LEFT JOIN products p ON p.category_id=c.id
                    WHERE c.is_active=1
                    GROUP BY c.id, c.slug
                    ORDER BY c.slug ASC")->fetchAll();

// Productos (limita a 5k por sitemap)
$stm = db()->query("SELECT slug, created_at AS lm FROM products WHERE is_active=1 ORDER BY created_at DESC LIMIT 5000");
$prods = $stm->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?= $base ?>/</loc><lastmod><?= $now ?></lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>
    <url><loc><?= $base ?>/productos</loc><lastmod><?= $now ?></lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>

    <?php foreach ($cats as $c): ?>
    <url>
        <loc><?= $base ?>/productos?categoria=<?= e($c['slug']) ?></loc>
        <lastmod><?= gmdate('Y-m-d\TH:i:s\Z', strtotime($c['lm'])) ?></lastmod>
        <changefreq>weekly</changefreq><priority>0.6</priority>
    </url>
    <?php endforeach; ?>

    <?php foreach ($prods as $p): ?>
    <url>
        <loc><?= $base ?>/producto?slug=<?= e($p['slug']) ?></loc>
        <lastmod><?= gmdate('Y-m-d\TH:i:s\Z', strtotime($p['lm'])) ?></lastmod>
        <changefreq>weekly</changefreq><priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
