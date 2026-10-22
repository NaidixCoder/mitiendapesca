<?php
namespace App\Repositories\Imports;

use PDO;

/** Snapshot reversible de conjuntos de relaciones; sin editar repos de Catálogo. */
final class ImportAttributeRelationsRepository
{
    private const TABLES = [
        'relation_variant_values' => ['product_variant_values','variant_id',
            ['variant_id','attribute_id','value_id']],
        'relation_product_attrs' => ['product_attributes','product_id',
            ['product_id','attribute_id','sort','is_required']],
        'relation_product_attr_values' => ['product_attribute_values','product_id',
            ['product_id','attribute_id','value_id']],
    ];

    public function __construct(private PDO $db) {}

    public static function supports(string $entity): bool
    {
        return isset(self::TABLES[$entity]);
    }

    /** @return list<array<string,mixed>> */
    public function rows(string $entity, int $scopeId, bool $lock = false): array
    {
        if ($scopeId <= 0 || !isset(self::TABLES[$entity])) {
            throw new \RuntimeException('Relación de atributos no identificable.');
        }
        if ($lock && !$this->db->inTransaction()) {
            throw new \LogicException('Bloqueo de relaciones requiere transacción.');
        }
        [$table, $scope] = self::TABLES[$entity];
        $st = $this->db->prepare("SELECT * FROM {$table} WHERE {$scope}=?"
            . ($lock ? ' FOR UPDATE' : ''));
        $st->execute([$scopeId]);
        $encoded = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            ksort($row, SORT_STRING);
            $encoded[] = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        sort($encoded, SORT_STRING);
        return array_map(static fn(string $s): array => json_decode($s, true, 512, JSON_THROW_ON_ERROR), $encoded);
    }

    public function fingerprint(string $entity, int $scopeId, bool $lock = false): string
    {
        return hash('sha256', json_encode($this->rows($entity, $scopeId, $lock),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function capture(string $entity, int $scopeId): array
    {
        return ['entity'=>$entity,'action'=>'update','id'=>$scopeId,
            'before'=>['rows'=>$this->rows($entity, $scopeId, true)]];
    }

    public function restore(string $entity, int $scopeId, array $before): bool
    {
        if (!$this->db->inTransaction() || !isset(self::TABLES[$entity]) || $scopeId <= 0
            || !array_key_exists('rows', $before) || !is_array($before['rows'])) return false;
        [$table, $scope, $columns] = self::TABLES[$entity];

        // Validar BEFORE completo antes de borrar relaciones.
        foreach ($before['rows'] as $row) {
            if (!is_array($row) || array_diff(array_keys($row), $columns)
                || array_diff($columns, array_keys($row))
                || (int)($row[$scope] ?? 0) !== $scopeId) return false;
        }
        if (!$this->db->prepare("DELETE FROM {$table} WHERE {$scope}=?")->execute([$scopeId])) {
            return false;
        }
        if (!$before['rows']) return true;
        $names=implode(',', $columns);
        $marks=implode(',', array_fill(0,count($columns),'?'));
        $insert=$this->db->prepare("INSERT INTO {$table} ({$names}) VALUES ({$marks})");
        foreach ($before['rows'] as $row) {
            if (!$insert->execute(array_map(static fn(string $c) => $row[$c], $columns))) return false;
        }
        return true;
    }
}
