<?php
namespace App\Controllers\System;

class MetaController
{
    public function robots(): void
    {
        // Asegurar headers correctos incluso si hubo output previo
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        header('Content-Type: text/plain; charset=utf-8');


        $base = rtrim(function_exists('url') ? url('/') : '/', '/');

        echo "User-agent: *\n";
        // Áreas privadas / de checkout (no deberían indexarse)
        echo "Disallow: /admin/\n";
        echo "Disallow: /cuenta/\n";
        echo "Disallow: /login\n";
        echo "Disallow: /registro\n";
        echo "Disallow: /checkout\n";
        echo "Disallow: /pago\n";
        echo "Disallow: /logout\n";
        echo "Disallow: /webhooks/\n";

        // Assets y uploads públicos (permitidos)
        echo "Allow: /assets/\n";
        echo "Allow: /uploads/\n";

        // Sitemaps
        echo "Sitemap: {$base}/sitemap.xml\n";
    }

    /**
     * Sitemap index (recomendado por Google para tiendas con crecimiento).
     * - /sitemap.xml => sitemapindex
     * - /sitemap_pages.xml, /sitemap_products.xml, /sitemap_categories.xml
     */
    public function sitemap(): void
    {
        // Asegurar headers correctos incluso si hubo output previo
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim(function_exists('url') ? url('/') : '/', '/');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        // Solo para visualización humana en el navegador (los bots ignoran esto)
        echo "<?xml-stylesheet type=\"text/xsl\" href=\"{$base}/assets/sitemap.xsl\"?>\n";

        echo "\n<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $add = function(string $path, ?string $lastmod = null) use ($base): void {
            $loc = htmlspecialchars($base . $path, ENT_QUOTES, 'UTF-8');
            $lm  = htmlspecialchars($lastmod ?: date('c'), ENT_QUOTES, 'UTF-8');
            echo "  <sitemap><loc>{$loc}</loc><lastmod>{$lm}</lastmod></sitemap>\n";
        };

        $add('/sitemap_pages.xml');
        $add('/sitemap_categories.xml');
        $add('/sitemap_brands.xml');
        $add('/sitemap_products.xml');

