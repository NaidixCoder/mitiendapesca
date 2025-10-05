<?php
namespace App\Repositories;

use PDO;

class AdminProductRepository
{
    public function __construct(private PDO $db) {}

    public function load(int $id): array {
        $base = [
            'id'=>0,'sku'=>'','slug'=>'','name'=>'','brand_id'=>null,'category_id'=>null,
            'short_desc'=>'','long_desc'=>'','price'=>0,'list_price'=>null,'cost'=>null,
            'barcode'=>null,'is_active'=>1,'meta_title'=>null,'meta_description'=>null,
        ];
        if ($id<=0) return $base;
        $st = $this->db->prepare("SELECT * FROM products WHERE id=?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: $base;
    }

    public function brands(): array {
        return $this->db->query("SELECT id,name FROM brands ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function categories(): array {
        return $this->db->query("SELECT id,name FROM categories WHERE is_active=1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function images(int $id): array {
        if ($id<=0) return [];
        $st = $this->db->prepare("
            SELECT id, path, alt, is_cover
            FROM product_images
            WHERE product_id=?
            ORDER BY is_cover DESC, sort ASC, id ASC
        ");
        $st->execute([$id]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function insert(array $d): int {
        $sql = "INSERT INTO products (sku,slug,name,brand_id,category_id,short_desc,long_desc,price,list_price,cost,barcode,is_active,meta_title,meta_description)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $ok = $this->db->prepare($sql)->execute([
            $d['sku'],$d['slug'],$d['name'],$d['brand'],$d['cat'],$d['short'],$d['long'],$d['price'],$d['listp'],$d['cost'],$d['bar'],$d['active'],$d['metaTitle'],$d['metaDesc']
        ]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    public function update(int $id, array $d): bool {
        $sql = "UPDATE products
                SET sku=?, slug=?, name=?, brand_id=?, category_id=?, short_desc=?, long_desc=?, price=?, list_price=?, cost=?, barcode=?, is_active=?, meta_title=?, meta_description=?
                WHERE id=?";
        return $this->db->prepare($sql)->execute([
            $d['sku'],$d['slug'],$d['name'],$d['brand'],$d['cat'],$d['short'],$d['long'],$d['price'],$d['listp'],$d['cost'],$d['bar'],$d['active'],$d['metaTitle'],$d['metaDesc'],$id
        ]);
    }
}
