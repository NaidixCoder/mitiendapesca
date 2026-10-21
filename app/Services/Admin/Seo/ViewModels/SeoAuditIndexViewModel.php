<?php
namespace App\Services\Admin\Seo\ViewModels;

final class SeoAuditIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $page = max(1, (int)($input['page'] ?? 1));
        $pages = max(1, (int)($input['pages'] ?? 1));
        $per = (int)($input['per'] ?? 25);
        $total = (int)($input['total'] ?? 0);

        $f = [
            'q' => trim((string)($filters['q'] ?? '')),
            'active' => (string)($filters['active'] ?? ''),
            'issue' => trim((string)($filters['issue'] ?? '')),
            'score_min' => (string)($filters['score_min'] ?? ''),
            'score_max' => (string)($filters['score_max'] ?? ''),
            'sort' => trim((string)($filters['sort'] ?? 'score')),
            'order' => trim((string)($filters['order'] ?? 'asc')),
        ];

        $issueMap = [
            '' => 'Todos',
            'meta_title' => 'Título SEO incorrecto o faltante',
            'meta_desc' => 'Descripción SEO incorrecta o faltante',
            'imagen' => 'Sin portada (imagen)',
            'precio' => 'Sin precio',
            'rich_bullets' => 'Sin puntos clave',
            'rich_specs' => 'Sin especificaciones',
            'rich_faq' => 'Sin FAQ',
        ];

        $issueOptions = $this->buildOptions($issueMap, $f['issue']);
        $activeOptions = $this->buildOptions([
            '' => 'Todos',
            '1' => 'Activos',
            '0' => 'Inactivos',
        ], $f['active']);
        $sortOptions = $this->buildOptions([
            'score' => 'Score',
            'name' => 'Nombre',
            'created_at' => 'Fecha de creación',
        ], $f['sort']);
        $orderOptions = $this->buildOptions([
            'asc' => 'Ascendente',
            'desc' => 'Descendente',
        ], $f['order']);

        $perOptions = [];
        foreach ([10, 25, 50, 100] as $n) {
            $perOptions[] = [
                'value' => (string)$n,
                'label' => (string)$n,
                'selected' => ($per === $n),
            ];
        }

        $rowsPrepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = (int)($row['id'] ?? 0);
            $score = (int)($row['seo_score'] ?? 0);
            $metaTitleOk = (int)($row['meta_title_ok'] ?? 0);
            $metaDescOk = (int)($row['meta_desc_ok'] ?? 0);
            $hasCover = (int)($row['has_cover'] ?? 0);
            $hasPrice = (int)($row['has_price'] ?? 0);

            $bulletsCount = $this->countJsonList($row['bullets_json'] ?? null);
            $specsCount = $this->countJsonList($row['specs_json'] ?? null);
            $faqCount = $this->countJsonList($row['faq_json'] ?? null);

            $issues = [];
            if ($metaTitleOk === 0) { $issues[] = 'Título SEO'; }
            if ($metaDescOk === 0) { $issues[] = 'Descripción SEO'; }
            if ($hasCover === 0) { $issues[] = 'Sin portada'; }
            if ($hasPrice === 0) { $issues[] = 'Sin precio'; }
            // Contenido complementario: no se suma al score ni se presenta como error SEO.
            $contentMissing = [];
            if ($bulletsCount === 0) { $contentMissing[] = 'Sin puntos clave'; }
            if ($specsCount === 0) { $contentMissing[] = 'Sin especificaciones'; }
            if ($faqCount === 0) { $contentMissing[] = 'Sin FAQ'; }

            $scoreTone = $score >= 85 ? 'success' : ($score >= 65 ? 'warning' : 'danger');

            $slug = trim((string)($row['slug'] ?? ''));
            $publicUrl = url('/producto?slug=' . rawurlencode($slug));
            $name = trim((string)($row['name'] ?? ''));
            $metaTitle = trim((string)($row['meta_title'] ?? ''));
            $metaDesc = trim((string)($row['meta_description'] ?? ''));
            $serpTitle = $metaTitle !== '' ? $metaTitle : ($name !== '' ? $name . ' | Mi Pesca & Aventura' : 'Producto | Mi Pesca & Aventura');
            $serpDesc = $metaDesc !== '' ? $metaDesc : ($name !== '' ? $name : 'Producto');

            $rowsPrepared[] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
                'sku' => (string)($row['sku'] ?? ''),
                'brand_name' => (string)($row['brand_name'] ?? ''),
                'category_name' => (string)($row['category_name'] ?? ''),
                'is_active' => (int)($row['is_active'] ?? 0) === 1,
                'score' => $score,
                'score_tone' => $scoreTone,
                'issues' => $issues,
                'issues_primary' => array_slice($issues, 0, 2),
                'issues_remaining' => array_slice($issues, 2),
                'issues_hidden_count' => max(0, count($issues) - 2),
                'content_missing' => $contentMissing,
                'bullets_count' => $bulletsCount,
                'specs_count' => $specsCount,
                'faq_count' => $faqCount,
                'public_url' => $publicUrl,
                'serp_title' => $serpTitle,
                'serp_desc' => $serpDesc,
                'has_meta_description' => $metaDesc !== '',
                'seo_url' => url('/admin/products/edit?id=' . $id . '&tab=seo'),
            ];
        }

        // URLs preparadas fuera de la vista, manteniendo búsqueda, orden y paginación.
        $urlFor = static function (array $overrides) use ($f, $per): string {
            $query = array_filter(array_replace($f, ['per' => $per], $overrides),
                static fn($v) => $v !== '' && $v !== null);
            return url('/admin/seo/auditoria') . ($query ? '?' . http_build_query($query) : '');
        };
        $clearScope = ['active' => '', 'score_min' => '', 'score_max' => '', 'issue' => '', 'page' => 1];
        $quickLinks = [
            ['label' => 'Todos', 'href' => $urlFor($clearScope),
                'active' => $f['active'] === '' && $f['score_min'] === '' && $f['score_max'] === '' && $f['issue'] === ''],
            ['label' => 'Activos', 'href' => $urlFor(array_replace($clearScope, ['active' => '1'])),
                'active' => $f['active'] === '1' && $f['score_min'] === '' && $f['score_max'] === '' && $f['issue'] === ''],
            ['label' => 'Inactivos', 'href' => $urlFor(array_replace($clearScope, ['active' => '0'])),
                'active' => $f['active'] === '0' && $f['score_min'] === '' && $f['score_max'] === '' && $f['issue'] === ''],
            ['label' => 'Prioridad alta', 'href' => $urlFor(array_replace($clearScope, ['active' => '1', 'score_max' => '64'])),
                'active' => $f['active'] === '1' && $f['score_min'] === '' && $f['score_max'] === '64' && $f['issue'] === ''],
        ];
        $links = [
            ['label' => 'Anterior', 'href' => $urlFor(['page' => max(1, $page - 1)]), 'disabled' => $page <= 1],
        ];
        for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) {
            $links[] = ['label' => (string)$p, 'href' => $urlFor(['page' => $p]), 'active' => $p === $page];
        }
        $links[] = ['label' => 'Siguiente', 'href' => $urlFor(['page' => min($pages, $page + 1)]), 'disabled' => $page >= $pages];
        $advancedCount = (int)($f['issue'] !== '') + (int)($f['active'] !== '')
            + (int)($f['score_min'] !== '') + (int)($f['score_max'] !== '')
            + (int)($per !== 25);
        $advancedOpen = $f['issue'] !== '' || $f['score_min'] !== ''
            || $f['score_max'] !== '' || $per !== 25;
        $first = $rowsPrepared ? (($page - 1) * $per + 1) : 0;
        $last = $rowsPrepared ? ($first + count($rowsPrepared) - 1) : 0;

        return [
            'filters' => $f,
            'issue_options' => $issueOptions,
            'active_options' => $activeOptions,
            'sort_options' => $sortOptions,
            'order_options' => $orderOptions,
            'per_options' => $perOptions,
            'rows_prepared' => $rowsPrepared,
            'pagination_links' => $links,
            'quick_links' => $quickLinks,
            'advanced_count' => $advancedCount,
            'advanced_open' => $advancedOpen,
            'seo_summary' => is_array($input['summary'] ?? null) ? $input['summary'] : [],
            'range_label' => $first . '–' . $last . ' de ' . $total,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per' => $per,
        ];
    }

    /**
     * @param array<string,string> $map
     * @return array<int,array<string,mixed>>
     */
    private function buildOptions(array $map, string $selected): array
    {
        $out = [];
        foreach ($map as $value => $label) {
            $out[] = [
                'value' => $value,
                'label' => $label,
                'selected' => ($value === $selected),
            ];
        }
        return $out;
    }

    /**
     * @param mixed $json
     */
    private function countJsonList($json): int
    {
        if ($json === null) {
            return 0;
        }
        $raw = trim((string)$json);
        if ($raw === '' || $raw === '[]') {
            return 0;
        }

        $arr = json_decode($raw, true);
        if (!is_array($arr)) {
            return 0;
        }

        $count = 0;
        foreach ($arr as $it) {
            if (is_string($it)) {
                if (trim($it) !== '') {
                    $count++;
                }
                continue;
            }
            if (is_array($it)) {
                $joined = '';
                foreach ($it as $v) {
                    if (is_string($v)) {
                        $joined .= trim($v);
                    }
                }
                if ($joined !== '') {
                    $count++;
                }
            }
        }
        return $count;
    }
}




