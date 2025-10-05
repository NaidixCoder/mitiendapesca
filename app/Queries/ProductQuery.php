<?php
namespace App\Queries;

use PDO;

class ProductQuery
{
    public function __construct(private PDO $db) {}

    public function search(array $filters): array {
        $q          = trim($filters['q'] ?? '');
        $brandId    = isset($filters['brand_id']) && $filters['brand_id'] !== '' ? (int)$filters['brand_id'] : null;
        $catId      = isset($filters['category_id']) && $filters['category_id'] !== '' ? (int)$filters['category_id'] : null;
        $active     = isset($filters['active']) && $filters['active'] !== '' ? (int)$filters['active'] : null;

        $page = max(1, (int)($filters['page'] ?? 1));
        $per  = max(1, min(200, (int)($filters['per']  ?? 20)));
        $off  = ($page - 1) * $per;

        $clauses = []; $params = [];
        if ($q !== '') {
            $clauses[] = "(p.name LIKE :q OR p.sku LIKE :q OR p.slug LIKE :q)";
            $params[':q'] = "%{$q}%";
        }
        if ($brandId !== null) { $clauses[] = "p.brand_id = :bid"; $params[':bid'] = $brandId; }
        if ($catId   !== null) { $clauses[] = "p.category_id = :cid"; $params[':cid'] = $catId; }
        if ($active  !== null) { $clauses[] = "p.is_active = :act"; $params[':act'] = $active; }
        $where = $clauses ? ('WHERE '.implode(' AND ', $clauses)) : '';

        $sort  = strtolower($filters['sort']  ?? 'created_at');
        $order = strtolower($filters['order'] ?? 'desc');
        $allowedSort  = ['created_at','price','name','id'];
        $allowedOrder = ['asc','desc'];
        if (!in_array($sort,  $allowedSort,  true)) $sort  = 'created_at';
        if (!in_array($order, $allowedOrder, true)) $order = 'desc';

        $stc = $this->db->prepare("SELECT COUNT(*) FROM products p $where");
        $stc->execute($params);
        $total = (int)$stc->fetchColumn();
        $pages = max(1, (int)ceil($total / $per));

        $sql = """
          SELECT p.id,p.sku,p.name,p.price,p.is_active,b.name AS brand,c.name AS category,
                 (SELECT path FROM product_images i WHERE i.product_id=p.id
                  ORDER BY is_cover DESC, id ASC LIMIT 1) AS cover
          FROM products p
          LEFT JOIN brands b ON b.id=p.brand_id
          LEFT JOIN categories c ON c.id=p.category_id
          $where
          ORDER BY p.$sort $order
          LIMIT :lim OFFSET :off
        """;
        $st = $this->db->prepare($sql);
        foreach ($params as $k=>$v) $st->bindValue($k,$v);
        $st->bindValue(':lim',$per,PDO::PARAM_INT);
        $st->bindValue(':off',$off,PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        return compact('rows','total','pages','page','per','q','brandId','catId','active','sort','order');
    }
}
