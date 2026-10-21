<?php
namespace App\Repositories\Catalog;

use PDO;

final class AttributeRepository
{
    public function __construct(private PDO $db) {}

    /** @return array{id:int,name:string,slug:string,type:string}|null */
    public function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $st = $this->db->prepare("SELECT id, name, slug, type FROM attributes WHERE slug=? LIMIT 1");
        $st->execute([$slug]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        return [
            'id' => (int)$r['id'],
            'name' => (string)$r['name'],
            'slug' => (string)$r['slug'],
            'type' => (string)($r['type'] ?? 'select'),
        ];
    }

    /** @return array{id:int,name:string,slug:string,type:string}|null */
    public function findByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') return null;
        $st = $this->db->prepare("SELECT id, name, slug, type FROM attributes WHERE name=? LIMIT 1");
        $st->execute([$name]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        return [
            'id' => (int)$r['id'],
            'name' => (string)$r['name'],
            'slug' => (string)$r['slug'],
            'type' => (string)($r['type'] ?? 'select'),
        ];
    }

    public function insert(string $name, string $slug, string $type = 'select'): int
    {
        $name = trim($name);
        $slug = trim($slug);
        if ($name === '' || $slug === '') return 0;
        $st = $this->db->prepare("INSERT INTO attributes (name, slug, type, created_at) VALUES (?,?,?,NOW())");
        $ok = $st->execute([$name, $slug, $type]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }
}

