<?php
namespace App\Services\Admin\Variants;

use PDO;
use Throwable;

final class VariantValueSetsService
{
    public function __construct(private PDO $db) {}

    public function createValueSetFromProduct(int $productId, string $name): array
    {
        $productId = (int)$productId;
        $name = trim($name);

        if ($productId <= 0) return ['ok' => false, 'message' => 'Producto inválido.'];
        if ($name === '' || mb_strlen($name) > 120) return ['ok' => false, 'message' => 'Nombre inválido (máx. 120 caracteres).'];

        $stp = $this->db->prepare("SELECT category_id, brand_id FROM products WHERE id=? LIMIT 1");
        $stp->execute([$productId]);
        $pm = $stp->fetch(PDO::FETCH_ASSOC) ?: [];
        $categoryId = (int)($pm['category_id'] ?? 0);
        $brandId = (int)($pm['brand_id'] ?? 0);

        if ($categoryId <= 0) return ['ok' => false, 'message' => 'El producto no tiene categoría.'];
        if ($brandId <= 0) return ['ok' => false, 'message' => 'Para guardar una plantilla por Categoría + Marca, asigná una marca al producto.'];

        $pairs = [];
        try {
            $sql = "SELECT pav.attribute_id, pav.value_id
                    FROM product_attribute_values pav
                    JOIN product_attributes pa
                      ON pa.product_id = pav.product_id AND pa.attribute_id = pav.attribute_id
                    JOIN attributes a
                      ON a.id = pa.attribute_id
                    WHERE pav.product_id = :pid
                      AND COALESCE(a.is_variant_axis, 0) = 1
                    ORDER BY pav.attribute_id ASC, pav.value_id ASC";
            $st = $this->db->prepare($sql);
            $st->execute([':pid' => $productId]);
            $pairs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'No se pudieron leer los valores del producto.'];
        }

        if (empty($pairs)) {
            return ['ok' => false, 'message' => 'No hay valores seleccionados para guardar como plantilla.'];
        }

        try {
            $chk = $this->db->prepare("SELECT id FROM variant_value_sets WHERE category_id=:cat AND brand_id=:brand AND name=:name LIMIT 1");
            $chk->execute([':cat' => $categoryId, ':brand' => $brandId, ':name' => $name]);
            $existingId = (int)($chk->fetchColumn() ?: 0);
            if ($existingId > 0) {
                return ['ok' => false, 'message' => 'Ya existe una plantilla con ese nombre para esta categoría y marca.'];
            }
        } catch (Throwable) {}

