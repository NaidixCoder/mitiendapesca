<?php
declare(strict_types=1);

namespace App\Services\Admin\Categories;

use PDO;

/**
 * Solo reglas de jerarquía; no persiste estados ni abre transacciones.
 * El llamador que necesite consistencia debe usar snapshot(true) en SU transacción.
 */
final class CategoryHierarchyGuard
{
    public function __construct(private PDO $db) {}

    /**
     * @return array<int,array{id:int,parent_id:?int,is_active:int}>
     */
    public function snapshot(bool $forUpdate = false): array
    {
        if ($forUpdate && !$this->db->inTransaction()) {
            throw new \LogicException('La lectura bloqueante requiere una transacción activa.');
        }

        // Orden estable de locks para reducir deadlocks en las transiciones de estado.
        $sql = 'SELECT id, parent_id, is_active FROM categories ORDER BY id';
        if ($forUpdate) $sql .= ' FOR UPDATE';
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $indexed = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            $indexed[$id] = [
                'id' => $id,
                'parent_id' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
                'is_active' => (int)$row['is_active'] === 1 ? 1 : 0,
            ];
        }
        return $indexed;
    }

    /** Mantiene el contrato anterior: solo existencia de padre y ausencia de ciclos. */
    public function validateParent(int $categoryId, ?int $parentId): ?string
    {
        return $this->parentError($categoryId, $parentId, $this->snapshot());
    }

    /**
     * Valida una nueva ubicación y estado, sin mutar filas.
     * @param array<int,array{id:int,parent_id:?int,is_active:int}>|null $tree
     */
    public function validatePlacement(int $categoryId, ?int $parentId, int $desiredActive, ?array $tree = null): ?string
    {
        if ($desiredActive !== 0 && $desiredActive !== 1) {
            return 'Estado de categoría inválido.';
        }
        $tree ??= $this->snapshot();
        $error = $this->parentError($categoryId, $parentId, $tree);
        if ($error !== null || $parentId === null) return $error;

        $seen = [];
        $ancestor = $parentId;
        $inactiveAncestor = null;
        while ($ancestor !== null) {
            if (isset($seen[$ancestor]) || !isset($tree[$ancestor])) {
                return 'La jerarquía seleccionada es inválida.';
            }
            $seen[$ancestor] = true;
            if ($tree[$ancestor]['is_active'] === 0) {
                $inactiveAncestor = $ancestor;
                break;
            }
            $ancestor = $tree[$ancestor]['parent_id'];
        }
        if ($inactiveAncestor === null) return null;

        if ($desiredActive === 1) {
            return 'No se puede activar una categoría bajo un ancestro inactivo (#'.$inactiveAncestor.').';
        }
        // Reubicar una raíz inactiva con hijas activas bajo una rama inactiva
        // introduciría más descendientes activos incoherentes: no sanear automáticamente.
        if ($categoryId > 0 && isset($tree[$categoryId])) {
            foreach ($this->descendantIdsFromSnapshot($categoryId, $tree) as $childId) {
                if ($tree[$childId]['is_active'] === 1) {
                    return 'El subárbol contiene descendientes activos y no puede ubicarse bajo un ancestro inactivo.';
                }
            }
        }
        return null;
    }

    /** @return int[] */
    public function descendantIds(int $categoryId): array
    {
        return $this->descendantIdsFromSnapshot($categoryId, $this->snapshot());
    }

    /**
     * Devuelve descendientes sin la raíz, una sola vez aun si los datos heredados tienen ciclos.
     * @param array<int,array{id:int,parent_id:?int,is_active:int}> $tree
     * @return int[]
     */
    public function descendantIdsFromSnapshot(int $categoryId, array $tree): array
    {
        $children = [];
        foreach ($tree as $row) {
            if ($row['parent_id'] !== null) $children[$row['parent_id']][] = $row['id'];
        }
        $seen = [$categoryId => true];
        $pending = [$categoryId];
        $result = [];
        while ($pending) {
            $node = array_pop($pending);
            foreach ($children[$node] ?? [] as $child) {
                if (isset($seen[$child])) continue;
                $seen[$child] = true;
                $result[] = $child;
                $pending[] = $child;
            }
        }
        sort($result, SORT_NUMERIC);
        return $result;
    }

    /**
     * @param array<int,array{id:int,parent_id:?int,is_active:int}> $tree
     */
    private function parentError(int $categoryId, ?int $parentId, array $tree): ?string
    {
        if ($parentId === null) return null;
        if ($parentId <= 0 || ($categoryId > 0 && $parentId === $categoryId)) {
            return 'La categoría no puede ser su propio padre.';
        }

        $seen = [];
        $ancestor = $parentId;
        while ($ancestor !== null) {
            if (isset($seen[$ancestor]) || ($categoryId > 0 && $ancestor === $categoryId)) {
                return 'No se puede mover una categoría dentro de sus descendientes.';
            }
            if (!isset($tree[$ancestor])) return 'La categoría padre seleccionada no existe.';
            $seen[$ancestor] = true;
            $ancestor = $tree[$ancestor]['parent_id'];
        }
        return null;
    }
}
