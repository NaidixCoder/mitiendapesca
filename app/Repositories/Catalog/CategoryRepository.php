<?php
namespace App\Repositories\Catalog;

use PDO;
use App\Services\Catalog\CategoryPublicVisibilityService;

final class CategoryRepository
{
    private ?CategoryPublicVisibilityService $publicVisibility = null;

    public function __construct(private PDO $db) {}

    public function publicVisibility(): CategoryPublicVisibilityService
    {
        return $this->publicVisibility ??= new CategoryPublicVisibilityService($this->db);
    }

    /** @return array<int,bool> */
    public function visibleCategoryIds(): array
    {
        return $this->publicVisibility()->visibleIdSet();
    }

    public function db(): PDO
    {
        return $this->db;
    }

    /**
     * Categorías públicas ordenadas para sitemap y navegación.
     * Una carga de jerarquía compartida por instancia y una consulta ordenada;
     * límite aplicado DESPUÉS del filtro para completar las plazas visibles.
     *
     * @return array<int,array{id:int,slug:string,name:string,created_at:?string}>
     */
    public function listPublicVisibleOrdered(?int $limit = null): array
    {
        $eligible = $this->visibleCategoryIds();
        if ($eligible === []) return [];

        $st = $this->db->query(
            "SELECT id, slug, name, created_at
             FROM categories
             WHERE is_active = 1
             ORDER BY sort ASC, name ASC"
        );
        $rows = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $id = (int)$row['id'];
            if (!isset($eligible[$id])) continue;
            $slug = trim((string)$row['slug']);
            $name = trim((string)$row['name']);
            if ($slug === '' || $name === '') continue;
            $rows[] = [
                'id' => $id,
                'slug' => $slug,
                'name' => $name,
                'created_at' => $row['created_at'] === null ? null : (string)$row['created_at'],
            ];
            if ($limit !== null && count($rows) >= max(1, $limit)) break;
        }
        return $rows;
    }

    public function idBySlug(string $slug): ?int
    {
        $slug = trim($slug);
        if ($slug === '') return null;

        $st = $this->db->prepare("SELECT id FROM categories WHERE slug=? LIMIT 1");
        $st->execute([$slug]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    /** @return array<string,mixed>|null */
    public function findActiveBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;

        $st = $this->db->prepare(
            "SELECT id, parent_id, name, slug, is_active, sort, meta_title, meta_description, seo_text, cover_image
             FROM categories
             WHERE slug = ? AND is_active = 1
             LIMIT 1"
        );
        $st->execute([$slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row && $this->publicVisibility()->isVisibleId((int)$row['id']) ? $row : null;
    }

    public function idByName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') return null;
        $st = $this->db->prepare("SELECT id FROM categories WHERE name=? LIMIT 1");
        $st->execute([$name]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }

    public function create(string $name, string $slug): int
    {
        $name = trim($name);
        $slug = trim($slug);
        if ($name === '' || $slug === '') return 0;
        $st = $this->db->prepare("INSERT INTO categories(name, slug, is_active) VALUES(?, ?, 1)");
        $ok = $st->execute([$name, $slug]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    /**
     * @return array<int,array{id:int,name:string,slug:string,sort:int,cover_image:string}>
     */
    public function listHomeFeatured(int $limit = 4): array
    {
        $limit = max(1, min(24, $limit));
        try {
            $st = $this->db->prepare(
                "SELECT id, name, slug, sort, COALESCE(cover_image, '') AS cover_image
                 FROM categories
                 WHERE is_active = 1
                 ORDER BY sort ASC, name ASC"
            );
            $st->execute();
        } catch (\Throwable $e) {
            // Compat fallback si la migracion aun no corrio.
            $st = $this->db->prepare(
                "SELECT id, name, slug, sort, '' AS cover_image
                 FROM categories
                 WHERE is_active = 1
                 ORDER BY sort ASC, name ASC"
            );
            $st->execute();
        }

        $eligible = $this->visibleCategoryIds();
        $rows = array_values(array_filter(
            $st->fetchAll(PDO::FETCH_ASSOC) ?: [],
            static fn(array $row): bool => isset($eligible[(int)$row['id']])
        ));
        $rows = array_slice($rows, 0, $limit);
        foreach ($rows as &$r) {
            $r['id'] = (int)($r['id'] ?? 0);
            $r['sort'] = (int)($r['sort'] ?? 0);
            $r['name'] = (string)($r['name'] ?? '');
            $r['slug'] = (string)($r['slug'] ?? '');
            $r['cover_image'] = (string)($r['cover_image'] ?? '');
        }
        return $rows;
    }

    /**
     * Resuelve ID por slug o nombre. Si no existe y $createMissing = true, crea.
     */
    public function resolveId(string $slugOrName, bool $createMissing = false): ?int
    {
        $v = trim($slugOrName);
        if ($v === '') return null;

        $slug = preg_replace('/[^a-zA-Z0-9]+/','-', strtolower($v));
        $slug = trim((string)$slug, '-');
        $id = $slug !== '' ? $this->idBySlug($slug) : null;
        if ($id) return $id;

        $id = $this->idByName($v);
        if ($id) return $id;

        if (!$createMissing) return null;

        $slug = preg_replace('/[^a-zA-Z0-9]+/','-', strtolower($v));
        $slug = trim((string)$slug, '-');
        if ($slug === '') return null;

        $st = $this->db->prepare("INSERT INTO categories (name, slug, is_active) VALUES (?,?,1)");
        $ok = $st->execute([$v, $slug]);
        return $ok ? (int)$this->db->lastInsertId() : null;
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

