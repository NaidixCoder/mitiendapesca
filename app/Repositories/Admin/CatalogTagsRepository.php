<?php
namespace App\Repositories\Admin;

use PDO;

final class CatalogTagsRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function listGroups(): array
    {
        $sql = "
            SELECT
                g.*,
                COUNT(t.id) AS tags_count
            FROM catalog_tag_groups g
            LEFT JOIN catalog_tags t ON t.group_id = g.id
            GROUP BY g.id
            ORDER BY g.sort ASC, g.name ASC
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findGroupById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $st = $this->db->prepare("SELECT * FROM catalog_tag_groups WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveGroup(int $id, array $data): int
    {
        if ($id > 0) {
            $st = $this->db->prepare("
                UPDATE catalog_tag_groups
                   SET name = :name,
                       slug = :slug,
                       sort = :sort,
                       is_active = :is_active
                 WHERE id = :id
            ");
            $st->execute([
                ':id' => $id,
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':sort' => $data['sort'],
                ':is_active' => $data['is_active'],
            ]);
            return $id;
        }

        $st = $this->db->prepare("
            INSERT INTO catalog_tag_groups (name, slug, sort, is_active)
            VALUES (:name, :slug, :sort, :is_active)
        ");
        $st->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':sort' => $data['sort'],
            ':is_active' => $data['is_active'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listTags(?int $groupId = null): array
    {
        $params = [];
        $where = '';
        if ($groupId !== null && $groupId > 0) {
            $where = 'WHERE t.group_id = :group_id';
            $params[':group_id'] = $groupId;
        }

        $sql = "
            SELECT
                t.*,
                g.name AS group_name,
                COUNT(DISTINCT pt.product_id) AS products_count
            FROM catalog_tags t
            INNER JOIN catalog_tag_groups g ON g.id = t.group_id
            LEFT JOIN product_catalog_tags pt ON pt.tag_id = t.id
            {$where}
            GROUP BY t.id
            ORDER BY g.sort ASC, g.name ASC, t.sort ASC, t.name ASC
        ";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findTagById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $st = $this->db->prepare("SELECT * FROM catalog_tags WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveTag(int $id, array $data): int
    {
        if ($id > 0) {
            $st = $this->db->prepare("
                UPDATE catalog_tags
                   SET group_id = :group_id,
                       name = :name,
                       slug = :slug,
                       sort = :sort,
                       is_active = :is_active
                 WHERE id = :id
            ");
            $st->execute([
                ':id' => $id,
                ':group_id' => $data['group_id'],
                ':name' => $data['name'],
                ':slug' => $data['slug'],
                ':sort' => $data['sort'],
                ':is_active' => $data['is_active'],
            ]);
            return $id;
        }

        $st = $this->db->prepare("
            INSERT INTO catalog_tags (group_id, name, slug, sort, is_active)
            VALUES (:group_id, :name, :slug, :sort, :is_active)
        ");
        $st->execute([
            ':group_id' => $data['group_id'],
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':sort' => $data['sort'],
            ':is_active' => $data['is_active'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listActiveGroupsWithTags(): array
    {
        $sql = "
            SELECT
                g.id AS group_id,
                g.name AS group_name,
                g.slug AS group_slug,
                g.sort AS group_sort,
                t.id AS tag_id,
                t.name AS tag_name,
                t.slug AS tag_slug,
                t.sort AS tag_sort
            FROM catalog_tag_groups g
            INNER JOIN catalog_tags t ON t.group_id = g.id
            WHERE g.is_active = 1
              AND t.is_active = 1
            ORDER BY g.sort ASC, g.name ASC, t.sort ASC, t.name ASC
        ";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $groups = [];
        foreach ($rows as $row) {
            $groupId = (int)$row['group_id'];
            if (!isset($groups[$groupId])) {
                $groups[$groupId] = [
                    'id' => $groupId,
                    'name' => (string)$row['group_name'],
                    'slug' => (string)$row['group_slug'],
                    'sort' => (int)$row['group_sort'],
                    'tags' => [],
                ];
            }
            $groups[$groupId]['tags'][] = [
                'id' => (int)$row['tag_id'],
                'name' => (string)$row['tag_name'],
                'slug' => (string)$row['tag_slug'],
                'sort' => (int)$row['tag_sort'],
            ];
        }
        return array_values($groups);
    }

    public function findProductTagIds(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }
        $st = $this->db->prepare("SELECT tag_id FROM product_catalog_tags WHERE product_id = :product_id");
        $st->execute([':product_id' => $productId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    public function syncProductTags(int $productId, array $tagIds): void
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

        $this->db->prepare("DELETE FROM product_catalog_tags WHERE product_id = :product_id")
            ->execute([':product_id' => $productId]);

        if ($tagIds === []) {
            return;
        }

        $st = $this->db->prepare("
            INSERT INTO product_catalog_tags (product_id, tag_id)
            VALUES (:product_id, :tag_id)
        ");
        foreach ($tagIds as $tagId) {
            $st->execute([
                ':product_id' => $productId,
                ':tag_id' => $tagId,
            ]);
        }
    }

    public function findVariantTagIdsByProduct(int $productId): array
    {
        if ($productId <= 0) {
            return [];
        }

        $st = $this->db->prepare("
            SELECT vct.variant_id, vct.tag_id
            FROM variant_catalog_tags vct
            INNER JOIN product_variants pv ON pv.id = vct.variant_id
            WHERE pv.product_id = :product_id
        ");
        $st->execute([':product_id' => $productId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $row) {
            $variantId = (int)($row['variant_id'] ?? 0);
            $tagId = (int)($row['tag_id'] ?? 0);
            if ($variantId <= 0 || $tagId <= 0) {
                continue;
            }
            $out[$variantId] ??= [];
            $out[$variantId][] = $tagId;
        }

        foreach ($out as $variantId => $tagIds) {
            $out[$variantId] = array_values(array_unique(array_map('intval', $tagIds)));
        }

        return $out;
    }

    public function syncVariantTags(int $variantId, array $tagIds): void
    {
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

        $this->db->prepare("DELETE FROM variant_catalog_tags WHERE variant_id = :variant_id")
            ->execute([':variant_id' => $variantId]);

        if ($tagIds === []) {
            return;
        }

        $st = $this->db->prepare("
            INSERT INTO variant_catalog_tags (variant_id, tag_id)
            VALUES (:variant_id, :tag_id)
        ");
        foreach ($tagIds as $tagId) {
            $st->execute([
                ':variant_id' => $variantId,
                ':tag_id' => $tagId,
            ]);
        }
    }
}
