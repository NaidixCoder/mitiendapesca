<?php

namespace App\Repositories\Marketing;

use PDO;

/**
 * EmailProductPickerRepository
 * Búsqueda simple y segura para seleccionar productos desde el editor de campañas.
 *
 * Reglas:
 * - Solo lectura
 * - Prepared statements
 * - No asume columnas fuera del schema.sql (products, product_images)
 */
class EmailProductPickerRepository
{
    public function __construct(private PDO $db) {}

    /** @var array<string, array<string,bool>> */
    private static array $colsCache = [];

    /**
     * Devuelve un set de columnas (en minúsculas) para una tabla.
     * Si falla por permisos/entorno, devuelve array vacío.
     */
    private function cols(string $table): array
    {
        if (isset(self::$colsCache[$table])) {
            return self::$colsCache[$table];
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}`");
            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            $set = [];
            foreach ($rows as $r) {
                if (!isset($r['Field'])) {
                    continue;
                }
                $set[strtolower((string)$r['Field'])] = true;
            }
            self::$colsCache[$table] = $set;
            return $set;
        } catch (\Throwable $e) {
            self::$colsCache[$table] = [];
            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $q, int $limit = 20): array
    {
        $q = trim($q);
        if ($q === '') {
            return [];
        }

        $limit = max(1, min(50, (int)$limit));
        $isNumeric = ctype_digit($q);
        $id = $isNumeric ? (int)$q : null;

        // Hacemos la query tolerante a schemas viejos: si faltan columnas,
        // devolvemos defaults en lugar de romper.
        $pCols = $this->cols('products');
        $piCols = $this->cols('product_images');

        $hasShort = isset($pCols['short_desc']);
        $hasList  = isset($pCols['list_price']);
        $hasCreated = isset($pCols['created_at']);

        $hasPI = !empty($piCols);
        $piOrder = 'pi.id ASC';
        if ($hasPI) {
            $parts = [];
            if (isset($piCols['is_cover'])) {
                $parts[] = 'pi.is_cover DESC';
            }
            if (isset($piCols['sort'])) {
                $parts[] = 'pi.sort ASC';
            }
            $parts[] = 'pi.id ASC';
            $piOrder = implode(', ', $parts);
        }

        // Armamos SQL compatible y segura (limit inlined como int).
        // Nota: para queries numéricas, buscamos por id exacto y también por SKU/slug/nombre.
        $selectShort = $hasShort ? 'p.short_desc' : "''";
        $selectList  = $hasList ? 'p.list_price' : '0';
        $selectImage = $hasPI
            ? "(SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY {$piOrder} LIMIT 1)"
            : "''";

        $orderBy = $hasCreated ? 'p.created_at DESC' : 'p.id DESC';

		// Nota: algunos drivers PDO no soportan reutilizar el mismo placeholder nombrado
		// múltiples veces (ej. :like en 3 condiciones), y arrojan HY093.
		// Por eso usamos placeholders únicos.
		$sql = "SELECT "
			. "  p.id, p.sku, p.slug, p.name, {$selectShort} AS short_desc, p.price, {$selectList} AS list_price, "
			. "  {$selectImage} AS image_path "
			. "FROM products p "
			. "WHERE p.is_active = 1 "
			. "AND ("
			. ($isNumeric ? "p.id = :pid OR " : "")
			. "     p.sku LIKE :q_sku "
			. "  OR p.slug LIKE :q_slug "
			. "  OR p.name LIKE :q_name "
			. ") "
			. "ORDER BY "
			. ($isNumeric ? " (p.id = :pid_order) DESC, " : "")
			. " {$orderBy} "
			. "LIMIT {$limit}";

        $stmt = $this->db->prepare($sql);

		$like = '%' . $q . '%';
		$stmt->bindValue(':q_sku', $like, PDO::PARAM_STR);
		$stmt->bindValue(':q_slug', $like, PDO::PARAM_STR);
		$stmt->bindValue(':q_name', $like, PDO::PARAM_STR);
		if ($isNumeric) {
			$stmt->bindValue(':pid', $id, PDO::PARAM_INT);
			$stmt->bindValue(':pid_order', $id, PDO::PARAM_INT);
		}

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $r['id'] = (int)($r['id'] ?? 0);
            $r['price'] = (int)($r['price'] ?? 0);
            $r['list_price'] = (int)($r['list_price'] ?? 0);
            $r['image_path'] = (string)($r['image_path'] ?? '');
            $r['sku'] = (string)($r['sku'] ?? '');
            $r['slug'] = (string)($r['slug'] ?? '');
            $r['name'] = (string)($r['name'] ?? '');
            $r['short_desc'] = (string)($r['short_desc'] ?? '');
        }
        unset($r);

        return $rows;
    }
}
