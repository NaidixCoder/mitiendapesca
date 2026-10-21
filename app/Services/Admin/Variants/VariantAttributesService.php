<?php
namespace App\Services\Admin\Variants;

use PDO;
use Throwable;

final class VariantAttributesService
{
    use VariantAttributesLifecycleTrait;
    use VariantAttributesSelectionTrait;

    public function __construct(private PDO $db) {}

    public function addAttribute(int $productId, int $attributeId): void
    {
        $isRequired = $this->attributeGeneratesVariants($attributeId) ? 1 : 0;

        // evitar duplicados
        $st = $this->db->prepare("SELECT 1 FROM product_attributes WHERE product_id=? AND attribute_id=?");
        $st->execute([$productId, $attributeId]);
        if ($st->fetchColumn()) return;

        // sort al final
        $mx = $this->db->prepare("SELECT COALESCE(MAX(sort),0) FROM product_attributes WHERE product_id=?");
        $mx->execute([$productId]);
        $sort = (int)$mx->fetchColumn() + 10;

        $ins = $this->db->prepare("INSERT IGNORE INTO product_attributes (product_id, attribute_id, sort, is_required) VALUES (?,?,?,?)");
        $ins->execute([$productId, $attributeId, $sort, $isRequired]);
    }

    public function removeAttribute(int $productId, int $attributeId): void
    {
        $isVariantAxis = $this->attributeGeneratesVariants($attributeId);

        $this->db->beginTransaction();
        try {
            // Borrar asociaciones de valores de variantes para ese atributo
            $vids = $this->db->prepare("SELECT id FROM product_variants WHERE product_id=?");
            $vids->execute([$productId]);
            $variants = $vids->fetchAll(PDO::FETCH_COLUMN) ?: [];

            if ($variants) {
                $in = implode(',', array_fill(0, count($variants), '?'));
                $args = array_map('intval', $variants);
                $args[] = $attributeId;

                $del = $this->db->prepare("DELETE FROM product_variant_values WHERE variant_id IN ($in) AND attribute_id = ?");
                $del->execute($args);
            }

            // Quitar selección del producto (si existía)
            $st = $this->db->prepare("DELETE FROM product_attribute_values WHERE product_id=? AND attribute_id=?");
            $st->execute([$productId, $attributeId]);

            // Quitar atributo del producto
            $st = $this->db->prepare("DELETE FROM product_attributes WHERE product_id=? AND attribute_id=?");
            $st->execute([$productId, $attributeId]);

            if (!$isVariantAxis) {
                $this->db->commit();
                return;
            }

            // Si ya no quedan atributos en el producto, no tiene sentido mantener variantes "vacías".
            // En ese caso limpiamos TODAS las variantes:
            // - sin pedidos: se eliminan
            // - con pedidos: se pausan (regla de seguridad)
            // y también limpiamos selección guardada.
            $cnt = $this->db->prepare("SELECT COUNT(*)
                                       FROM product_attributes pa
                                       JOIN attributes a ON a.id = pa.attribute_id
                                       WHERE pa.product_id=? AND COALESCE(a.is_variant_axis, 0) = 1");
            $cnt->execute([$productId]);
            $remainingAttrs = (int)($cnt->fetchColumn() ?: 0);
            if ($remainingAttrs <= 0) {
                // Limpia selección (defensivo)
                $this->db->prepare("DELETE pav
                                    FROM product_attribute_values pav
                                    JOIN attributes a ON a.id = pav.attribute_id
                                    WHERE pav.product_id=? AND COALESCE(a.is_variant_axis, 0) = 1")->execute([$productId]);

                // Limpia valores por variante (defensivo)
                try {
                    $this->db->prepare(
                        "DELETE pvv FROM product_variant_values pvv JOIN product_variants pv ON pv.id = pvv.variant_id WHERE pv.product_id = ?"
                    )->execute([$productId]);
                } catch (Throwable $_) { /* noop */ }

                // Elimina/pausa todas las variantes
                $vidsAll = $this->db->prepare("SELECT id FROM product_variants WHERE product_id=?");
                $vidsAll->execute([$productId]);
                $all = array_map('intval', $vidsAll->fetchAll(PDO::FETCH_COLUMN) ?: []);
                foreach ($all as $vid) {
                    if ($vid <= 0) continue;
                    $this->deleteVariant($productId, (int)$vid); // respeta regla (pausa si tiene pedidos)
                }

                // Si ya no quedan variantes, también eliminamos selección (por si algún flujo la regeneró)
                $this->clearSelectionIfNoVariants($productId);

                $this->db->commit();
                return;
            }

            // Si al quitar un atributo se colapsan combinaciones, deduplicamos:
            // - si la variante tiene pedidos -> se pausa (no se elimina)
            // - si no tiene pedidos -> se elimina (y el stock se suma a la variante que queda)
            $this->deduplicateVariants($productId);

            // Recalcular títulos de variantes (si quedan valores)
            $this->refreshVariantTitles($productId);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Vista previa de impacto al quitar un atributo del producto.
     * No toca la base, solo calcula:
     * - cuántas variantes se eliminarían (sin pedidos)
     * - cuántas se pausarían (con pedidos)
     * - si al quitar este atributo el producto se queda sin atributos (reset)
     *
     * La lógica replica la deduplicación real (signature) para que el conteo sea representativo.
     *
     * @return array{will_delete:int, will_pause:int, will_reset:int, remaining_attrs_after:int}
     */
    public function previewRemoveAttribute(int $productId, int $attributeId): array
    {
        $isVariantAxis = $this->attributeGeneratesVariants($attributeId);
        if (!$isVariantAxis) {
            return [
                'will_delete' => 0,
                'will_pause' => 0,
                'will_reset' => 0,
                'remaining_attrs_after' => 0,
            ];
        }

        // Atributos que quedarían después de quitar este
        $cnt = $this->db->prepare("SELECT COUNT(*)
                                   FROM product_attributes pa
                                   JOIN attributes a ON a.id = pa.attribute_id
                                   WHERE pa.product_id=? AND pa.attribute_id<>? AND COALESCE(a.is_variant_axis, 0) = 1");
        $cnt->execute([$productId, $attributeId]);
        $remaining = (int)($cnt->fetchColumn() ?: 0);

        // Variantes del producto
        $stVars = $this->db->prepare("SELECT id, is_active FROM product_variants WHERE product_id=? ORDER BY id ASC");
        $stVars->execute([$productId]);
        $vars = $stVars->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        if (!$vars) {
            return [
                'will_delete' => 0,
                'will_pause' => 0,
                'will_reset' => ($remaining <= 0) ? 1 : 0,
                'remaining_attrs_after' => $remaining,
            ];
        }

        $orders = $this->ordersCountByVariant($productId);

        // Si ya no quedarían atributos, el removeAttribute real limpia TODAS las variantes:
        // - sin pedidos: se eliminan
        // - con pedidos: se pausan
        if ($remaining <= 0) {
            $del = 0;
            $pau = 0;
            foreach ($vars as $v) {
                $vid = (int)($v['id'] ?? 0);
                if ($vid <= 0) continue;
                $has = (int)($orders[$vid] ?? 0) > 0;
                if ($has) $pau++; else $del++;
            }
            return [
                'will_delete' => $del,
                'will_pause' => $pau,
                'will_reset' => 1,
                'remaining_attrs_after' => 0,
            ];
        }

        // Mapa de valores por variante (sin el atributo a quitar)
        $vv = $this->db->prepare(
            "SELECT pvv.variant_id, pvv.attribute_id, pvv.value_id
             FROM product_variant_values pvv
             JOIN product_variants pv ON pv.id = pvv.variant_id
             WHERE pv.product_id = ?"
        );
        $vv->execute([$productId]);
        $rows = $vv->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $mapByVariant = [];
        foreach ($vars as $v) {
            $vid = (int)($v['id'] ?? 0);
            if ($vid > 0) $mapByVariant[$vid] = [];
        }
        foreach ($rows as $r) {
            $vid = (int)($r['variant_id'] ?? 0);
            $aid = (int)($r['attribute_id'] ?? 0);
            $val = (int)($r['value_id'] ?? 0);
            if (!$vid || !$aid || !$val) continue;
            if ($aid === $attributeId) continue; // simulamos el remove
            $mapByVariant[$vid][$aid] = $val;
        }

        // Agrupar por signature
        $groups = [];
        foreach ($mapByVariant as $vid => $m) {
            $sig = $this->signature($m);
            $groups[$sig] ??= [];
            $groups[$sig][] = (int)$vid;
        }

        // Meta rápida (active)
        $activeById = [];
        foreach ($vars as $v) {
            $activeById[(int)($v['id'] ?? 0)] = ((int)($v['is_active'] ?? 1) === 1) ? 1 : 0;
        }

        $willDelete = 0;
        $willPause  = 0;

        foreach ($groups as $sig => $ids) {
            if (count($ids) <= 1) continue;

            // Elegir keeper con el mismo criterio que deduplicateVariants
            $meta = [];
            foreach ($ids as $vid) {
                $meta[] = [
                    'id' => (int)$vid,
                    'orders' => (int)($orders[(int)$vid] ?? 0),
                    'active' => (int)($activeById[(int)$vid] ?? 1),
                ];
            }

            usort($meta, function ($a, $b) {
                if ($a['orders'] === 0 && $b['orders'] > 0) return -1;
                if ($b['orders'] === 0 && $a['orders'] > 0) return 1;
                if ($a['active'] !== $b['active']) return $b['active'] <=> $a['active'];
                return $a['id'] <=> $b['id'];
            });

            $keeperId = (int)($meta[0]['id'] ?? 0);
            foreach ($ids as $vid) {
                $vid = (int)$vid;
                if ($vid === $keeperId) continue;
                $has = (int)($orders[$vid] ?? 0) > 0;
                if ($has) $willPause++; else $willDelete++;
            }
        }

        return [
            'will_delete' => (int)$willDelete,
            'will_pause' => (int)$willPause,
            'will_reset' => 0,
            'remaining_attrs_after' => (int)$remaining,
        ];
    }

    public function updateProductAttribute(int $productId, int $attributeId, int $sort, int $required): void
    {
        $st = $this->db->prepare("UPDATE product_attributes SET sort=:s, is_required=:r WHERE product_id=:p AND attribute_id=:a");
        $st->execute([':s'=>$sort, ':r'=>$required, ':p'=>$productId, ':a'=>$attributeId]);
    }

    private function attributeGeneratesVariants(int $attributeId): bool
    {
        if ($attributeId <= 0) {
            return false;
        }

        try {
            $st = $this->db->prepare("SELECT COALESCE(is_variant_axis, 0) FROM attributes WHERE id = ? LIMIT 1");
            $st->execute([$attributeId]);
            return (int)($st->fetchColumn() ?: 0) === 1;
        } catch (Throwable) {
            return false;
        }
    }

    
    public function setAttributeValues(int $productId, int $attributeId, array $valueIds): void
    {
        // Normalizamos IDs
        $valueIds = array_values(array_unique(array_map('intval', $valueIds)));
        // Validar que pertenezcan al atributo
        if ($valueIds) {
            $in = implode(',', array_fill(0, count($valueIds), '?'));
            $st = $this->db->prepare("SELECT id FROM attribute_values WHERE attribute_id=? AND id IN ($in)");
            $st->execute(array_merge([$attributeId], $valueIds));
            $okIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $valueIds = $okIds;
        }

        // Este método puede ser llamado desde otros flujos que ya están dentro de una transacción
        // (por ejemplo: alta rápida de valores). PDO no soporta transacciones anidadas.
        // Si ya hay una transacción activa, usamos SAVEPOINT para mantener atomicidad local.
        $alreadyInTx = $this->db->inTransaction();
        $rand = function_exists('random_bytes') ? bin2hex(random_bytes(4)) : uniqid('', true);
        $spName = 'sp_set_attr_vals_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$rand);

        if ($alreadyInTx) {
            $this->db->exec("SAVEPOINT {$spName}");
        } else {
            $this->db->beginTransaction();
        }

        try {
            $del = $this->db->prepare("DELETE FROM product_attribute_values WHERE product_id=? AND attribute_id=?");
            $del->execute([$productId, $attributeId]);

            if ($valueIds) {
                $ins = $this->db->prepare("INSERT INTO product_attribute_values (product_id, attribute_id, value_id) VALUES (?,?,?)");
                foreach ($valueIds as $vid) {
                    $ins->execute([$productId, $attributeId, $vid]);
                }
            }

            if ($alreadyInTx) {
                $this->db->exec("RELEASE SAVEPOINT {$spName}");
            } else {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($alreadyInTx) {
                try { $this->db->exec("ROLLBACK TO SAVEPOINT {$spName}"); } catch (Throwable $_) {}
            } else {
                $this->db->rollBack();
            }
            throw $e;
        }
    }


    


    /**
     * Crea valores para un atributo (si no existen) y los deja seleccionados para este producto.
     * Acepta texto pegado: uno por línea o separado por coma/punto y coma/tab.
     */

    /**
     * Limpieza segura: elimina un attribute_values.id si ya no está referenciado en ningún lado.
     * Se usa cuando el usuario cargó un valor por error y lo quitó del producto.
     *
     * Reglas:
     * - Si el valor está usado en product_attribute_values, product_variant_values o variant_value_set_items => NO se elimina.
     * - Si alguna tabla no existe en este entorno, se ignora (compat).
     */
    public function pruneAttributeValueIfUnused(int $valueId): bool
    {
        return (new VariantAttributeValueDeleteService($this->db))->pruneAttributeValueIfUnused($valueId);
    }

    /**
     * Elimina un valor de atributo validando uso real para evitar errores por FK y fallos silenciosos.
     *
     * @return array{ok:bool,code:string,message:string,attribute_id?:int,value_id?:int,value?:string,usage?:array<string,int>}
     */
    public function deleteAttributeValue(int $valueId, ?int $attributeId = null): array
    {
        return (new VariantAttributeValueDeleteService($this->db))->deleteAttributeValue($valueId, $attributeId);
    }

    public function createValuesAndSelect(int $productId, int $attributeId, string $raw): array
    {
            $raw = str_replace("\r", "\n", (string)$raw);

            $parts = [];
            foreach (explode("\n", $raw) as $line) {
                $chunks = preg_split('/[;,\t]/', (string)$line);
                foreach (($chunks ?: []) as $p) {
                    $v = trim((string)$p);
                    if ($v === '') continue;
                    $parts[] = $v;
                }
            }

            // uniq (case-insensitive) + límite defensivo
            // Nota: mbstring puede no estar habilitado en algunos entornos. Degradamos sin romper.
            $toLower = static function(string $s): string {
                return function_exists('mb_strtolower') ? (string)mb_strtolower($s, 'UTF-8') : strtolower($s);
            };
            $sub = static function(string $s, int $len): string {
                return function_exists('mb_substr') ? (string)mb_substr($s, 0, $len, 'UTF-8') : substr($s, 0, $len);
            };

            $values = [];
            $seen = [];
            foreach ($parts as $v) {
                $key = $toLower((string)$v);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $values[] = $sub((string)$v, 80);
                if (count($values) >= 300) break;
            }

            if (!$values) {
                return ['created'=>0, 'added'=>0, 'selection_total'=>0];
            }

            // Aseguramos que el atributo esté agregado al producto
            $this->addAttribute($productId, $attributeId);

            // Puede ejecutarse desde otros flujos que ya abrieron una transacción.
            // En ese caso evitamos el error "There is already an active transaction" y usamos SAVEPOINT.
            $alreadyInTx = $this->db->inTransaction();
            $rand = function_exists('random_bytes') ? bin2hex(random_bytes(4)) : uniqid('', true);
            $spName = 'sp_add_values_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$rand);

            if ($alreadyInTx) {
                // MySQL soporta SAVEPOINT dentro de una transacción activa
                $this->db->exec("SAVEPOINT {$spName}");
            } else {
                $this->db->beginTransaction();
            }

            try {
                // selección actual
                $st = $this->db->prepare("SELECT value_id FROM product_attribute_values WHERE product_id=? AND attribute_id=?");
                $st->execute([$productId, $attributeId]);
                $current = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
                $currentSet = array_flip($current);

                $created = 0;
                $newIds = [];

                // Detectamos esquema real de attribute_values (compat: value vs name, sort opcional)
                $valueCol = 'value';
                try { $this->db->query("SELECT `value` FROM attribute_values LIMIT 0"); } catch (Throwable $e) { $valueCol = 'name'; }

                $hasSort = true;
                try { $this->db->query("SELECT sort FROM attribute_values LIMIT 0"); } catch (Throwable $e) { $hasSort = false; }

                $insSql = $hasSort
                    ? "INSERT IGNORE INTO attribute_values (attribute_id, {$valueCol}, sort) VALUES (?,?,0)"
                    : "INSERT IGNORE INTO attribute_values (attribute_id, {$valueCol}) VALUES (?,?)";

                $selSql = "SELECT id FROM attribute_values WHERE attribute_id=? AND {$valueCol}=? LIMIT 1";

                $ins = $this->db->prepare($insSql);
                $sel = $this->db->prepare($selSql);

                foreach ($values as $val) {
                    $ins->execute([$attributeId, $val]);
                    if ($ins->rowCount() > 0) $created++;

                    $sel->execute([$attributeId, $val]);
                    $vid = (int)($sel->fetchColumn() ?: 0);
                    if ($vid > 0) $newIds[] = $vid;
                }

                $merged = $current;
                $added = 0;
                foreach ($newIds as $vid) {
                    if (isset($currentSet[$vid])) continue;
                    $merged[] = $vid;
                    $currentSet[$vid] = true;
                    $added++;
                }

                // Esto recalcula títulos si corresponde
                $this->setAttributeValues($productId, $attributeId, $merged);

                if ($alreadyInTx) {
                    $this->db->exec("RELEASE SAVEPOINT {$spName}");
                } else {
                    $this->db->commit();
                }
                return ['created'=>$created, 'added'=>$added, 'selection_total'=>count($merged)];
            } catch (\Throwable $e) {
                if ($alreadyInTx) {
                    // No podemos hacer rollback de la transacción padre; volvemos al savepoint.
                    try { $this->db->exec("ROLLBACK TO SAVEPOINT {$spName}"); } catch (\Throwable $_) {}
                } else {
                    $this->db->rollBack();
                }
                throw $e;
            }
        }



    public function suggestValues(int $productId, int $attributeId, string $q, int $limit = 12): array
    {
        $q = trim((string)$q);
        if ($productId <= 0 || $attributeId <= 0 || $q === '') return [];

        // compat columna value
        $valueCol = 'value';
        try { $this->db->query("SELECT `value` FROM attribute_values LIMIT 0"); } catch (Throwable $e) { $valueCol = 'name'; }

        // Meta del producto
        $stp = $this->db->prepare("SELECT category_id, brand_id FROM products WHERE id=? LIMIT 1");
        $stp->execute([$productId]);
        $pm = $stp->fetch(PDO::FETCH_ASSOC) ?: [];
        $categoryId = (int)($pm['category_id'] ?? 0);
        $brandId = (int)($pm['brand_id'] ?? 0);

        // Excluir lo que ya está disponible en el producto (para no sugerir redundante)
        $stEx = $this->db->prepare("SELECT value_id FROM product_attribute_values WHERE product_id=? AND attribute_id=?");
        $stEx->execute([$productId, $attributeId]);
        $existing = array_map('intval', $stEx->fetchAll(PDO::FETCH_COLUMN) ?: []);
        $existingSet = array_flip($existing);

        $out = [];
        $push = function(array $rows) use (&$out, $existingSet, $limit) {
            foreach ($rows as $r) {
                if (count($out) >= $limit) break;
                $id = (int)($r['id'] ?? 0);
                $val = (string)($r['value'] ?? '');
                if ($id <= 0 || $val === '') continue;
                if (isset($existingSet[$id])) continue;
                // dedupe
                foreach ($out as $e) { if ((int)$e['id'] === $id) { continue 2; } }
                $out[] = ['id'=>$id, 'value'=>$val];
            }
        };

        $like = '%'.$q.'%';

        // 1) Sets categoría+marca (y categoría genérica)
        try {
            if ($categoryId > 0) {
                $sql = "SELECT DISTINCT av.id, av.{$valueCol} AS value
                        FROM variant_value_sets s
                        JOIN variant_value_set_items i ON i.set_id = s.id
                        JOIN attribute_values av ON av.id = i.value_id
                        WHERE s.category_id = :cat
                          AND i.attribute_id = :aid
                          AND (s.brand_id IS NULL OR s.brand_id = :brand)
                          AND av.{$valueCol} LIKE :q
                        ORDER BY (s.brand_id IS NULL) ASC, av.{$valueCol} ASC, av.id ASC
                        LIMIT ".(int)$limit;
                $st = $this->db->prepare($sql);
                $st->execute([':cat'=>$categoryId, ':aid'=>$attributeId, ':brand'=>$brandId, ':q'=>$like]);
                $push($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
            }
        } catch (Throwable $e) {
            // si no existen tablas de sets, ignorar
        }

        // 2) Catálogo general (fallback)
        if (count($out) < $limit) {
            $sql = "SELECT id, {$valueCol} AS value FROM attribute_values
                    WHERE attribute_id = :aid AND {$valueCol} LIKE :q
                    ORDER BY {$valueCol} ASC, id ASC
                    LIMIT ".(int)$limit;
            $st = $this->db->prepare($sql);
            $st->execute([':aid'=>$attributeId, ':q'=>$like]);
            $push($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
        }

        return $out;
    }



}

