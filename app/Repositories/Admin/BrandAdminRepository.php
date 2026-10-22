<?php
namespace App\Repositories\Admin;

use PDO;

/**
 * Consultas administrativas de marcas. No altera los criterios de publicación
 * del catálogo público ni la relación entre marcas y productos.
 */
final class BrandAdminRepository
{
    public function __construct(private PDO $db) {}

    public function find(int $id): ?array
    {
        if ($id <= 0) return null;
        $st = $this->db->prepare('SELECT * FROM brands WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return array{rows:array,total:int} */
    public function listPage(string $query, int $page, int $perPage): array
    {
        $query = trim($query);
        $perPage = in_array($perPage, [25, 50], true) ? $perPage : 25;
        $where = '';
        $bindings = [];
        if ($query !== '') {
            $where = "WHERE (b.name LIKE :name_query ESCAPE '=' OR b.slug LIKE :slug_query ESCAPE '=')";
            // El usuario busca texto literal: %, _ y el carácter de escape no son comodines.
            $escaped = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $query);
            $term = '%' . $escaped . '%';
            $bindings = [':name_query' => $term, ':slug_query' => $term];
        }

        $count = $this->db->prepare("SELECT COUNT(*) FROM brands b $where");
        foreach ($bindings as $key => $value) $count->bindValue($key, $value, PDO::PARAM_STR);
        $count->execute();
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT b.id, b.name, b.slug, b.sort, b.cover_image, b.created_at,
                (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id) AS product_count,
                (SELECT COUNT(*) FROM products p WHERE p.brand_id = b.id AND p.is_active = 1) AS active_product_count
                FROM brands b $where
                ORDER BY b.sort ASC, b.name ASC, b.id ASC
                LIMIT :limit OFFSET :offset";
        $st = $this->db->prepare($sql);
        foreach ($bindings as $key => $value) $st->bindValue($key, $value, PDO::PARAM_STR);
        $st->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return ['rows' => $st->fetchAll(PDO::FETCH_ASSOC) ?: [], 'total' => $total];
    }

    public function slugExists(string $slug, int $exceptId = 0): bool
    {
        $st = $this->db->prepare('SELECT id FROM brands WHERE slug = ? AND id <> ? LIMIT 1');
        $st->execute([$slug, $exceptId]);
        return $st->fetchColumn() !== false;
    }
}
