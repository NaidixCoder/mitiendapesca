<?php
namespace App\Queries\Admin;

use PDO;

final class SeoAuditQuery
{
    public function __construct(private PDO $db) {}

    /**
     * Busca productos para la auditoría SEO global.
     * Filtros soportados:
     * - q: texto (name/sku/slug)
     * - active: 0/1
     * - issue: meta_title|meta_desc|imagen|precio|rich_bullets|rich_specs|rich_faq
     * - score_min / score_max: 0..100
     * - page (>=1), per (1..200)
     * - sort: score|name|created_at
     * - order: asc|desc
     */
    public function search(array $filters): array
    {
        $q      = trim((string)($filters['q'] ?? ''));
        $active = isset($filters['active']) && $filters['active'] !== '' ? (int)$filters['active'] : null;

        $issue  = trim((string)($filters['issue'] ?? ''));
        $scoreMin = isset($filters['score_min']) && $filters['score_min'] !== '' ? max(0, min(100, (int)$filters['score_min'])) : null;
        $scoreMax = isset($filters['score_max']) && $filters['score_max'] !== '' ? max(0, min(100, (int)$filters['score_max'])) : null;

        $page = max(1, (int)($filters['page'] ?? 1));
        $per  = max(1, min(200, (int)($filters['per']  ?? 25)));
        $off  = ($page - 1) * $per;

        $sort  = strtolower((string)($filters['sort']  ?? 'score'));
        $order = strtolower((string)($filters['order'] ?? 'asc'));
        if (!in_array($order, ['asc','desc'], true)) $order = 'asc';

        $allowedSort = [
            'score'      => 't.seo_score',
            'name'       => 't.name',
            'created_at' => 't.created_at',
        ];
        $orderBy = $allowedSort[$sort] ?? $allowedSort['score'];

        $params = [];
        $where  = [];

        if ($q !== '') {
            $where[] = "(t.name LIKE :q_name OR t.sku LIKE :q_sku OR t.slug LIKE :q_slug)";
            $params[':q_name'] = "%{$q}%";
            $params[':q_sku'] = "%{$q}%";
            $params[':q_slug'] = "%{$q}%";
        }
        if ($active !== null) {
            $where[] = "t.is_active = :act";
            $params[':act'] = $active;
        }
        if ($scoreMin !== null) {
            $where[] = "t.seo_score >= :smin";
            $params[':smin'] = $scoreMin;
        }
        if ($scoreMax !== null) {
            $where[] = "t.seo_score <= :smax";
            $params[':smax'] = $scoreMax;
        }

        if ($issue !== '') {
            switch ($issue) {
                case 'meta_title':
                    $where[] = "t.meta_title_ok = 0";
                    break;
                case 'meta_desc':
                    $where[] = "t.meta_desc_ok = 0";
                    break;
                case 'imagen':
                    $where[] = "t.has_cover = 0";
                    break;
                case 'precio':
                    $where[] = "t.has_price = 0";
                    break;
                case 'rich_bullets':
                    $where[] = "(t.bullets_json IS NULL OR t.bullets_json = '' OR t.bullets_json = '[]')";
                    break;
                case 'rich_specs':
                    $where[] = "(t.specs_json IS NULL OR t.specs_json = '' OR t.specs_json = '[]')";
                    break;
                case 'rich_faq':
                    $where[] = "(t.faq_json IS NULL OR t.faq_json = '' OR t.faq_json = '[]')";
                    break;
                default:
                    // ignore
                    break;
            }
        }

        $whereSql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

        // Subquery con columnas calculadas para poder filtrar/ordenar sin duplicar lógica.
        $baseSql = "
          SELECT
            p.id,
            p.sku,
            p.slug,
            p.name,
            p.is_active,
            p.sale_mode,
            p.price,
            p.meta_title,
            p.meta_description,
            p.created_at,
            b.name AS brand_name,
            c.name AS category_name,
            sb.bullets_json,
            sb.specs_json,
            sb.faq_json,

            -- checks
            CASE
              WHEN p.meta_title IS NOT NULL AND TRIM(p.meta_title) <> '' AND CHAR_LENGTH(p.meta_title) BETWEEN 25 AND 60
              THEN 1 ELSE 0
            END AS meta_title_ok,

            CASE
              WHEN p.meta_description IS NOT NULL AND TRIM(p.meta_description) <> '' AND CHAR_LENGTH(p.meta_description) BETWEEN 70 AND 160
              THEN 1 ELSE 0
            END AS meta_desc_ok,

            CASE
              WHEN EXISTS (SELECT 1 FROM product_images i WHERE i.product_id = p.id AND i.is_cover = 1)
              THEN 1 ELSE 0
            END AS has_cover,

            CASE
              WHEN p.sale_mode = 'variants' THEN
                CASE
                  WHEN EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1 AND v.price IS NOT NULL AND v.price > 0)
                  THEN 1 ELSE 0
                END
              ELSE
                CASE WHEN p.price IS NOT NULL AND p.price > 0 THEN 1 ELSE 0 END
            END AS has_price,

