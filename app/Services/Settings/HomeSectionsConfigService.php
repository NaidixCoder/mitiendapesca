<?php
namespace App\Services\Settings;

final class HomeSectionsConfigService
{
    public function defaults(): array
    {
        return [
            'version' => 2,
            'sections' => [
                [
                    'key' => 'featured',
                    'template' => 'products_grid',
                    'enabled' => 1,
                    'title' => 'Destacados',
                    'subtitle' => 'Seleccionados para vos',
                    'badge' => 'En stock',
                    'product_ids' => [],
                ],
                [
                    'key' => 'new_arrivals',
                    'template' => 'products_grid',
                    'enabled' => 1,
                    'title' => 'Nuevos ingresos',
                    'subtitle' => 'Lo ultimo en el shop',
                    'badge' => 'Nuevo',
                    'product_ids' => [],
                ],
            ],
            'youtube' => [
                'product_ids' => [],
                'avatar_fallback_url' => '',
            ],
        ];
    }

    public function sanitizeProductIds($raw): array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $parts = preg_split('/[,\s]+/', (string)$raw) ?: [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $parts), static fn(int $v): bool => $v > 0)));
        return array_slice($ids, 0, 12);
    }

    public function sanitizeAssetUrl($raw): string
    {
        $value = trim((string)$raw);
        if ($value === '') return '';
        $value = str_replace('\\', '/', $value);
        if (preg_match('~^https?://~i', $value)) return $value;
        if (str_starts_with($value, '/uploads/')) return $value;
        $posUploads = stripos($value, '/uploads/');
        if ($posUploads !== false) {
            return substr($value, $posUploads);
        }
        return '';
    }

    public function sanitizeKey($raw, string $fallback = 'section'): string
    {
        $key = strtolower(trim((string)$raw));
        $key = preg_replace('/[^a-z0-9_\-]+/', '_', $key);
        $key = trim((string)$key, '_-');
        if ($key === '') $key = $fallback;
        return substr($key, 0, 40);
    }

    public function normalize(array $decoded): array
    {
        $defaults = $this->defaults();
        $sections = [];
        $youtube = is_array($decoded['youtube'] ?? null) ? $decoded['youtube'] : [];

        if (isset($decoded['sections']) && is_array($decoded['sections'])) {
            foreach ($decoded['sections'] as $idx => $raw) {
                if (!is_array($raw)) continue;
                $fallback = 'section_' . ((int)$idx + 1);
                $key = $this->sanitizeKey($raw['key'] ?? '', $fallback);
                $title = trim((string)($raw['title'] ?? ''));
                $subtitle = trim((string)($raw['subtitle'] ?? ''));
                $badge = trim((string)($raw['badge'] ?? ''));
                $sections[] = [
                    'key' => $key,
                    'template' => 'products_grid',
                    'enabled' => !empty($raw['enabled']) ? 1 : 0,
                    'title' => $title !== '' ? $title : 'Seccion',
                    'subtitle' => $subtitle !== '' ? $subtitle : 'Seleccionados para vos',
                    'badge' => $badge !== '' ? $badge : 'En stock',
                    'product_ids' => $this->sanitizeProductIds($raw['product_ids'] ?? []),
                ];
            }
        }

        if ($sections === []) {
            $sections = (array)($defaults['sections'] ?? []);
        }

        $used = [];
        foreach ($sections as $i => $section) {
            $baseKey = $this->sanitizeKey($section['key'] ?? ('section_' . ($i + 1)));
            $key = $baseKey;
            $n = 2;
            while (isset($used[$key])) {
                $key = $baseKey . '_' . $n;
                $n++;
            }
            $used[$key] = 1;
            $sections[$i]['key'] = $key;
            $sections[$i]['template'] = 'products_grid';
        }

        return [
            'version' => 2,
            'sections' => array_values($sections),
            'youtube' => [
                'product_ids' => $this->sanitizeProductIds($youtube['product_ids'] ?? []),
                'avatar_fallback_url' => $this->sanitizeAssetUrl($youtube['avatar_fallback_url'] ?? ''),
            ],
        ];
    }

    public function read(?string $json): array
    {
        $defaults = $this->defaults();
        $raw = is_string($json) ? trim($json) : '';
        if ($raw === '') return $defaults;

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) return $defaults;
        return $this->normalize($decoded);
    }
}
