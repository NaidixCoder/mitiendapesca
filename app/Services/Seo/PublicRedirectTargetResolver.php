<?php
declare(strict_types=1);

namespace App\Services\Seo;

use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Seo\SeoRedirectRepository;
use App\Services\Catalog\ProductPublicVisibilityService;
use PDO;

/**
 * Resolver público de redirects SEO. Solo rutas locales conocidas, sin
 * inferir nuevas equivalencias ni alterar el historial.
 * Fail-closed: una cadena dudosa nunca produce header Location.
 */
final class PublicRedirectTargetResolver
{
    private const MAX_HOPS = 12;
    private SeoRedirectRepository $redirects;
    private CategoryRepository $categories;
    private ProductPublicVisibilityService $products;

    public function __construct(private PDO $db)
    {
        $this->redirects = new SeoRedirectRepository($db);
        $this->categories = new CategoryRepository($db);
        $this->products = new ProductPublicVisibilityService($db);
    }

    /**
     * @return array{target:string,status:int,hops:int}|null
     * El primer registro define el estado; una cadena que incluya 302
     * degrada a 302 para no fijar permanentemente un redirect temporal.
     */
    public function resolve(string $from): ?array
    {
        $source = $this->normalize($from, false);
        if ($source === null) return null;

        $seen = [$source => true];
        $current = $from;
        $status = 301;
        for ($hop = 0; $hop < self::MAX_HOPS; $hop++) {
            // La búsqueda usa la clave literal persistida, no normaliza
            // ni reescribe seo_redirects ni seo_slug_history.
            $row = $this->redirects->findTargetByFrom($current);
            if (!$row) {
                if ($hop === 0 || !$this->isEligibleTerminal($current)) return null;
                return ['target' => $this->normalize($current, true), 'status' => $status, 'hops' => $hop];
            }

            $code = (int)($row['status_code'] ?? 0);
            if (!in_array($code, [301, 302], true)) return null;
            if ($hop === 0) $status = $code;
            elseif ($code === 302) $status = 302;

            $next = (string)($row['to_path'] ?? '');
            $normalized = $this->normalize($next, false);
            if ($normalized === null || isset($seen[$normalized])) return null;
            $seen[$normalized] = true;
            $current = $next;
        }
        return null;
    }

    /** Comprueba un destino legacy, sin buscar aliases implícitos. */
    public function resolveDirect(string $target): ?string
    {
        $canonical = $this->normalize($target, true);
        if ($canonical === null) return null;
        // Evitar que el redirect legacy genere una nueva cadena SEO.
        // Una cadena explícita sana colapsa; otra inválida se rechaza.
        if ($this->redirects->findTargetByFrom($target)) {
            return $this->resolve($target)['target'] ?? null;
        }
        return $this->isEligibleTerminal($canonical) ? $canonical : null;
    }

    /**
     * Destino ÚNICO de canonicalización de tracking.
     * Prefiere mapping histórico explícito; en URLs legacy reutiliza
     * exactamente las reglas públicas del resolver y nunca crea un alias SQL.
     * @return array{target:string,status:int,hops:int,historical:bool}|null
     */
    public function resolveCleanedRequest(string $cleaned): ?array
    {
        $history = $this->resolve($cleaned);
        if ($history !== null) {
            $history['historical'] = true;
            return $history;
        }

        $parts = parse_url($cleaned);
        if (!is_array($parts)) return null;
        if (($parts['path'] ?? '') === '/productos' && isset($parts['query'])) {
            parse_str((string)$parts['query'], $get);
            if (array_key_exists('categoria',$get) || array_key_exists('marca',$get)) {
                $category = $get['categoria'] ?? null;
                $brand = $get['marca'] ?? null;
                if (($category !== null && !is_string($category)) ||
                    ($brand !== null && !is_string($brand))) return null;
                $category = trim((string)$category);
                $brand = trim((string)$brand);
                if ($category === '' && $brand === '') return null;
                $isCat = $category !== '';
                $parameters = ['slug' => $isCat ? $category : $brand];
                if ($isCat && isset($get['sub'])) $parameters['sub'] = $get['sub'];
                foreach (['q','min','max','orden','page','perPage','per_page'] as $key) {
                    if (isset($get[$key]) && $get[$key] !== '') $parameters[$key] = $get[$key];
                }
                $target = ($isCat ? '/categoria' : '/marca') . '?' .
                    http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
                $safe = $this->resolveDirect($target);
                return $safe === null ? null : [
                    'target'=>$safe,'status'=>301,'hops'=>0,'historical'=>false
                ];
            }
        }

        $safe = $this->resolveDirect($cleaned);
        return $safe === null ? null : [
            'target'=>$safe,'status'=>301,'hops'=>0,'historical'=>false
        ];
    }

