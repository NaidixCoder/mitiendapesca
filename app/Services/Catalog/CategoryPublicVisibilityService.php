<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use PDO;

/**
 * Visibilidad pública exclusivamente de CATEGORÍAS.
 *
 * El estado guardado NO se modifica: visible = nodo y ancestros activos,
 * camino finito hacia una raíz válida, sin ciclos ni padres inexistentes.
 * Una lectura de toda la jerarquía por instancia y memoización O(N);
 * no hace consultas por cada categoría, ni decide visibilidad de productos.
 */
final class CategoryPublicVisibilityService
{
    /** @var array<int,array{parent_id:?int,is_active:bool}>|null */
    private ?array $nodes = null;

    /** @var array<int,bool>|null */
    private ?array $visible = null;

    public function __construct(private PDO $db) {}

    /** @return array<int,bool> IDs elegibles como claves; solo valores true. */
    public function visibleIdSet(): array
    {
        if ($this->visible !== null) {
            return $this->visible;
        }

        $rows = $this->db->query('SELECT id,parent_id,is_active FROM categories')->fetchAll(PDO::FETCH_ASSOC);
        $this->nodes = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            if ($id <= 0) continue;
            $this->nodes[$id] = [
                'parent_id' => $row['parent_id'] === null ? null : (int)$row['parent_id'],
                'is_active' => (int)$row['is_active'] === 1,
            ];
        }

        $resolved = [];
        foreach ($this->nodes as $id => $_) {
            if (array_key_exists($id, $resolved)) continue;

            $path = [];
            $seen = [];
            $current = $id;
            while (true) {
                if (array_key_exists($current, $resolved)) {
                    $valid = $resolved[$current];
                    break;
                }
                if (isset($seen[$current]) || !isset($this->nodes[$current])) {
                    $valid = false; // ciclo, ID huérfano o padre inválido
                    break;
                }
                $seen[$current] = true;
                $path[] = $current;
                $node = $this->nodes[$current];
                if (!$node['is_active']) {
                    $valid = false;
                    break;
                }
                if ($node['parent_id'] === null) {
                    $valid = true;
                    break;
                }
                if ($node['parent_id'] <= 0) {
                    $valid = false;
                    break;
                }
                $current = $node['parent_id'];
            }
            foreach ($path as $nodeId) $resolved[$nodeId] = $valid;
        }
        $this->visible = array_filter($resolved);
        return $this->visible;
    }

    public function isVisibleId(int $id): bool
    {
        if ($id <= 0) return false;
        return isset($this->visibleIdSet()[$id]);
    }
}