            -- score (misma lógica que SeoAuditService)
            LEAST(
              100,
              (CASE WHEN TRIM(COALESCE(p.name,'')) <> '' THEN 15 ELSE 0 END) +
              (CASE WHEN TRIM(COALESCE(p.slug,'')) <> '' THEN 10 ELSE 0 END) +
              (CASE WHEN (p.meta_title IS NOT NULL AND TRIM(p.meta_title) <> '' AND CHAR_LENGTH(p.meta_title) BETWEEN 25 AND 60) THEN 20 ELSE 10 END) +
              (CASE WHEN (p.meta_description IS NOT NULL AND TRIM(p.meta_description) <> '' AND CHAR_LENGTH(p.meta_description) BETWEEN 70 AND 160) THEN 20 ELSE 10 END) +
              (CASE WHEN EXISTS (SELECT 1 FROM product_images i2 WHERE i2.product_id = p.id AND i2.is_cover = 1) THEN 20 ELSE 0 END) +
              (CASE
                 WHEN p.sale_mode = 'variants' THEN
                   (CASE WHEN EXISTS (SELECT 1 FROM product_variants v2 WHERE v2.product_id = p.id AND v2.is_active = 1 AND v2.price IS NOT NULL AND v2.price > 0) THEN 15 ELSE 0 END)
                 ELSE
                   (CASE WHEN p.price IS NOT NULL AND p.price > 0 THEN 15 ELSE 0 END)
               END)
            ) AS seo_score

          FROM products p
          LEFT JOIN brands b ON b.id = p.brand_id
          LEFT JOIN categories c ON c.id = p.category_id
          LEFT JOIN product_seo_blocks sb ON sb.product_id = p.id
        ";

        // Resumen independiente de los filtros: solo productos activos.
        // Reutiliza el mismo SQL de score que el listado; no hay cálculo duplicado.
        $summarySql = "SELECT COUNT(*) AS active,
            COALESCE(SUM(CASE WHEN t.seo_score < 65 THEN 1 ELSE 0 END), 0) AS low,
            COALESCE(SUM(CASE WHEN t.seo_score BETWEEN 65 AND 84 THEN 1 ELSE 0 END), 0) AS medium,
            COALESCE(SUM(CASE WHEN t.seo_score >= 85 THEN 1 ELSE 0 END), 0) AS good
            FROM ({$baseSql}) t WHERE t.is_active = 1";
        $summary = $this->db->query($summarySql)->fetch(PDO::FETCH_ASSOC) ?: [];

        $sql = "SELECT * FROM ({$baseSql}) t {$whereSql} ORDER BY {$orderBy} {$order} LIMIT :lim OFFSET :off";
        $countSql = "SELECT COUNT(*) FROM ({$baseSql}) t {$whereSql}";

        $stC = $this->db->prepare($countSql);
        foreach ($params as $k => $v) { $stC->bindValue($k, $v); }
        $stC->execute();
        $total = (int)$stC->fetchColumn();

        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) { $st->bindValue($k, $v); }
        $st->bindValue(':lim', $per, PDO::PARAM_INT);
        $st->bindValue(':off', $off, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $pages = (int)max(1, ceil($total / max(1, $per)));

        return [
            'rows'  => $rows,
            'total' => $total,
            'page'  => $page,
            'per'   => $per,
            'pages' => $pages,
            'summary' => [
                'active' => (int)($summary['active'] ?? 0),
                'low' => (int)($summary['low'] ?? 0),
                'medium' => (int)($summary['medium'] ?? 0),
                'good' => (int)($summary['good'] ?? 0),
            ],
        ];
    }
}
