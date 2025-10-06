<?php
namespace App\Repositories;

use PDO;

class ProductRepository
{
    public function __construct(private PDO $db) {}

    /** Obtiene un producto por slug con marca, categoría, stock e imágenes */
    public function findBySlug(string $slug): ?array
    {
        $sql = "
            SELECT
                p.*,
                b.name AS brand,
                b.slug AS brand_slug,
                c.slug AS category_slug,
                (SELECT i.stock FROM inventory i WHERE i.product_id = p.id) AS stock
            FROM products p
            LEFT JOIN brands b     ON b.id = p.brand_id
            INNER JOIN categories c ON c.id = p.category_id
            WHERE p.slug = :slug AND p.is_active = 1
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->execute([':slug' => $slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        // Imágenes (cover primero, luego sort, luego id)
        $im = $this->db->prepare("
            SELECT path
            FROM product_images
            WHERE product_id = :pid
            ORDER BY is_cover DESC, sort ASC, id ASC
        ");
        $im->execute([':pid' => $row['id']]);
        $images = array_column($im->fetchAll(PDO::FETCH_ASSOC), 'path');

        $row['images'] = $images;
        $row['thumb']  = !empty($images) ? $images[0] : 'img/placeholder.jpg';

        // Alias de compatibilidad con vistas (si las usan)
        $row['title'] = $row['name'] ?? '';
        $row['short'] = $row['short_desc'] ?? '';

        return $row;
    }

    /** Productos relacionados por categoría o marca (excluye el propio slug) */
    public function related(array $product, int $limit = 8): array
    {
        $sql = "
            SELECT
                p.id, p.slug, p.name, p.price, p.list_price,
                (SELECT pi.path
                 FROM product_images pi
                 WHERE pi.product_id = p.id
                 ORDER BY pi.is_cover DESC, pi.sort ASC, pi.id ASC
                 LIMIT 1) AS thumb
            FROM products p
            WHERE p.is_active = 1
              AND p.slug <> :slug
              AND (p.category_id = :cat_id OR p.brand_id = :brand_id)
            ORDER BY p.created_at DESC
            LIMIT :lim
        ";
        $st = $this->db->prepare($sql);
        $st->bindValue(':slug', (string)($product['slug'] ?? ''), PDO::PARAM_STR);
        $st->bindValue(':cat_id', (int)($product['category_id'] ?? 0), PDO::PARAM_INT);
        $st->bindValue(':brand_id', (int)($product['brand_id'] ?? 0), PDO::PARAM_INT);
        $st->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $r['thumb'] = !empty($r['thumb']) ? public_url($r['thumb']) : asset('img/placeholder.jpg');
        }
        return $rows;
    }

    /** Listado paginado con filtros por q / categoria / marca (id o slug) */
    public function paginate(array $filters = []): array
    {
        $f = array_replace([
            'q'         => '',
            'categoria' => '',   // id o slug
            'marca'     => '',   // id o slug
            'sort'      => 'created_at',
            'order'     => 'desc',
            'page'      => 1,
            'per'       => 24,
            'active'    => 1,
        ], $filters);

        $page = max(1, (int)$f['page']);
        $per  = max(1, min(96, (int)$f['per']));
        $off  = ($page - 1) * $per;

        // Orden seguro (mapea a columnas REALES del schema)
        $sortMap = [
            'created_at' => 'p.created_at',
            'price'      => 'p.price',
            'name'       => 'p.name',
            'title'      => 'p.name', // alias de compatibilidad
            'id'         => 'p.id',
        ];
        $sort  = $sortMap[strtolower((string)$f['sort'])] ?? 'p.created_at';
        $order = in_array(strtolower((string)$f['order']), ['asc','desc'], true) ? strtoupper($f['order']) : 'DESC';

        $where  = [];
        $params = [];

        if ((int)$f['active'] === 1) {
            $where[] = 'p.is_active = 1';
        }

        if ($f['q'] !== '') {
            // FULLTEXT por índice ft_name_desc; fallback a LIKE
            $where[] = '(MATCH(p.name, p.short_desc, p.long_desc) AGAINST (:q IN BOOLEAN MODE)
                        OR p.name LIKE :q_like OR p.short_desc LIKE :q_like)';
            $params[':q']      = '+' . preg_replace('/\s+/', ' +', trim((string)$f['q'])) . '*';
            $params[':q_like'] = '%' . $f['q'] . '%';
        }

        // Categoria: id o slug
        if ($f['categoria'] !== '') {
            if (ctype_digit((string)$f['categoria'])) {
                $where[] = 'p.category_id = :cat_id';
                $params[':cat_id'] = (int)$f['categoria'];
            } else {
                $where[] = 'c.slug = :cat_slug';
                $params[':cat_slug'] = (string)$f['categoria'];
            }
        }

        // Marca: id o slug
        if ($f['marca'] !== '') {
            if (ctype_digit((string)$f['marca'])) {
                $where[] = 'p.brand_id = :brand_id';
                $params[':brand_id'] = (int)$f['marca'];
            } else {
                $where[] = 'b.slug = :brand_slug';
                $params[':brand_slug'] = (string)$f['marca'];
            }
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Total
        $sqlCount = "
            SELECT COUNT(*)
            FROM products p
            LEFT JOIN brands b     ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            {$whereSql}
        ";
        $st = $this->db->prepare($sqlCount);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->execute();
        $total = (int)$st->fetchColumn();

        // Page
        $sql = "
            SELECT
                p.id,
                p.slug,
                p.name                      AS title,  -- alias para vistas
                p.short_desc                AS short,  -- alias
                p.price,
                p.list_price,
                p.rating,
                p.reviews,
                p.is_new,
                p.created_at,
                b.name                      AS brand,
                b.slug                      AS brand_slug,
                c.slug                      AS category_slug,
                (SELECT pi.path
                 FROM product_images pi
                 WHERE pi.product_id = p.id
                 ORDER BY pi.is_cover DESC, pi.sort ASC, pi.id ASC
                 LIMIT 1)                   AS thumb
            FROM products p
            LEFT JOIN brands b     ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id
            {$whereSql}
            ORDER BY {$sort} {$order}
            LIMIT :limit OFFSET :offset
        ";
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':limit', $per, PDO::PARAM_INT);
        $st->bindValue(':offset', $off, PDO::PARAM_INT);
        $st->execute();
        $items = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Normalizar thumb a URL pública
        foreach ($items as &$it) {
            $it['thumb'] = !empty($it['thumb']) ? public_url($it['thumb']) : asset('img/placeholder.jpg');
        }

        $meta = [
            'total' => $total,
            'page'  => $page,
            'per'   => $per,
        ];

        return compact('items', 'meta');
    }
}
