<?php
namespace App\Services\Catalog;

final class FilterChipService
{
    private string $baseUrl;
    /** @var null|callable */
    private $qsBuild;
    /** @var array<string,mixed>|null */
    private ?array $labelMap = null;
    /** @var null|callable */
    private $labelResolver;

    public function __construct(string $baseUrl = '/productos', $qsBuild = null, ?array $labelMap = null, $labelResolver = null)
    {
        $this->baseUrl = $baseUrl;
        $this->qsBuild = $qsBuild;
        $this->labelMap = $labelMap;
        $this->labelResolver = $labelResolver;
    }

    /**
     * @return array{chips: array<int, array{label:string, href:string}>, clearAll: string}
     */
    public function make(array $filters): array
    {
        $q = trim((string)($filters['q'] ?? ''));
        $cat = trim((string)($filters['categoria'] ?? ($filters['category_slug'] ?? '')));
        $sub = trim((string)($filters['sub'] ?? ($filters['subcategory_slug'] ?? '')));
        $min = trim((string)($filters['min'] ?? ''));
        $max = trim((string)($filters['max'] ?? ''));
        $orden = (string)($filters['orden'] ?? 'relevancia');
        $brands = $this->brandSlugs($filters);
        $tagSelections = $this->tagSelections($filters);
        $routeSlug = trim((string)($filters['slug'] ?? ''));
        $isCategoryLanding = $this->baseUrl === '/categoria' && $routeSlug !== '';
        $isBrandLanding = $this->baseUrl === '/marca' && $routeSlug !== '';

        $chips = [];

        if ($q !== '') {
            $chips[] = ['label' => 'Busqueda: ' . $q, 'href' => $this->url([], ['q', 'page'])];
        }

        if ($cat !== '' && $sub === '' && !($isCategoryLanding && $routeSlug === $cat)) {
            $chips[] = ['label' => $this->pretty($cat), 'href' => $this->url([], ['categoria', 'category_slug', 'page'])];
        }

        if ($cat !== '' && $sub !== '') {
            $chips[] = ['label' => $this->pretty($cat), 'href' => $this->url(['sub' => null, 'subcategory_slug' => null, 'page' => 1])];
            $chips[] = ['label' => $this->pretty($sub), 'href' => $this->url([], ['sub', 'subcategory_slug', 'page'])];
        }

        foreach ($brands as $brand) {
            if ($brand === '' || ($isBrandLanding && $routeSlug === $brand)) {
                continue;
            }
            $remainingBrands = array_values(array_filter($brands, static fn(string $value): bool => $value !== $brand));
            $chips[] = [
                'label' => 'Marca: ' . $this->pretty($brand),
                'href' => $this->url(['marca' => $remainingBrands ?: null], ['brand_slug', 'page']),
            ];
        }

        foreach ($tagSelections as $groupSlug => $options) {
            $groupName = $this->tagGroupLabel($groupSlug);
            foreach ($options as $option) {
                $remaining = array_values(array_filter($options, static fn(array $row): bool => (string)$row['slug'] !== (string)$option['slug']));
                $nextTags = $this->rawTagFilters($filters);
                if ($remaining === []) {
                    unset($nextTags[$groupSlug]);
                } else {
                    $nextTags[$groupSlug] = array_map(static fn(array $row): string => (string)$row['slug'], $remaining);
                }
                $chips[] = [
                    'label' => $groupName . ': ' . (string)$option['label'],
                    'href' => $this->url(['tag' => $nextTags ?: null], ['page']),
                ];
            }
        }

        if ($min !== '') {
            $chips[] = ['label' => 'Min: ' . money((int)$min, 0), 'href' => $this->url([], ['min', 'page'])];
        }

        if ($max !== '') {
            $chips[] = ['label' => 'Max: ' . money((int)$max, 0), 'href' => $this->url([], ['max', 'page'])];
        }

        $clearAll = $this->url(
            ['orden' => $orden],
            ['q', 'categoria', 'category_slug', 'sub', 'subcategory_slug', 'marca', 'brand_slug', 'tag', 'min', 'max', 'page']
        );

        return ['chips' => $chips, 'clearAll' => $clearAll];
    }

    private function url(array $add = [], array $drop = []): string
    {
        if (is_callable($this->qsBuild)) {
            $qs = ($this->qsBuild)($add, $drop);
            return $this->base($qs);
        }

        $q = array_merge($_GET ?? [], $add);
        foreach ($drop as $k) {
            unset($q[$k]);
        }
        $q = array_filter($q, fn($v) => $v !== null && $v !== '');
        $qs = $q ? ('?' . http_build_query($q)) : '';
        return $this->base($qs);
    }

    private function base(string $qs): string
    {
        if (function_exists('base_url')) {
            $path = (string)(parse_url(base_url($this->baseUrl), PHP_URL_PATH) ?: $this->baseUrl);
            return rtrim($path, '/') . $qs;
        }

        return $this->baseUrl . $qs;
    }

    private function pretty(string $slug): string
    {
        if (is_callable($this->labelResolver)) {
            try {
                $label = ($this->labelResolver)($slug);
                if (is_string($label) && $label !== '') {
                    return $label;
                }
            } catch (\Throwable $e) {
            }
        }

        if (is_array($this->labelMap) && isset($this->labelMap[$slug])) {
            return (string)$this->labelMap[$slug];
        }

        $tmp = str_replace(['-', '_'], ' ', $slug);
        return mb_convert_case($tmp, MB_CASE_TITLE, 'UTF-8');
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

    /**
     * @return array<string,array<int,array{slug:string,label:string}>>
     */
    private function tagSelections(array $filters): array
    {
        $raw = $this->rawTagFilters($filters);
        $out = [];
        foreach ($raw as $slug => $values) {
            $slug = trim((string)$slug);
            if ($slug === '') {
                continue;
            }
            foreach ((array)$values as $tagSlug) {
                $tagSlug = trim((string)$tagSlug);
                if ($tagSlug === '') {
                    continue;
                }
                $out[$slug][] = [
                    'slug' => $tagSlug,
                    'label' => $this->tagLabel($slug, $tagSlug),
                ];
            }
        }
        return $out;
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function rawTagFilters(array $filters): array
    {
        $raw = $filters['tags'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $slug => $values) {
            $slug = trim((string)$slug);
            if ($slug === '') {
                continue;
            }
            $tagSlugs = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string)$value), is_array($values) ? $values : [$values]), static fn(string $value): bool => $value !== '')));
            if ($tagSlugs === []) {
                continue;
            }
            $out[$slug] = $tagSlugs;
        }
        return $out;
    }

    private function tagGroupLabel(string $slug): string
    {
        $labels = is_array($this->labelMap['tag_group_labels'] ?? null) ? $this->labelMap['tag_group_labels'] : [];
        if (isset($labels[$slug]) && trim((string)$labels[$slug]) !== '') {
            return (string)$labels[$slug];
        }
        return $this->pretty($slug);
    }

    private function tagLabel(string $groupSlug, string $tagSlug): string
    {
        $labels = is_array($this->labelMap['tag_labels'] ?? null) ? $this->labelMap['tag_labels'] : [];
        $key = $groupSlug . ':' . $tagSlug;
        if (isset($labels[$key]) && trim((string)$labels[$key]) !== '') {
            return (string)$labels[$key];
        }
        return $this->pretty($tagSlug);
    }
}
