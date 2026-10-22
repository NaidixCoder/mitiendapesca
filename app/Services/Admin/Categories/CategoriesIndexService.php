<?php
declare(strict_types=1);
namespace App\Services\Admin\Categories;

use PDO;

final class CategoriesIndexService
{
    public function __construct(private PDO $db) {}

    /** @return array<string,mixed> */
    public function search(array $input): array
    {
        $q = mb_substr(trim((string)($input['q'] ?? '')), 0, 100);
        $showInactive = (string)($input['show_inactive'] ?? '') === '1';
        $per = in_array((int)($input['per'] ?? 25), [25, 50], true) ? (int)($input['per'] ?? 25) : 25;
        $requestedPage = max(1, min(100000, (int)($input['page'] ?? 1)));
        $where = [];
        $params = [];
        if (!$showInactive) $where[] = 'c.is_active = 1';
        if ($q !== '') {
            $where[] = '(c.name LIKE :name OR c.slug LIKE :slug)';
            $params[':name'] = '%'.$q.'%';
            $params[':slug'] = '%'.$q.'%';
        }
        $predicate = $where ? ' WHERE '.implode(' AND ', $where) : '';

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM categories c'.$predicate);
        foreach ($params as $key => $val) $countStmt->bindValue($key, $val);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));
        $page = min($requestedPage, $pages);

        $stmt = $this->db->prepare('
            SELECT c.id, c.name, c.slug, c.parent_id, p.name AS parent_name,
                   c.sort, c.is_active, c.cover_image,
                   COALESCE(k.child_count, 0) AS child_count,
                   COALESCE(pr.product_count, 0) AS product_count
            FROM categories c
            LEFT JOIN categories p ON p.id = c.parent_id
            LEFT JOIN (SELECT parent_id, COUNT(*) AS child_count
                       FROM categories WHERE parent_id IS NOT NULL GROUP BY parent_id) k ON k.parent_id = c.id
            LEFT JOIN (SELECT category_id, COUNT(*) AS product_count
                       FROM products GROUP BY category_id) pr ON pr.category_id = c.id
            '.$predicate.'
            ORDER BY (c.parent_id IS NULL) DESC, COALESCE(p.name, \'\'), c.sort, c.name, c.id
            LIMIT :limit OFFSET :offset
        ');
        foreach ($params as $key => $val) $stmt->bindValue($key, $val);
        $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', ($page - 1) * $per, PDO::PARAM_INT);
        $stmt->execute();

        $summary = $this->db->query('
            SELECT COUNT(*) AS total,
                   COALESCE(SUM(is_active = 1), 0) AS active,
                   COALESCE(SUM(parent_id IS NULL), 0) AS roots
            FROM categories
        ')->fetch(PDO::FETCH_ASSOC) ?: [];

        // Estado administrativo y elegibilidad pública son DOS cosas distintas.
        // Un nodo activo bajo un ancestro inactivo/huérfano/cíclico no se publica.
        // Una sola lectura O(N), sin consulta por fila/paginación.
        $visibleSet = (new \App\Services\Catalog\CategoryPublicVisibilityService($this->db))->visibleIdSet();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['public_visible'] = isset($visibleSet[(int)$row['id']]);
        }
        unset($row);

        $urlForPage = static function(int $p) use ($q, $showInactive, $per): string {
            $filters = ['page' => $p, 'per' => $per];
            if ($q !== '') $filters['q'] = $q;
            if ($showInactive) $filters['show_inactive'] = '1';
            return url('/admin/categories') . '?' . http_build_query($filters);
        };

        return [
            'rows' => $rows,
            'q' => $q, 'showInactive' => $showInactive, 'per' => $per,
            'page' => $page, 'pages' => $pages, 'total' => $total,
            'start' => $total ? (($page - 1) * $per + 1) : 0,
            'end' => min($total, $page * $per),
            'summary' => [
                'total' => (int)($summary['total'] ?? 0),
                'active' => (int)($summary['active'] ?? 0),
                'roots' => (int)($summary['roots'] ?? 0),
                'public_visible' => count($visibleSet),
            ],
            'previousUrl' => $page > 1 ? $urlForPage($page - 1) : null,
            'nextUrl' => $page < $pages ? $urlForPage($page + 1) : null,
        ];
    }
}
