<?php
namespace App\Repositories;

use PDO;

class ProductRepository
{
    public function __construct(private PDO $db) {}

    public function findBySlug(string $slug): ?array
    {
        $st = $this->db->prepare("
            SELECT p.*, b.name AS brand, c.slug AS category,
                (SELECT stock FROM inventory WHERE product_id=p.id) AS stock
            FROM products p
            LEFT JOIN brands b ON b.id=p.brand_id
            LEFT JOIN categories c ON c.id=p.category_id
            WHERE p.slug=? AND p.is_active=1
            LIMIT 1
        ");
        $st->execute([$slug]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if (!$p) return null;

        // Alias consistentes para las vistas
        $p['title'] = $p['name'];
        $p['short'] = $p['short_desc'] ?? '';
        $p['long']  = $p['long_desc']  ?? '';

        // imágenes...
        $imgs = $this->db->prepare("
        SELECT path FROM product_images WHERE product_id=? ORDER BY is_cover DESC, sort ASC, id ASC
        ");
        $imgs->execute([(int)$p['id']]);
        $p['images'] = array_map(fn($r)=>$r['path'], $imgs->fetchAll(\PDO::FETCH_ASSOC));

        return $p;
    }

    public function related(array $product, int $limit = 8): array
    {
        $seen = [$product['slug'] => true];
        $out  = [];

        // Helpers
        $push = function($rows) use (&$out, &$seen, $limit) {
            foreach ($rows as $r) {
                if (isset($seen[$r['slug']])) continue;
                $seen[$r['slug']] = true;
                $out[] = [
                    'slug'=>$r['slug'],
                    'title'=>$r['name'],
                    'price'=>(int)$r['price'],
                    'thumb'=>$r['thumb'] ?: 'img/placeholder.jpg',
                ];
                if (count($out) >= $limit) break;
            }
        };

        // 1) misma categoría
        if (!empty($product['category']) && count($out) < $limit) {
            $st = $this->db->prepare("
              SELECT p.slug,p.name,p.price,
                (SELECT path FROM product_images i WHERE i.product_id=p.id ORDER BY is_cover DESC, sort,id LIMIT 1) AS thumb
              FROM products p
              JOIN categories c ON c.id=p.category_id
              WHERE p.is_active=1 AND p.slug<>:slug AND c.slug=:cid
              ORDER BY p.created_at DESC LIMIT 12
            ");
            $st->execute([':slug'=>$product['slug'], ':cid'=>$product['category']]);
            $push($st->fetchAll(PDO::FETCH_ASSOC));
        }

        // 2) misma marca
        if (!empty($product['brand']) && count($out) < $limit) {
            $st = $this->db->prepare("
              SELECT p.slug,p.name,p.price,
                (SELECT path FROM product_images i WHERE i.product_id=p.id ORDER BY is_cover DESC, sort,id LIMIT 1) AS thumb
              FROM products p
              LEFT JOIN brands b ON b.id=p.brand_id
              WHERE p.is_active=1 AND p.slug<>:slug AND b.name=:bid
              ORDER BY p.created_at DESC LIMIT 12
            ");
            $st->execute([':slug'=>$product['slug'], ':bid'=>$product['brand']]);
            $push($st->fetchAll(PDO::FETCH_ASSOC));
        }

        // 3) rango de precio ±50%
        if (count($out) < $limit) {
            $price = (int)($product['price'] ?? 0);
            $low   = (int)floor($price * 0.5);
            $high  = (int)ceil($price * 1.5);
            $st = $this->db->prepare("
              SELECT p.slug,p.name,p.price,
                (SELECT path FROM product_images i WHERE i.product_id=p.id ORDER BY is_cover DESC, sort,id LIMIT 1) AS thumb
              FROM products p
              WHERE p.is_active=1 AND p.slug<>:slug
                AND p.price BETWEEN :low AND :high
              ORDER BY p.created_at DESC LIMIT 24
            ");
            $st->execute([':slug'=>$product['slug'], ':low'=>$low, ':high'=>$high]);
            $push($st->fetchAll(PDO::FETCH_ASSOC));
        }

        // Fallback
        if (count($out) < 4) {
            $st = $this->db->prepare("
              SELECT p.slug,p.name,p.price,
                (SELECT path FROM product_images i WHERE i.product_id=p.id ORDER BY is_cover DESC, sort,id LIMIT 1) AS thumb
              FROM products p
              WHERE p.is_active=1 AND p.slug<>:slug
              ORDER BY p.created_at DESC LIMIT 24
            ");
            $st->execute([':slug'=>$product['slug']]);
            $push($st->fetchAll(PDO::FETCH_ASSOC));
        }

        // Normalizar thumbs (URL absoluta pública)
        foreach ($out as &$r) {
            $r['thumb'] = public_url($r['thumb']);
        }
        return $out;
    }
}