        try {
            $this->db->beginTransaction();

            $ins = $this->db->prepare("INSERT INTO variant_value_sets (name, category_id, brand_id) VALUES (:name, :cat, :brand)");
            $ins->execute([':name' => $name, ':cat' => $categoryId, ':brand' => $brandId]);
            $setId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare("INSERT IGNORE INTO variant_value_set_items (set_id, attribute_id, value_id) VALUES (:sid, :aid, :vid)");
            $itemsInserted = 0;
            $attrsUniq = [];
            foreach ($pairs as $row) {
                $aid = (int)($row['attribute_id'] ?? 0);
                $vid = (int)($row['value_id'] ?? 0);
                if ($aid <= 0 || $vid <= 0) continue;
                $insItem->execute([':sid' => $setId, ':aid' => $aid, ':vid' => $vid]);
                $itemsInserted += (int)$insItem->rowCount();
                $attrsUniq[$aid] = true;
            }

            $this->db->commit();

            if ($itemsInserted <= 0) {
                return ['ok' => true, 'set_id' => $setId, 'message' => 'Plantilla guardada (sin nuevos ítems).', 'items' => 0, 'attributes' => count($attrsUniq)];
            }

            return ['ok' => true, 'set_id' => $setId, 'items' => $itemsInserted, 'attributes' => count($attrsUniq), 'message' => "Plantilla guardada: {$name}."];
        } catch (Throwable) {
            try { $this->db->rollBack(); } catch (Throwable $_) {}
            return ['ok' => false, 'message' => 'No se pudo guardar la plantilla.'];
        }
    }

    public function importValueSet(int $productId, int $setId): array
    {
        if ($productId <= 0 || $setId <= 0) return ['ok' => false, 'message' => 'Datos inválidos.'];

        $stp = $this->db->prepare("SELECT category_id, brand_id FROM products WHERE id=? LIMIT 1");
        $stp->execute([$productId]);
        $pm = $stp->fetch(PDO::FETCH_ASSOC) ?: [];
        $categoryId = (int)($pm['category_id'] ?? 0);
        $brandId = (int)($pm['brand_id'] ?? 0);

        $st = $this->db->prepare("SELECT id, name, category_id, brand_id FROM variant_value_sets WHERE id=? LIMIT 1");
        $st->execute([$setId]);
        $set = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$set) return ['ok' => false, 'message' => 'Plantilla no encontrada.'];

        $setCat = (int)($set['category_id'] ?? 0);
        $setBrand = (int)($set['brand_id'] ?? 0);
        $setBrandIsNull = ($set['brand_id'] === null);

        if ($categoryId > 0 && $setCat !== $categoryId) return ['ok' => false, 'message' => 'Esta plantilla no corresponde a la categoría del producto.'];
        if (!$setBrandIsNull && $brandId > 0 && $setBrand !== $brandId) return ['ok' => false, 'message' => 'Esta plantilla no corresponde a la marca del producto.'];

        $sti = $this->db->prepare("SELECT attribute_id, value_id FROM variant_value_set_items WHERE set_id=? ORDER BY attribute_id ASC, value_id ASC");
        $sti->execute([$setId]);
        $items = $sti->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$items) return ['ok' => false, 'message' => 'La plantilla no tiene ítems.'];

        $this->db->beginTransaction();
        try {
            $addedAttrs = 0;
            $addedVals = 0;
            $insAttr = $this->db->prepare("INSERT IGNORE INTO product_attributes (product_id, attribute_id, sort, is_required) VALUES (?,?,?,1)");
            $insVal = $this->db->prepare("INSERT IGNORE INTO product_attribute_values (product_id, attribute_id, value_id) VALUES (?,?,?)");

            $stMax = $this->db->prepare("SELECT COALESCE(MAX(sort),0) FROM product_attributes WHERE product_id=?");
            $stMax->execute([$productId]);
            $sortBase = (int)($stMax->fetchColumn() ?: 0);

            $seenAttrs = [];
            foreach ($items as $it) {
                $aid = (int)($it['attribute_id'] ?? 0);
                $vid = (int)($it['value_id'] ?? 0);
                if ($aid <= 0 || $vid <= 0) continue;

                if (empty($seenAttrs[$aid])) {
                    $sortBase += 10;
                    $axisSt = $this->db->prepare("SELECT COALESCE(is_variant_axis, 0) FROM attributes WHERE id = ? LIMIT 1");
                    $axisSt->execute([$aid]);
                    $seenAttrs[$aid] = ((int)($axisSt->fetchColumn() ?: 0) === 1);
                    if ($seenAttrs[$aid]) {
                        $insAttr->execute([$productId, $aid, $sortBase]);
                        $addedAttrs += (int)$insAttr->rowCount();
                    }
                }

                if (empty($seenAttrs[$aid])) continue;

                $insVal->execute([$productId, $aid, $vid]);
                $addedVals += (int)$insVal->rowCount();
            }

            $this->db->commit();
            $name = (string)($set['name'] ?? 'Plantilla');
            return ['ok' => true, 'set_id' => $setId, 'set_name' => $name, 'added_attributes' => $addedAttrs, 'added_values' => $addedVals, 'message' => "Plantilla importada: {$name}."];
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function valueSetsTablesExist(): bool
    {
        try {
            $a = $this->db->query("SHOW TABLES LIKE 'variant_value_sets'")->fetchColumn();
            $b = $this->db->query("SHOW TABLES LIKE 'variant_value_set_items'")->fetchColumn();
            return !empty($a) && !empty($b);
        } catch (Throwable) {
            return false;
        }
    }

    public function listValueSetsAdmin(array $filters = [], int $limit = 100): array
    {
        if (!$this->valueSetsTablesExist()) return [];
        $q = trim((string)($filters['q'] ?? ''));
        $categoryId = (int)($filters['category_id'] ?? 0);
        $brandId = (int)($filters['brand_id'] ?? 0);
        $where = [];
        $params = [];

        if ($q !== '') { $where[] = '(s.name LIKE :q)'; $params[':q'] = '%' . $q . '%'; }
        if ($categoryId > 0) { $where[] = '(s.category_id = :cid)'; $params[':cid'] = $categoryId; }
        if ($brandId > 0) { $where[] = '(s.brand_id = :bid)'; $params[':bid'] = $brandId; }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT
                  s.id,
                  s.name,
                  s.category_id,
                  s.brand_id,
                  s.created_at,
                  s.updated_at,
                  c.name AS category_name,
                  b.name AS brand_name,
                  (SELECT COUNT(*) FROM variant_value_set_items i WHERE i.set_id = s.id) AS items_count
                FROM variant_value_sets s
                LEFT JOIN categories c ON c.id = s.category_id
                LEFT JOIN brands b ON b.id = s.brand_id
                $sqlWhere
                ORDER BY s.updated_at DESC, s.id DESC
                LIMIT " . (int)$limit;

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getValueSetItems(int $setId): array
    {
        if (!$this->valueSetsTablesExist()) return [];

        $st = $this->db->prepare("SELECT
            i.attribute_id,
            a.name AS attribute_name,
            i.value_id,
            v.value AS value_label
          FROM variant_value_set_items i
          JOIN attributes a ON a.id = i.attribute_id
          JOIN attribute_values v ON v.id = i.value_id
          WHERE i.set_id = :sid
          ORDER BY a.name ASC, v.sort ASC, v.value ASC, v.id ASC");
        $st->execute([':sid' => $setId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $aid = (int)$r['attribute_id'];
            if (!isset($out[$aid])) {
                $out[$aid] = ['attribute_id' => $aid, 'attribute_name' => (string)($r['attribute_name'] ?? ''), 'values' => []];
            }
            $out[$aid]['values'][] = ['value_id' => (int)$r['value_id'], 'value' => (string)($r['value_label'] ?? '')];
        }
        return array_values($out);
    }

    public function deleteValueSet(int $setId): bool
    {
        if (!$this->valueSetsTablesExist()) return false;
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM variant_value_set_items WHERE set_id = ?")->execute([$setId]);
            $this->db->prepare("DELETE FROM variant_value_sets WHERE id = ?")->execute([$setId]);
            $this->db->commit();
            return true;
        } catch (Throwable) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getValueSet(int $setId): ?array
    {
        if (!$this->valueSetsTablesExist()) return null;
        $st = $this->db->prepare("SELECT id, name, category_id, brand_id, created_at, updated_at
                                  FROM variant_value_sets WHERE id = :id LIMIT 1");
        $st->execute([':id' => $setId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getValueSetAdminRow(int $setId): ?array
    {
        if (!$this->valueSetsTablesExist()) return null;
        $st = $this->db->prepare("SELECT
            s.id,
            s.name,
            s.category_id,
            s.brand_id,
            c.name AS category_name,
            b.name AS brand_name,
            (SELECT COUNT(*) FROM variant_value_set_items i WHERE i.set_id = s.id) AS items_count
          FROM variant_value_sets s
          LEFT JOIN categories c ON c.id = s.category_id
          LEFT JOIN brands b ON b.id = s.brand_id
          WHERE s.id = :id
          LIMIT 1");
        $st->execute([':id' => $setId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listCategories(): array
    {
        $st = $this->db->query("SELECT id, name FROM categories ORDER BY name ASC, id ASC");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listBrands(): array
    {
        $st = $this->db->query("SELECT id, name FROM brands ORDER BY name ASC, id ASC");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listAttributes(): array
    {
        $st = $this->db->query("SELECT id, name FROM attributes ORDER BY name ASC, id ASC");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function listAttributeValues(int $attributeId): array
    {
        $st = $this->db->prepare("SELECT id, value FROM attribute_values WHERE attribute_id = :aid ORDER BY sort ASC, value ASC, id ASC");
        $st->execute([':aid' => $attributeId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function ensureAttributeValuesExist(int $attributeId, array $values): array
    {
        $ids = [];
        foreach ($values as $raw) {
            $v = trim((string)$raw);
            if ($v === '') continue;
            $v = preg_replace('/\s+/', ' ', $v);
            $st = $this->db->prepare("SELECT id FROM attribute_values WHERE attribute_id = :aid AND value = :v LIMIT 1");
            $st->execute([':aid' => $attributeId, ':v' => $v]);
            $id = (int)($st->fetchColumn() ?: 0);
            if ($id <= 0) {
                $ins = $this->db->prepare("INSERT INTO attribute_values (attribute_id, value, sort) VALUES (:aid, :v, 0)");
                $ins->execute([':aid' => $attributeId, ':v' => $v]);
                $id = (int)$this->db->lastInsertId();
            }
            if ($id > 0) $ids[] = $id;
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function getValueSetEditGroups(int $setId, $postedValues = null, int $addAttributeId = 0, int $addValuesAttributeId = 0, string $addValuesCsv = ''): array
    {
        $groups = $this->getValueSetItems($setId);
        $selectedByAttr = [];
        foreach ($groups as $g) {
            $aid = (int)($g['attribute_id'] ?? 0);
            $selectedByAttr[$aid] = [];
            foreach (($g['values'] ?? []) as $v) {
                $selectedByAttr[$aid][] = (int)($v['value_id'] ?? 0);
            }
        }

        if (is_array($postedValues)) {
            $selectedByAttr = [];
            foreach ($postedValues as $aid => $vids) {
                $aid = (int)$aid;
                if ($aid <= 0) continue;
                $selectedByAttr[$aid] = [];
                if (!is_array($vids)) continue;
                foreach ($vids as $vid) {
                    $vid = (int)$vid;
                    if ($vid > 0) $selectedByAttr[$aid][] = $vid;
                }
            }
        }

        if ($addAttributeId > 0 && !isset($selectedByAttr[$addAttributeId])) {
            $selectedByAttr[$addAttributeId] = [];
        }

        if ($addValuesAttributeId > 0 && $addValuesCsv !== '') {
            $parts = array_map('trim', preg_split('/[,\n]+/', $addValuesCsv));
            $parts = array_values(array_filter($parts, fn($x) => $x !== ''));
            if (!empty($parts)) {
                if (!isset($selectedByAttr[$addValuesAttributeId])) {
                    $selectedByAttr[$addValuesAttributeId] = [];
                }
                $newIds = $this->ensureAttributeValuesExist($addValuesAttributeId, $parts);
                if (!empty($newIds)) {
                    $selectedByAttr[$addValuesAttributeId] = array_values(array_unique(array_merge($selectedByAttr[$addValuesAttributeId], $newIds)));
                }
            }
        }

        if (empty($selectedByAttr)) return [];
        $attrIds = array_keys($selectedByAttr);
        $in = implode(',', array_fill(0, count($attrIds), '?'));
        $st = $this->db->prepare("SELECT id, name FROM attributes WHERE id IN ($in) ORDER BY name ASC, id ASC");
        $st->execute($attrIds);
        $attrs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($attrs as $a) {
            $aid = (int)($a['id'] ?? 0);
            $out[] = [
                'attribute_id' => $aid,
                'attribute_name' => (string)($a['name'] ?? ''),
                'values_all' => $this->listAttributeValues($aid),
                'selected_ids' => $selectedByAttr[$aid] ?? [],
            ];
        }
        return $out;
    }

    public function updateValueSet(int $setId, string $name, int $categoryId, int $brandId, array $pairs): bool
    {
        if (!$this->valueSetsTablesExist()) return false;
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE variant_value_sets
                                SET name = :name, category_id = :cid, brand_id = :bid, updated_at = NOW()
                                WHERE id = :id")
                     ->execute([':name' => $name, ':cid' => $categoryId, ':bid' => $brandId, ':id' => $setId]);
            $this->db->prepare("DELETE FROM variant_value_set_items WHERE set_id = ?")->execute([$setId]);
            $ins = $this->db->prepare("INSERT INTO variant_value_set_items (set_id, attribute_id, value_id) VALUES (:sid,:aid,:vid)");
            foreach ($pairs as $p) {
                $aid = (int)($p[0] ?? 0);
                $vid = (int)($p[1] ?? 0);
                if ($aid <= 0 || $vid <= 0) continue;
                $ins->execute([':sid' => $setId, ':aid' => $aid, ':vid' => $vid]);
            }
            $this->db->commit();
            return true;
        } catch (Throwable) {
            $this->db->rollBack();
            return false;
        }
    }
}
