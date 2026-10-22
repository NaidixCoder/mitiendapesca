<?php
namespace App\Repositories\Imports;

use PDO;
use PDOException;

/**
 * Repo "dueño" del rollback v2.
 *
 * Regla: SQL vive acá, el Service solo orquesta.
 */
final class ImportRevertRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Huella del registro completo persistido. Tabla/PK de lista blanca;
     * los campos se leen todos para cubrir cambios no previstos, incluso NULL y DECIMAL.
     * La consulta FOR UPDATE solo puede ejecutarse durante una transacción de reversión.
     */
    public function fingerprint(string $entity, int $id, bool $lock = false): ?string
    {
        if (ImportAttributeRelationsRepository::supports($entity)) {
            return (new ImportAttributeRelationsRepository($this->db))->fingerprint($entity, $id, $lock);
        }
        $locations = [
            'product' => ['products', 'id'],
            'variant' => ['product_variants', 'id'],
            'inventory_product' => ['inventory', 'product_id'],
            'inventory_variant' => ['inventory_variants', 'variant_id'],
            'brand' => ['brands', 'id'],
            'category' => ['categories', 'id'],
            'supplier' => ['suppliers', 'id'],
            'attribute' => ['attributes', 'id'],
            'attribute_value' => ['attribute_values', 'id'],
        ];
        if ($id <= 0 || !isset($locations[$entity])) {
            throw new \RuntimeException('Entidad/PK no verificable para reversión: '.$entity);
        }
        if ($lock && !$this->db->inTransaction()) {
            throw new \LogicException('FOR UPDATE requiere transacción activa');
        }
        [$table, $pk] = $locations[$entity];
        $sql = "SELECT * FROM `{$table}` WHERE `{$pk}` = ? LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
        $st = $this->db->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) return null;
        ksort($row, SORT_STRING);
        // PDO MySQL retorna NULL o cadenas para enteros/decimales: sin roundings ni casts.
        return hash('sha256', json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Para altas que puedan ser eliminadas, verificar también el árbol FK
     * completo de registros dependientes; así un CASCADE no pierde una edición
     * manual en variantes/atributos aun si el padre permanece idéntico.
     */
    public function dependentFingerprint(string $entity, int $id, bool $lock = false): string
    {
        $parents = [
            'product'=>'products','variant'=>'product_variants',
            'brand'=>'brands','category'=>'categories','supplier'=>'suppliers',
            'attribute'=>'attributes','attribute_value'=>'attribute_values',
            'inventory_product'=>'inventory','inventory_variant'=>'inventory_variants',
        ];
        $edges = [
            'products'=>[['cart_items','product_id'],['inventory','product_id'],
                ['order_items','product_id'],['product_attributes','product_id'],
                ['product_attribute_values','product_id'],['product_catalog_tags','product_id'],
                ['product_images','product_id'],['product_seo_blocks','product_id'],
                ['product_specs','product_id'],['product_variants','product_id'],
                ['user_product_favorites','product_id']],
            'product_variants'=>[['cart_items','variant_id'],['inventory_variants','variant_id'],
                ['order_items','variant_id'],['product_images','variant_id'],
                ['product_variant_values','variant_id'],['variant_catalog_tags','variant_id']],
            'brands'=>[['brand_seo_blocks','brand_id'],['products','brand_id'],['variant_value_sets','brand_id']],
            'categories'=>[['categories','parent_id'],['category_seo_blocks','category_id'],
                ['products','category_id'],['variant_value_sets','category_id']],
            'suppliers'=>[['purchase_invoices','supplier_id']],
            'attributes'=>[['attribute_values','attribute_id'],['product_attributes','attribute_id'],
                ['product_attribute_values','attribute_id'],['product_variant_values','attribute_id'],
                ['variant_value_set_items','attribute_id']],
            'attribute_values'=>[['product_attribute_values','value_id'],
                ['product_variant_values','value_id'],['variant_value_set_items','value_id']],
            'variant_value_sets'=>[['variant_value_set_items','set_id']],
        ];
        if ($id <= 0 || !isset($parents[$entity])) throw new \RuntimeException('Árbol de dependencias no conocido');
        if ($lock && !$this->db->inTransaction()) throw new \LogicException('Árbol FK requiere transacción');

        $visited = [];
        $scan = function(string $table, int $parentId) use (&$scan,&$visited,$edges,$lock): array {
            $key=$table.':'.$parentId;
            if (isset($visited[$key])) return [];
            $visited[$key]=true;
            $expected=$edges[$table] ?? [];
            // Si el esquema ganó un FK no incluido, no declarar seguridad.
            $check=$this->db->prepare("SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME=? AND TABLE_NAME<>''");
            $check->execute([$table]);
            $known=[];
            foreach($expected as [$t,$col])$known[$t.':'.$col]=true;
            foreach($check->fetchAll(PDO::FETCH_ASSOC) as $fk) {
                if (($fk['REFERENCED_COLUMN_NAME'] ?? '') !== 'id'
                    || !isset($known[$fk['TABLE_NAME'].':'.$fk['COLUMN_NAME']])) {
                    throw new \RuntimeException('Dependencia nueva/no verificable de '.$table);
                }
            }
            $result=[];
            foreach($expected as [$child,$fk]) {
                $sql="SELECT * FROM `{$child}` WHERE `{$fk}` = ?".($lock?' FOR UPDATE':'');
                $st=$this->db->prepare($sql);
                $st->execute([$parentId]);
                $lines=[];
                foreach($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $childId=isset($row['id'])?(int)$row['id']:0;
                    ksort($row,SORT_STRING);
                    $data=['row'=>$row];
                    if (isset($edges[$child])) {
                        if ($childId<=0) throw new \RuntimeException('Dependencia sin PK identificable');
                        $data['children']=$scan($child,$childId);
                    }
                    $lines[]=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                }
                sort($lines,SORT_STRING);
                $result[$child.':'.$fk]=$lines;
            }
            ksort($result,SORT_STRING);
            return $result;
        };
        return hash('sha256',json_encode($scan($parents[$entity],$id),
            JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    /**
     * Barrera global de conciliación. Escanea la tabla COMPLETA por PRIMARY
     * bajo InnoDB REPEATABLE READ + FOR UPDATE (next-key/gap locks).
     *
     * Esto serializa cambios de estado sobre jobs existentes Y evita inserts
     * fantasma mientras la reversión mantiene la transacción abierta.
     * Usar el MISMO orden descendente que LIFO/lockJob.
     *
     * @return list<int> IDs de importaciones con commit no conciliado.
     */
    public function lockPendingReconciliationJobIds(): array
    {
        if (!$this->db->inTransaction()) {
            throw new \LogicException('Conciliación LIFO requiere transacción activa');
        }
        // Sin WHERE status: un índice de estado dejaría gaps de otros estados
        // desbloqueados, posibilitando que aparezca un review_required nuevo.
        $st = $this->db->query(
            'SELECT id, status FROM import_jobs FORCE INDEX(PRIMARY) ORDER BY id DESC FOR UPDATE'
        );
        if (!$st) throw new \RuntimeException('No pudo verificarse la conciliación de importaciones');
        $pending = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if ((string)($row['status'] ?? '') === 'review_required') {
                $pending[] = (int)$row['id'];
            }
        }
        return $pending;
    }

    /** Serializa la comprobación LIFO con locks de filas en la misma transacción. */
    public function lockLatestPendingJobId(): int
    {
        if (!$this->db->inTransaction()) throw new \LogicException('LIFO requiere transacción');
        $st = $this->db->query("SELECT id FROM import_jobs
            WHERE status IN ('finished','ok')
              AND COALESCE(revert_status,'not_reverted') NOT IN ('reverted','reverted_with_warnings')
            ORDER BY id DESC LIMIT 1 FOR UPDATE");
        return (int)($st->fetchColumn() ?: 0);
    }

    public function lockJob(int $id): ?array
    {
        if (!$this->db->inTransaction()) throw new \LogicException('Job lock requiere transacción');
        $st = $this->db->prepare('SELECT id,status,revert_status FROM import_jobs WHERE id=? FOR UPDATE');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }


    /**
     * Obtiene updated_at si la entidad lo soporta.
     * @return ?string datetime (Y-m-d H:i:s) o null si no existe/columna ausente
     */
    public function getUpdatedAt(string $entity, int $id): ?string
    {
        $table = null;
        switch ($entity) {
            case 'product': $table = 'products'; break;
            case 'variant': $table = 'product_variants'; break;
        }
        if (!$table) return null;
        if (!$this->hasColumn($table, 'updated_at')) return null;

        $st = $this->db->prepare("SELECT updated_at FROM `{$table}` WHERE id=? LIMIT 1");
        $st->execute([(int)$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $v = $row['updated_at'] ?? null;
        return is_string($v) && trim($v) !== '' ? substr($v, 0, 19) : null;
    }

    public function exists(string $entity, int $id): bool
    {
        $table = null;
        switch ($entity) {
            case 'product': $table = 'products'; break;
            case 'variant': $table = 'product_variants'; break;
            case 'inventory_product': $table = 'inventory'; break;
            case 'inventory_variant': $table = 'inventory_variants'; break;
            case 'brand': $table = 'brands'; break;
            case 'category': $table = 'categories'; break;
            case 'supplier': $table = 'suppliers'; break;
            case 'attribute': $table = 'attributes'; break;
            case 'attribute_value': $table = 'attribute_values'; break;
        }
        if (!$table) return false;

        if ($entity === 'inventory_product') {
            $st = $this->db->prepare("SELECT 1 FROM `{$table}` WHERE product_id=? LIMIT 1");
            $st->execute([(int)$id]);
            return (bool)$st->fetchColumn();
        }
        if ($entity === 'inventory_variant') {
            $st = $this->db->prepare("SELECT 1 FROM `{$table}` WHERE variant_id=? LIMIT 1");
            $st->execute([(int)$id]);
            return (bool)$st->fetchColumn();
        }

        $st = $this->db->prepare("SELECT 1 FROM `{$table}` WHERE id=? LIMIT 1");
        $st->execute([(int)$id]);
        return (bool)$st->fetchColumn();
    }

    /** @param array<string,mixed> $pk @param array<string,mixed> $before */
    public function restore(string $entity, array $pk, array $before): bool
    {
        $id = isset($pk['id']) ? (int)$pk['id'] : 0;
        if (ImportAttributeRelationsRepository::supports($entity)) {
            return (new ImportAttributeRelationsRepository($this->db))->restore($entity, $id, $before);
        }
        if ($id <= 0) return false;
        if (!$before) return true;

        switch ($entity) {
            case 'product':
                return $this->updateById('products', $id, $this->filter($before, [
                    'name','slug','sku','internal_code','sale_mode','brand_id','category_id','supplier_id',
                    'short_desc','long_desc','price','list_price','price_from_cache','list_price_from_cache','cost','cost_currency_id','margin_pct','barcode','iva_rate',
                    'ship_weight','ship_height','ship_width','ship_length','is_active','meta_title','meta_description'
                ]));

            case 'variant':
                return $this->updateById('product_variants', $id, $this->filter($before, [
                    'internal_code','product_id','sku','title','price','list_price','cost','cost_currency_id','margin_pct','is_active'
                ]));

            case 'inventory_product':
                // inventory: PK = product_id
                return $this->updateByPk('inventory', ['product_id'=>$id], $this->filter($before, ['stock','low_stock_threshold']));

            case 'inventory_variant':
                return $this->updateByPk('inventory_variants', ['variant_id'=>$id], $this->filter($before, ['stock','low_stock_threshold']));

            case 'brand':
                return $this->updateById('brands', $id, $this->filter($before, ['name','slug','is_active']));

            case 'category':
                return $this->updateById('categories', $id, $this->filter($before, ['name','slug','parent_id','is_active']));

            case 'supplier':
                return $this->updateById('suppliers', $id, $this->filter($before, ['nombre','cuit','email','telefono','condicion_iva','is_active']));

            case 'attribute':
                return $this->updateById('attributes', $id, $this->filter($before, ['name','slug','type','is_active']));

            case 'attribute_value':
                return $this->updateById('attribute_values', $id, $this->filter($before, ['attribute_id','value','sort','is_active']));
        }

        return false;
    }

    /**
     * Delete hard. Si falla por FK, intenta inactivar si existe columna compatible.
     * @param array<string,mixed> $pk
     * @return array{ok:bool, fk_block?:bool, deactivated?:bool}
     */
    public function deleteResult(string $entity, array $pk): array
    {
        $id = isset($pk['id']) ? (int)$pk['id'] : 0;
        if ($id <= 0) return ['ok'=>false];

        try {
            switch ($entity) {
                case 'inventory_variant':
                    return ['ok'=>$this->deleteWhere('inventory_variants', ['variant_id'=>$id])];
                case 'inventory_product':
                    return ['ok'=>$this->deleteWhere('inventory', ['product_id'=>$id])];
                case 'variant':
                    return ['ok'=>$this->deleteWhere('product_variants', ['id'=>$id])];
                case 'product':
                    return ['ok'=>$this->deleteWhere('products', ['id'=>$id])];
                case 'brand':
                    return ['ok'=>$this->deleteWhere('brands', ['id'=>$id])];
                case 'category':
                    return ['ok'=>$this->deleteWhere('categories', ['id'=>$id])];
                case 'supplier':
                    return ['ok'=>$this->deleteWhere('suppliers', ['id'=>$id])];
                case 'attribute_value':
                    return ['ok'=>$this->deleteWhere('attribute_values', ['id'=>$id])];
                case 'attribute':
                    return ['ok'=>$this->deleteWhere('attributes', ['id'=>$id])];
            }
        } catch (PDOException $e) {
            // 23000: integrity constraint violation
            if ((string)$e->getCode() === '23000') {
                $deactivated = $this->deactivateIfPossible($entity, $id);
                return ['ok'=>false,'fk_block'=>true,'deactivated'=>$deactivated];
            }
            throw $e;
        }

        return ['ok'=>false];
    }

    /** Compat: mantiene firma anterior */
    public function delete(string $entity, array $pk): bool
    {
        $r = $this->deleteResult($entity, $pk);
        return (bool)($r['ok'] ?? false);
    }

    private function deactivateIfPossible(string $entity, int $id): bool
    {
        $table = null;
        $pkCol = 'id';
        switch ($entity) {
            case 'product': $table = 'products'; break;
            case 'variant': $table = 'product_variants'; break;
            case 'brand': $table = 'brands'; break;
            case 'category': $table = 'categories'; break;
            case 'supplier': $table = 'suppliers'; break;
            case 'attribute': $table = 'attributes'; break;
            case 'attribute_value': $table = 'attribute_values'; break;
        }
        if (!$table) return false;
        // Preferimos is_active si existe
        if ($this->hasColumn($table, 'is_active')) {
            $st = $this->db->prepare("UPDATE `{$table}` SET is_active=0 WHERE `{$pkCol}`=? LIMIT 1");
            return (bool)$st->execute([(int)$id]);
        }
        // Fallback: published / status (si existen)
        if ($this->hasColumn($table, 'published')) {
            $st = $this->db->prepare("UPDATE `{$table}` SET published=0 WHERE `{$pkCol}`=? LIMIT 1");
            return (bool)$st->execute([(int)$id]);
        }
        if ($this->hasColumn($table, 'status')) {
            $st = $this->db->prepare("UPDATE `{$table}` SET status='draft' WHERE `{$pkCol}`=? LIMIT 1");
            return (bool)$st->execute([(int)$id]);
        }
        return false;
    }

    /**
     * @param array<string,mixed> $fields
     */
    private function updateById(string $table, int $id, array $fields): bool
    {
        if (!$fields) return true;

        // supplier_id puede no existir (instancias viejas). Lo evitamos si no está.
        if ($table === 'products' && array_key_exists('supplier_id', $fields) && !$this->hasColumn('products','supplier_id')) {
            unset($fields['supplier_id']);
        }

        $set = [];
        $params = [];
        foreach ($fields as $k=>$v) {
            $set[] = "`{$k}` = ?";
            $params[] = $v;
        }
        if (!$set) return true;
        $params[] = $id;

        $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE id = ? LIMIT 1";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute($params);
    }

    /** @param array<string,int> $pk @param array<string,mixed> $fields */
    private function updateByPk(string $table, array $pk, array $fields): bool
    {
        if (!$fields) return true;

        $where = [];
        $params = [];
        foreach ($fields as $k=>$v) {
            $params[] = $v;
        }

        $set = [];
        foreach (array_keys($fields) as $k) {
            $set[] = "`{$k}` = ?";
        }

        foreach ($pk as $k=>$v) {
            $where[] = "`{$k}` = ?";
            $params[] = (int)$v;
        }

        $sql = "UPDATE `{$table}` SET " . implode(', ', $set) . " WHERE " . implode(' AND ', $where) . " LIMIT 1";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute($params);
    }

    /** @param array<string,int> $where */
    private function deleteWhere(string $table, array $where): bool
    {
        $conds = [];
        $params = [];
        foreach ($where as $k=>$v) {
            $conds[] = "`{$k}` = ?";
            $params[] = (int)$v;
        }
        if (!$conds) return false;

        $sql = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $conds) . " LIMIT 1";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute($params);
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            $st = $this->db->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $st->execute([$column]);
            return (bool)$st->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    /** @param array<string,mixed> $arr @param array<int,string> $keys */
    private function filter(array $arr, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $arr)) $out[$k] = $arr[$k];
        }
        return $out;
    }
}

