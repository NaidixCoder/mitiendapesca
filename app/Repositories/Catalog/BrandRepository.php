<?php
namespace App\Repositories\Catalog;

use PDO;

final class BrandRepository
{
    public function __construct(private PDO $db) {}

    public function idBySlug(string $slug): ?int
    {
        $st = $this->db->prepare("SELECT id FROM brands WHERE slug=?");
        $st->execute([$slug]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function resolveId(string $value, bool $createMissing = false): ?int
    {
        $v = trim($value);
        if ($v === '') return null;
        // Si parece slug, intenta slug; si no, lo slugifica
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($v));
        $slug = trim((string)$slug, '-');
        $id = $this->idBySlug($slug);
        if ($id) return $id;
        $id = $this->idByName($v);
        if ($id) return $id;
        if (!$createMissing) return null;
        $newId = $this->create($v, $slug);
        return $newId > 0 ? $newId : null;
    }

    public function idByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;
        $st = $this->db->prepare("SELECT id FROM brands WHERE name=?");
        $st->execute([$name]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function create(string $name, string $slug): int
    {
        $name = trim($name);
        $slug = trim($slug);
        if ($name === '' || $slug === '') return 0;
        $st = $this->db->prepare("INSERT INTO brands(name, slug) VALUES(?, ?)");
        if (!$st->execute([$name, $slug])) return 0;
        return (int)$this->db->lastInsertId();
    }

public function findBySlug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '') return null;

    $st = $this->db->prepare("
        SELECT id, slug, name, meta_title, meta_description, seo_text
          FROM brands
         WHERE slug = ?
         LIMIT 1
    ");
    $st->execute([$slug]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

    /**
     * Lista todas las marcas con cantidad de productos activos.
     *
     * @return array<int, array{id:int, slug:string, name:string, product_count:int, cover_image:string}>
     */
    public function listAllWithProductCount(): array
    {
        $sql = "
            SELECT b.id, b.slug, b.name,
                   COALESCE(b.cover_image, '') AS cover_image,
                   COUNT(p.id) AS product_count
              FROM brands b
         LEFT JOIN products p
                ON p.brand_id = b.id
               AND p.is_active = 1
          GROUP BY b.id, b.slug, b.name, b.cover_image
          ORDER BY b.name ASC
        "
        ;

        $st = $this->db->prepare($sql);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$r) {
            $r['id'] = (int)($r['id'] ?? 0);
            $r['product_count'] = (int)($r['product_count'] ?? 0);
            $r['cover_image'] = (string)($r['cover_image'] ?? '');
        }
        return $rows;
    }

    /**
     * @return array<int,array{id:int,slug:string,name:string,sort:int,cover_image:string}>
     */
    public function listHomeFeatured(int $limit = 6): array
    {
        $limit = max(1, min(24, $limit));
        try {
            $st = $this->db->prepare(
                "SELECT id, slug, name, sort, COALESCE(cover_image, '') AS cover_image
                 FROM brands
                 ORDER BY sort ASC, name ASC
                 LIMIT $limit"
            );
            $st->execute();
        } catch (\Throwable $e) {
            // Compat fallback si la migracion aun no corrio.
            $st = $this->db->prepare(
                "SELECT id, slug, name, 0 AS sort, '' AS cover_image
                 FROM brands
                 ORDER BY name ASC
                 LIMIT $limit"
            );
            $st->execute();
        }

        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$r) {
            $r['id'] = (int)($r['id'] ?? 0);
            $r['sort'] = (int)($r['sort'] ?? 0);
            $r['slug'] = (string)($r['slug'] ?? '');
            $r['name'] = (string)($r['name'] ?? '');
            $r['cover_image'] = (string)($r['cover_image'] ?? '');
        }
        return $rows;
    }



/**
 * Resuelve ID por slug/nombre. Si $dry=true y $createMissing=true y no existe,
 * NO escribe en DB: devuelve id=0 y would_create=true.
 *
 * @return array{id:int,would_create:bool,slug:string,name:string}
 */
public function resolveIdPlanned(string $value, bool $createMissing = false, bool $dry = false): array
{
    $v = trim($value);
    if ($v === '') return ['id'=>0,'would_create'=>false,'slug'=>'','name'=>''];

    $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($v));
    $slug = trim((string)$slug, '-');

    $id = $this->idBySlug($slug);
    if ($id) return ['id'=>$id,'would_create'=>false,'slug'=>$slug,'name'=>$v];

    $id = $this->idByName($v);
    if ($id) return ['id'=>$id,'would_create'=>false,'slug'=>$slug,'name'=>$v];

    if (!$createMissing) return ['id'=>0,'would_create'=>false,'slug'=>$slug,'name'=>$v];
    if ($dry) return ['id'=>0,'would_create'=>true,'slug'=>$slug,'name'=>$v];

    $newId = $this->create($v, $slug);
    return ['id'=>($newId > 0 ? $newId : 0),'would_create'=>($newId > 0),'slug'=>$slug,'name'=>$v];
}
}

