<?php
declare(strict_types=1);

namespace App\Services\Admin\Categories;

use PDO;
use Throwable;

/**
 * Política única de estado. No realiza SEO, no modifica productos y no audita:
 * su caller debe auditar únicamente después de un commit exitoso.
 */
final class CategoryVisibilityPolicyService
{
    private CategoryHierarchyGuard $hierarchy;

    public function __construct(private PDO $db)
    {
        $this->hierarchy = new CategoryHierarchyGuard($db);
    }

    /**
     * Estado final EXPLÍCITO; llamadas repetidas no vuelven a desactivar descendientes.
     * Si hay transacción externa, el llamador posee commit/rollback y los errores SQL
     * excepcionales se propagan para impedir que se confirme una transacción parcial.
     *
     * @return array<string,mixed>
     */
    public function setActive(int $categoryId, int $desiredActive): array
    {
        if ($desiredActive !== 0 && $desiredActive !== 1) {
            return ['ok' => false, 'code' => 'invalid_state', 'error' => 'Estado inválido.'];
        }
        return $this->transition($categoryId, $desiredActive);
    }

    /**
     * Adaptador semántico de toggle: calcula el estado objetivo dentro del mismo lock.
     * @return array<string,mixed>
     */
    public function toggle(int $categoryId): array
    {
        return $this->transition($categoryId, null);
    }

    /**
     * @return array<string,mixed>
     */
    private function transition(int $categoryId, ?int $requested): array
    {
        if ($categoryId <= 0) {
            return ['ok' => false, 'code' => 'invalid_id', 'error' => 'Categoría inválida.'];
        }

        $ownsTransaction = !$this->db->inTransaction();
        try {
            if ($ownsTransaction) $this->db->beginTransaction();

            // Bloqueo conservador de toda la jerarquía, en orden de clave primaria.
            // Evita dos transiciones simultáneas con snapshots incompatibles;
            // requiere valorar granularidad/phantoms antes de Ventana 2.
            $tree = $this->hierarchy->snapshot(true);
            if (!isset($tree[$categoryId])) {
                if ($ownsTransaction) $this->db->rollBack();
                return ['ok' => false, 'code' => 'not_found', 'error' => 'Categoría no encontrada.'];
            }

            $from = $tree[$categoryId]['is_active'];
            $to = $requested ?? (1 - $from);
            if ($to === $from) {
                if ($ownsTransaction) $this->db->commit();
                return [
                    'ok' => true, 'from' => $from, 'to' => $to, 'changed' => false,
                    'cascade_ids' => [$categoryId], 'children_count' => 0, 'products_count' => 0,
                ];
            }

            if ($to === 1) {
                $error = $this->hierarchy->validatePlacement(
                    $categoryId, $tree[$categoryId]['parent_id'], 1, $tree
                );
                if ($error !== null) {
                    if ($ownsTransaction) $this->db->rollBack();
                    return ['ok' => false, 'code' => 'inactive_ancestor', 'error' => $error];
                }
                $ids = [$categoryId];
            } else {
                $ids = array_merge(
                    [$categoryId],
                    $this->hierarchy->descendantIdsFromSnapshot($categoryId, $tree)
                );
            }

            $affectedProducts = $this->countProductsInCategories($ids);
            $params = array_merge([$to], $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $update = $this->db->prepare(
                "UPDATE categories SET is_active = ? WHERE id IN ($placeholders)"
            );
            if (!$update->execute($params)) {
                throw new \RuntimeException('No se pudo actualizar el estado.');
            }

            if ($ownsTransaction) $this->db->commit();
            return [
                'ok' => true, 'from' => $from, 'to' => $to, 'changed' => true,
                'cascade_ids' => $ids,
                'children_count' => max(0, count($ids) - 1),
                'products_count' => $affectedProducts,
            ];
        } catch (Throwable $e) {
            if (!$ownsTransaction) throw $e;
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) {
                log_error('CategoryVisibilityPolicyService: '.$e->getMessage());
            }
            return [
                'ok' => false,
                'code' => 'transaction_failed',
                'error' => 'No se pudo actualizar. Los cambios fueron revertidos.',
            ];
        }
    }

    /** @param int[] $categoryIds */
    private function countProductsInCategories(array $categoryIds): int
    {
        $ids = array_values(array_unique(array_map('intval', $categoryIds)));
        if ($ids === []) return 0;
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ($marks)");
        $stmt->execute($ids);
        return (int)$stmt->fetchColumn();
    }
}