    private function isEligibleTerminal(string $target): bool
    {
        $canonical = $this->normalize($target, true);
        if ($canonical === null) return false;
        $p = parse_url($canonical);
        if (!is_array($p)) return false;
        $path = $p['path'] ?? '';
        if (in_array($path, ['/', '/productos', '/categorias', '/marcas', '/terminos', '/privacidad'], true)) {
            return true;
        }
        parse_str((string)($p['query'] ?? ''), $params);
        $slug = (string)($params['slug'] ?? '');
        if ($path === '/categoria') {
            $row = $this->categories->findActiveBySlug($slug);
            if (!$row || !$this->isIndexable('category', (int)$row['id'], '/categoria?slug=' . urlencode($slug))) return false;
            if (isset($params['sub'])) {
                $child = $this->categories->findActiveBySlug((string)$params['sub']);
                if (!$child || (int)$child['id'] === (int)$row['id']) return false;
                // Recorrido iterativo batch, sin una consulta por ancestro.
                return in_array(
                    (int)$child['id'],
                    $this->products->descendantCategoryIds((int)$row['id']),
                    true
                );
            }
            return true;
        }
        if ($path === '/producto') {
            $st = $this->db->prepare('SELECT id,category_id,is_active FROM products WHERE slug=? LIMIT 1');
            $st->execute([$slug]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row && $this->products->permitsProduct((int)$row['category_id'], (int)$row['is_active'] === 1)
                && $this->isIndexable('product', (int)$row['id'], '/producto?slug=' . urlencode($slug));
        }
        if ($path === '/marca') {
            $st = $this->db->prepare('SELECT id FROM brands WHERE slug=? LIMIT 1');
            $st->execute([$slug]);
            $id = (int)($st->fetchColumn() ?: 0);
            return $id > 0 && $this->isIndexable('brand', $id, '/marca?slug=' . urlencode($slug));
        }
        return false;
    }

    /** No redirigir persistentemente hacia una URL noindex/no canónica. */
    private function isIndexable(string $type, int $id, string $own): bool
    {
        $st = $this->db->prepare(
            'SELECT robots_index,canonical_url FROM seo_settings WHERE entity_type=? AND entity_id=? LIMIT 1'
        );
        $st->execute([$type, $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return true;
        if ((int)$row['robots_index'] === 0) return false;
        $editorial = trim((string)($row['canonical_url'] ?? ''));
        if ($editorial === '') return true;
        if (!preg_match('~^https?://~i', $editorial)) {
            $editorial = function_exists('base_url') ? base_url('/' . ltrim($editorial, '/')) : $editorial;
        }
        $expected = function_exists('url') ? url($own) : $own;
        return $editorial === $expected;
    }

    /**
     * @param bool $terminal Rechaza rutas intermedias desconocidas solo si terminal.
     * @return string|null ruta canónica local, sin hosts/esquemas ni encoding ambiguo
     */
    private function normalize(string $value, bool $terminal): ?string
    {
        if ($value === '' || strlen($value) > 2048 || $value[0] !== '/' ||
            str_starts_with($value, '//') || str_contains($value, '\\') ||
            preg_match('/[\x00-\x20\x7F]/', $value) || str_contains($value, '#') ||
            preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|2f|5c|25)/i', $value)) return null;

        $parsed = parse_url($value);
        if (!is_array($parsed) || isset($parsed['host'], $parsed['scheme']) ||
            isset($parsed['host']) || isset($parsed['port']) || isset($parsed['user']) || isset($parsed['pass'])) return null;
        $path = (string)($parsed['path'] ?? '');
        if ($path === '' || str_contains($path, '%') || str_contains($path, '//') ||
            preg_match('~(^|/)\.{1,2}(/|$)~', $path) || preg_match('~[^/a-zA-Z0-9._-]~', $path)) return null;
        if ($path !== '/') $path = rtrim($path, '/');

        $allowed = [
            '/' => [], '/productos' => ['q','min','max','orden','page','perPage','per_page'],
            '/categorias' => [], '/marcas' => [], '/terminos' => [], '/privacidad' => [],
            '/producto' => ['slug'], '/categoria' => ['slug','sub','q','min','max','orden','page','perPage','per_page'],
            '/marca' => ['slug','q','min','max','orden','page','perPage','per_page'],
        ];
        if ($terminal && !array_key_exists($path,$allowed)) return null;

        $query = (string)($parsed['query'] ?? '');
        if ($query === '') {
            if ($terminal && in_array($path, ['/producto','/categoria','/marca'], true)) return null;
            return $path;
        }
        if (strlen($query) > 700 || preg_match('/%(?:0[0-9a-f]|1[0-9a-f]|2f|5c|25)/i',$query)) return null;
        $parts = explode('&',$query);
        $params = [];
        foreach ($parts as $part) {
            if ($part === '' || !str_contains($part, '=')) return null;
            [$key,$rawValue] = explode('=', $part, 2);
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/',$key) || isset($params[$key])) return null;
            if ($terminal && !in_array($key,$allowed[$path] ?? [], true)) return null;
            if (!$terminal && !in_array($key, ['slug','sub','q','min','max','orden','page','perPage','per_page'],true)) return null;
            $v = urldecode($rawValue);
            if (strlen($v)>200 || !preg_match('//u',$v) ||
                preg_match('/[\x00-\x1F\x7F\\\\\/]/u',$v) ||
                preg_match('/%[0-9A-F]{2}/i',$v)) return null;
            if (in_array($key,['slug','sub'],true)) {
                if (!preg_match('/^[\pL\pN][\pL\pN._-]{0,159}$/u',$v)) return null;
            } elseif (in_array($key,['page','perPage','per_page'],true)) {
                if (!preg_match('/^[1-9][0-9]{0,5}$/',$v)) return null;
            } elseif (in_array($key,['min','max'],true)) {
                if (!preg_match('/^[0-9]{1,12}(?:[.,][0-9]{1,2})?$/',$v)) return null;
            } elseif ($key === 'orden') {
                if (!preg_match('/^[a-zA-Z0-9_-]{1,32}$/',$v)) return null;
            } elseif ($key === 'q' && trim($v)==='') return null;
            $params[$key]=$v;
        }
        if ($terminal && in_array($path,['/producto','/categoria','/marca'],true) && empty($params['slug'])) return null;
        return $path.'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986);
    }
}