        echo "</sitemapindex>";
    }

    public function sitemapPages(): void
    {
        // Asegurar headers correctos incluso si hubo output previo
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim(function_exists('url') ? url('/') : '/', '/');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?xml-stylesheet type=\"text/xsl\" href=\"{$base}/assets/sitemap.xsl\"?>\n";

        echo "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $addUrl = function(string $pathOrUrl, ?string $lastmod = null, string $freq = 'weekly', string $prio = '0.7') use ($base): void {
            $locUrl = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : ($base . $pathOrUrl);
            $loc = htmlspecialchars($locUrl, ENT_QUOTES, 'UTF-8');
            $lm  = htmlspecialchars($lastmod ?: date('c'), ENT_QUOTES, 'UTF-8');
            echo "  <url><loc>{$loc}</loc><lastmod>{$lm}</lastmod><changefreq>{$freq}</changefreq><priority>{$prio}</priority></url>\n";
        };

        $addUrl('/', null, 'daily', '1.0');
        $addUrl('/productos', null, 'daily', '0.9');
        $addUrl('/terminos', null, 'yearly', '0.3');
        $addUrl('/privacidad', null, 'yearly', '0.3');
        $addUrl('/categorias', null, 'weekly', '0.6');
        $addUrl('/marcas', null, 'weekly', '0.6');

        echo "</urlset>";
    }

    public function sitemapProducts(): void
    {
        // Asegurar headers correctos incluso si hubo output previo
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim(function_exists('url') ? url('/') : '/', '/');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?xml-stylesheet type=\"text/xsl\" href=\"{$base}/assets/sitemap.xsl\"?>\n";

        echo "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $addUrl = function(string $pathOrUrl, ?string $lastmod = null, string $freq = 'weekly', string $prio = '0.8') use ($base): void {
            $locUrl = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : ($base . $pathOrUrl);
            $loc = htmlspecialchars($locUrl, ENT_QUOTES, 'UTF-8');
            $lm  = htmlspecialchars($lastmod ?: date('c'), ENT_QUOTES, 'UTF-8');
            echo "  <url><loc>{$loc}</loc><lastmod>{$lm}</lastmod><changefreq>{$freq}</changefreq><priority>{$prio}</priority></url>\n";
        };

        try {
            $db = db();
            // B1 y SEO se filtran ANTES de emitir las URL. Una consulta,
            // sin N+1 ni reutilizar selectores de administración/importaciones.
            $visibility = new \App\Services\Catalog\ProductPublicVisibilityService($db);
            $sql = "SELECT p.slug, p.created_at, ss.canonical_url
                    FROM products p
                    LEFT JOIN seo_settings ss
                      ON ss.entity_type='product' AND ss.entity_id=p.id
                    WHERE " . $visibility->predicate('p') . "
                      AND (ss.id IS NULL OR ss.robots_index = 1)
                    ORDER BY p.id DESC";
            $st = $db->query($sql);
            $rows = $st ? $st->fetchAll(\PDO::FETCH_ASSOC) : [];
            foreach ($rows as $r) {
                $slug = trim((string)($r['slug'] ?? ''));
                if ($slug === '') continue;

                // Una URL con canonical editorial distinto NO debe ser
                // anunciada como otra página canónica del sitemap.
                $ownCanonical = url('/producto?slug=' . urlencode($slug));
                $editorialCanonical = trim((string)($r['canonical_url'] ?? ''));
                if ($editorialCanonical !== '') {
                    if (!preg_match('~^https?://~i', $editorialCanonical)) {
                        $editorialCanonical = base_url('/' . ltrim($editorialCanonical, '/'));
                    }
                    if ($editorialCanonical !== $ownCanonical) continue;
                }

                $lm = null;
                $ca = (string)($r['created_at'] ?? '');
                if ($ca !== '') {
                    $ts = strtotime($ca);
                    if ($ts) $lm = date('c', $ts);
                }

                $addUrl('/producto?slug=' . rawurlencode($slug), $lm, 'weekly', '0.8');
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        echo "</urlset>";
    }

    public function sitemapCategories(): void
    {
        // Asegurar headers correctos incluso si hubo output previo
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim(function_exists('url') ? url('/') : '/', '/');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?xml-stylesheet type=\"text/xsl\" href=\"{$base}/assets/sitemap.xsl\"?>\n";

        echo "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $addUrl = function(string $pathOrUrl, ?string $lastmod = null, string $freq = 'weekly', string $prio = '0.6') use ($base): void {
            $locUrl = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : ($base . $pathOrUrl);
            $loc = htmlspecialchars($locUrl, ENT_QUOTES, 'UTF-8');
            $lm  = htmlspecialchars($lastmod ?: date('c'), ENT_QUOTES, 'UTF-8');
            echo "  <url><loc>{$loc}</loc><lastmod>{$lm}</lastmod><changefreq>{$freq}</changefreq><priority>{$prio}</priority></url>\n";
        };

        try {
            $db = db();

            // Misma política de visibilidad jerárquica que GET /categoria.
            // Las URLs no elegibles nunca se anuncian a buscadores.
            $rows = (new \App\Repositories\Catalog\CategoryRepository($db))
                ->listPublicVisibleOrdered();

            // Todas las decisiones editoriales en un SELECT batch; no
            // repetir una consulta SEO para cada categoría de la lista.
            $seoRows = $db->query(
                "SELECT entity_id, robots_index, canonical_url FROM seo_settings
                 WHERE entity_type='category'"
            )->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $seoById = [];
            foreach ($seoRows as $seoRow) $seoById[(int)$seoRow['entity_id']] = $seoRow;

            foreach ($rows as $r) {
                $slug = trim((string)($r['slug'] ?? ''));
                if ($slug === '') continue;
                $settings = $seoById[(int)$r['id']] ?? null;
                if ($settings && (int)$settings['robots_index'] === 0) continue;

                $ownCanonical = url('/categoria?slug=' . urlencode($slug));
                $editorialCanonical = trim((string)($settings['canonical_url'] ?? ''));
                if ($editorialCanonical !== '') {
                    if (!preg_match('~^https?://~i', $editorialCanonical)) {
                        $editorialCanonical = base_url('/' . ltrim($editorialCanonical, '/'));
                    }
                    if ($editorialCanonical !== $ownCanonical) continue;
                }

                $lm = null;
                $ca = (string)($r['created_at'] ?? '');
                if ($ca !== '') {
                    $ts = strtotime($ca);
                    if ($ts) $lm = date('c', $ts);
                }

                $addUrl('/categoria?slug=' . rawurlencode($slug), $lm, 'weekly', '0.6');
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        echo "</urlset>";
    }

    public function sitemapBrands(): void
    {
        // Asegurar headers correctos incluso si hubo output previo
        if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_clean(); }
        header('Content-Type: application/xml; charset=utf-8');
        $base = rtrim(function_exists('url') ? url('/') : '/', '/');
        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<?xml-stylesheet type=\"text/xsl\" href=\"{$base}/assets/sitemap.xsl\"?>\n";

        echo "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        $addUrl = function(string $pathOrUrl, ?string $lastmod = null, string $freq = 'weekly', string $prio = '0.6') use ($base): void {
            $locUrl = str_starts_with($pathOrUrl, 'http') ? $pathOrUrl : ($base . $pathOrUrl);
            $loc = htmlspecialchars($locUrl, ENT_QUOTES, 'UTF-8');
            $lm  = htmlspecialchars($lastmod ?: date('c'), ENT_QUOTES, 'UTF-8');
            echo "  <url><loc>{$loc}</loc><lastmod>{$lm}</lastmod><changefreq>{$freq}</changefreq><priority>{$prio}</priority></url>\n";
        };

        try {
            $db = db();
            $st = $db->query("SELECT slug, created_at FROM brands ORDER BY id DESC");
            $rows = $st ? $st->fetchAll(\PDO::FETCH_ASSOC) : [];
            foreach ($rows as $r) {
                $slug = (string)($r['slug'] ?? '');
                if ($slug === '') continue;

                $lm = null;
                $ca = (string)($r['created_at'] ?? '');
                if ($ca !== '') {
                    $ts = strtotime($ca);
                    if ($ts) $lm = date('c', $ts);
                }

                $addUrl('/marca?slug=' . rawurlencode($slug), $lm, 'weekly', '0.6');
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        echo "</urlset>";
    }
}