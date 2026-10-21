<?php
namespace App\Services\Catalog;

/**
 * Genera metadatos SEO para el listado de productos.
 * Reglas clave:
 * - Canonical SIEMPRE excluye parámetros de presentación (perPage, orden).
 * - Prev/Next SIEMPRE preservan los parámetros de presentación vigentes (perPage, orden),
 *   además de los filtros de contenido (q, categoria, sub, min, max) y la página.
 */
final class SeoService
{
    /**
     * @param array $filters  Filtros normalizados (q, categoria, sub, min, max, orden, perPage, page, etc.)
     * @param array $pager    ['total'=>int, 'page'=>int, 'per_page'=>int]
     * @param callable|null $labelResolver  function(string $slug): string  (mapear slug→nombre legible)
     * @return array{title:string,description:string,canonical:string,prev:?string,next:?string}
     */
    public function buildListSeo(array $filters, array $pager, ?callable $labelResolver = null): array
    {
        $page   = (int)($pager['page']      ?? $filters['page']      ?? 1);
        $per    = (int)($pager['per_page']  ?? $filters['per_page']  ?? $filters['perPage'] ?? 12);
        $total  = (int)($pager['total']     ?? 0);

        $catSlug = (string)($filters['categoria']        ?? $filters['category_slug']    ?? '');
        $subSlug = (string)($filters['sub']              ?? $filters['subcategory_slug'] ?? '');
        $brandSlugs = $this->brandSlugs($filters);
        $brandSlug = $brandSlugs[0] ?? '';
        $q       = trim((string)($filters['q']           ?? ''));
        $min     = trim((string)($filters['min']         ?? ''));
        $max     = trim((string)($filters['max']         ?? ''));
        $orden   = (string)($filters['orden']            ?? $filters['order'] ?? 'relevancia');
        $perPage = (int)   ($filters['perPage']          ?? $filters['per_page'] ?? $per);

        $label = function(string $slug) use ($labelResolver): string {
            if ($slug === '') return '';
            if ($labelResolver) {
                try { return (string)$labelResolver($slug); } catch (\Throwable $e) {}
            }
            return $slug;
        };

        $catName = $label($catSlug);
        $subName = $label($subSlug);
        $brandName = $label($brandSlug);

        /* ===== Title ===== */
        $titleParts = [];
        if ($q !== '')         $titleParts[] = 'Resultados para “' . $q . '”';
        if ($subName !== '')   $titleParts[] = $subName;
        if ($catName !== '' && $subName === '') $titleParts[] = $catName;
        if ($brandName !== '') $titleParts[] = $brandName;
        if (!$titleParts)      $titleParts[] = 'Catálogo';
        if ($page > 1)         $titleParts[] = 'Página ' . $page;
        $title = implode(' — ', $titleParts) . ' | Mi Pesca & Aventura';

        /* ===== Description (<= ~155 chars) ===== */
        $descParts = [];
        $countText = ($total <= 10) ? ($total . ' productos') : ('Más de ' . $total . ' productos');
        if ($q !== '')            $descParts[] = 'Buscá “' . $q . '”';
        if ($subName || $catName) $descParts[] = 'Explorá ' . ($subName ?: $catName);
        else                      $descParts[] = 'Explorá nuestro catálogo';
        $descParts[] = $countText;
        $descParts[] = 'Envíos a todo el país';
        $description = implode('. ', $descParts) . '.';
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($description) > 155) {
                $description = mb_substr($description, 0, 152) . '...';
            }
        } else {
            if (strlen($description) > 155) {
                $description = substr($description, 0, 152) . '...';
            }
        }

        /* ===== Canonical / Prev / Next ===== */
        // Canonical PRO:
        // - SOLO por intención (categoría/subcategoría).
        // - Nunca incluye q/min/max/orden/perPage/page para evitar duplicados infinitos.
        // Canonical y robots (Lote 10):
        // - /productos es el catálogo general.
        // - /productos?categoria=... y /productos?marca=... son LEGACY: canonical a landings SEO (/categoria, /marca)
        $catC   = trim((string)($filters['categoria'] ?? ''));
        $subC   = trim((string)($filters['sub'] ?? ''));
        $brandC = $brandSlugs[0] ?? '';
        $hasSingleBrand = count($brandSlugs) === 1;

        $base = function(string $path = '/productos'): string {
            return function_exists('base_url') ? base_url($path) : $path;
        };
        $build = function(string $path, array $q = []) use ($base): string {
            $b = $base($path);
            return $q ? ($b . '?' . http_build_query($q)) : $b;
        };

        $isLegacyCategory = ($catC !== '' || $subC !== '');
        $isLegacyBrand    = $hasSingleBrand && $brandC !== '';

        // Nav (prev/next) preserva filtros y presentación + page.
        $navQuery = [];
        foreach (['q','categoria','sub','marca','min','max'] as $key) {
            $value = $filters[$key] ?? null;
            if (is_array($value)) {
                $value = array_values(array_filter(array_map(static fn($item): string => trim((string)$item), $value), static fn(string $item): bool => $item !== ''));
                if (!empty($value)) {
                    $navQuery[$key] = $value;
                }
                continue;
            }
            $v = trim((string)$value);
            if ($v !== '') $navQuery[$key] = $v;
        }
        // Presentación
        $navQuery['orden']   = $orden;
        $navQuery['perPage'] = $perPage;
        if ($page > 1) $navQuery['page'] = $page;

        // Canonical:
        // - Legacy categoría/sub → /categoria?slug=...
        // - Legacy marca → /marca?slug=...
        // - Catálogo general → /productos
        if ($isLegacyBrand) {
            $canonical = $build('/marca', ['slug' => $brandC]);
        } elseif ($isLegacyCategory) {
            $target = ($subC !== '') ? $subC : $catC;
            $canonical = $build('/categoria', ['slug' => $target]);
        } else {
            $canonical = $build('/productos');
        }

        // Prev/Next preservando perPage/orden
        $prev = null; $next = null;
        if ($page > 1) {
            $qPrev = $navQuery;
            if ($page - 1 > 1) $qPrev['page'] = $page - 1; else unset($qPrev['page']);
            $prev = $build('/productos', $qPrev);
        }
        if ($total > $page * $per) {
            $qNext = $navQuery; $qNext['page'] = $page + 1;
            $next = $build('/productos', $qNext);
        }

        // Robots (SEO pro): evitamos indexar combinaciones infinitas.
        // Indexamos SOLO páginas de intención "limpia":
        // - Catálogo base o (categoría/subcategoría) en página 1, sin búsqueda ni rango de precio
        // - Presentación (orden/perPage) y paginación: noindex,follow
        $robots = 'index,follow';

        // Legacy (categoría/marca) siempre noindex: la intención vive en /categoria y /marca.
        if ($isLegacyBrand || $isLegacyCategory) {
            $robots = 'noindex,follow';
        }

        // Para catálogo general: solo indexamos la versión "limpia".
        if ($page > 1) $robots = 'noindex,follow';
        if ($q !== '' || $min !== '' || $max !== '') $robots = 'noindex,follow';
        if ($orden !== 'relevancia') $robots = 'noindex,follow';
        if ((int)$perPage !== 12) $robots = 'noindex,follow';

        return [
            'title'       => $title,
            'description' => $description,
            'canonical'   => $canonical,
            'prev'        => $prev,
            'next'        => $next,
            'robots'      => $robots,
        ];
    }

    /** @return array<int,string> */
    private function brandSlugs(array $filters): array
    {
        $brandSlug = trim((string)($filters['brand_slug'] ?? ''));
        if ($brandSlug !== '') {
            return [$brandSlug];
        }

        $rawBrands = $filters['marca'] ?? [];
        $brands = is_array($rawBrands) ? $rawBrands : [$rawBrands];

        return array_values(array_filter(array_map(static fn($value): string => trim((string)$value), $brands), static fn(string $value): bool => $value !== ''));
    }
}
