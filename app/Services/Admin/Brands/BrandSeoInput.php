<?php
declare(strict_types=1);

namespace App\Services\Admin\Brands;

/**
 * Valida antes del guardado los límites reales de los repositorios SEO.
 * Nada se descarta/trunca silenciosamente: filas vacías son placeholders,
 * filas incompletas o fuera de rango requieren corrección en el formulario.
 */
final class BrandSeoInput
{
    /** @return array{settings:array<string,string>,bullets:array,specs:array,faq:array,errors:array<int,string>} */
    public static function parse(array $post): array
    {
        $errors = [];
        $get = static function (string $field, int $max) use ($post, &$errors): string {
            $v = $post[$field] ?? '';
            if (!is_string($v)) {
                $errors[] = "El campo $field no es válido.";
                return '';
            }
            $v = trim($v);
            if (mb_strlen($v) > $max) $errors[] = "El campo $field supera $max caracteres.";
            return $v;
        };
        $fields = [
            'seo_canonical_override' => 255, 'seo_og_title' => 255,
            'seo_og_description' => 500, 'seo_og_image' => 255,
            'seo_schema_override_jsonld' => 65000,
        ];
        $data = [];
        foreach ($fields as $key => $max) $data[$key] = $get($key, $max);

        $schema = $data['seo_schema_override_jsonld'];
        if ($schema !== '' && json_decode($schema) === null && json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'El JSON-LD debe contener JSON válido.';
        }

        $index = $get('seo_index', 16);
        $follow = $get('seo_follow', 16);
        $twitter = $get('seo_twitter_card', 32);
        if (!in_array($index, ['index','noindex'], true)) $errors[] = 'Indexación inválida.';
        if (!in_array($follow, ['follow','nofollow'], true)) $errors[] = 'Seguimiento de enlaces inválido.';
        if (!in_array($twitter, ['', 'summary', 'summary_large_image'], true)) $errors[] = 'Twitter Card inválida.';

        $lists = [];
        foreach (['seo_bullets','seo_specs_label','seo_specs_value','seo_faq_q','seo_faq_a'] as $key) {
            $val = $post[$key] ?? [];
            if (!is_array($val) || count($val) > 25) {
                $errors[] = "La lista $key es inválida o demasiado larga.";
                $lists[$key] = [];
                continue;
            }
            $lists[$key] = [];
            foreach (array_values($val) as $v) {
                if (!is_string($v)) {
                    $errors[] = "La lista $key contiene datos inválidos.";
                    $v = '';
                }
                $lists[$key][] = trim($v);
            }
        }

        $bullets = [];
        foreach ($lists['seo_bullets'] as $v) {
            if ($v === '') continue;
            if (mb_strlen($v) > 140) $errors[] = 'Cada punto clave admite hasta 140 caracteres.';
            $bullets[] = $v;
        }

        $pairs = static function (array $first, array $second, int $maxA, int $maxB, string $label, string $a, string $b) use (&$errors): array {
            $result = [];
            for ($i = 0; $i < max(count($first), count($second)); $i++) {
                $x = $first[$i] ?? '';
                $y = $second[$i] ?? '';
                if ($x === '' && $y === '') continue;
                if ($x === '' || $y === '') {
                    $errors[] = "Completá ambos campos de $label en la fila " . ($i + 1) . '.';
                    continue;
                }
                if (mb_strlen($x) > $maxA || mb_strlen($y) > $maxB) {
                    $errors[] = "$label excede el tamaño permitido en la fila " . ($i + 1) . '.';
                }
                $result[] = [$a => $x, $b => $y];
            }
            return $result;
        };
        $specs = $pairs($lists['seo_specs_label'], $lists['seo_specs_value'], 80, 200, 'Especificaciones', 'label', 'value');
        $faq = $pairs($lists['seo_faq_q'], $lists['seo_faq_a'], 160, 600, 'Preguntas frecuentes', 'q', 'a');
        if (count($bullets) > 20 || count($specs) > 20 || count($faq) > 20) {
            $errors[] = 'Cada sección de contenido admite hasta 20 elementos.';
        }
        return [
            'settings' => [
                'robots' => $index . ',' . $follow,
                'canonical_override' => $data['seo_canonical_override'],
                'og_title' => $data['seo_og_title'],
                'og_description' => $data['seo_og_description'],
                'og_image' => $data['seo_og_image'],
                'twitter_card' => $twitter,
                'schema_override_jsonld' => $schema,
            ],
            'bullets' => $bullets,
            'specs' => $specs,
            'faq' => $faq,
            'errors' => $errors,
        ];
    }
}
