<?php
namespace App\Services\Import;

final class ImportResult
{
    public const APPLY_NOT_STARTED = 'not_started';
    public const APPLY_COMMITTED = 'committed';
    public const APPLY_ROLLED_BACK = 'rolled_back';
    public const APPLY_COMMIT_UNCERTAIN = 'commit_uncertain';

    /** Resultado de la transacción SQL, independiente de errores por fila. */
    public string $applyOutcome = self::APPLY_NOT_STARTED;

    public function setApplyOutcome(string $outcome): void
    {
        if (!in_array($outcome, [self::APPLY_NOT_STARTED, self::APPLY_COMMITTED,
            self::APPLY_ROLLED_BACK, self::APPLY_COMMIT_UNCERTAIN], true)) {
            throw new \InvalidArgumentException('Resultado de importación desconocido.');
        }
        $this->applyOutcome = $outcome;
    }

    /**
     * Total de filas analizadas (incluye OK/WARNING/SKIPPED/ERROR).
     */
    public int $total = 0;

    /**
     * Filas aplicables (OK + WARNING). No incluye SKIPPED ni ERROR.
     */
    public int $ok = 0;

    /**
     * Filas saltadas/ignoradas (sin cambios o sin acción aplicable).
     */
    public int $skipped = 0;

    /**
     * Filas con error (bloqueantes).
     */
    public int $errors = 0;

    /**
     * Filas con warning (no bloqueantes). Nota: ok incluye warnings.
     */
    public int $warnings = 0;

    /** @var array<int, array{row:int,error:string}> */
    public array $list = [];

    /** @var array<int, array{row:int,warning:string}> */
    public array $warnList = [];

    /**
     * Fila por fila: diagnóstico del dry-run (y también útil para auditoría del apply).
     * @var array<int, array{
     *   row:int,
     *   tipo_fila:string,
     *   sku:string,
     *   sku_padre:string,
     *   entidad:string,
     *   accion:string,
     *   notas:array<int,string>,
     *   level:string
     * }>
     */
    public array $rows = [];

    /**
     * Plan agregado (contadores).
     * @var array<string,int>
     */
    public array $plan = [
        'create_products' => 0,
        'update_products' => 0,
        'noop_products'   => 0,
        'create_variants' => 0,
        'update_variants' => 0,
        'noop_variants'   => 0,
        'create_parents_from_variants' => 0,
        'create_brands' => 0,
        'create_categories' => 0,
        'create_suppliers' => 0,
    ];

    /**
     * Detalle del plan (listas únicas acotadas para preview).
     * @var array{brands:array<int,string>,categories:array<int,string>,suppliers:array<int,string>}
     */
    public array $plan_details = [
        'brands' => [],
        'categories' => [],
        'suppliers' => [],
    ];

    public function addPlanned(string $type, string $name): void
    {
        if ($type === 'categories') $type = 'categories';
        else if ($type === 'suppliers') $type = 'suppliers';
        else $type = 'brands';

        $name = trim($name);
        if ($name === '') return;

        // único, acotado
        if (!in_array($name, $this->plan_details[$type], true)) {
            if (count($this->plan_details[$type]) < 500) {
                $this->plan_details[$type][] = $name;
            }
        }
    }

    public function addError(int $row, string $error): void
    {
        $this->errors++;
        $this->list[] = ['row' => $row, 'error' => $error];
        $this->total++;
    }

    public function addWarning(int $row, string $warning): void
    {
        // Warnings globales (compatibilidad): solo lista. El conteo de warnings por fila se hace con addOkWithWarning().
        $this->warnList[] = ['row' => $row, 'warning' => $warning];
    }

    /**
     * @param array{row:int,tipo_fila:string,sku?:string,sku_padre?:string,entidad?:string,accion?:string,notas?:array<int,string>,level?:string} $r
     */
    public function addRow(array $r): void
    {
        $this->rows[] = [
            'row' => (int)($r['row'] ?? 0),
            'tipo_fila' => (string)($r['tipo_fila'] ?? ''),
            'sku' => (string)($r['sku'] ?? ''),
            'sku_padre' => (string)($r['sku_padre'] ?? ''),
            'entidad' => (string)($r['entidad'] ?? ''),
            'accion' => (string)($r['accion'] ?? ''),
            'notas' => (array)($r['notas'] ?? []),
            'level' => (string)($r['level'] ?? 'ok'),
        ];
    }

    public function inc(string $key, int $by = 1): void
    {
        if (!array_key_exists($key, $this->plan)) $this->plan[$key] = 0;
        $this->plan[$key] += $by;
    }

    /**
     * Finaliza contadores derivados de listas únicas (para evitar contar por fila).
     * Esto es clave para casos como: 11 productos con la misma categoría => 1 categoría nueva.
     */
    public function finalizePlan(): void
    {
        $this->plan['create_brands'] = count($this->plan_details['brands'] ?? []);
        $this->plan['create_categories'] = count($this->plan_details['categories'] ?? []);
        $this->plan['create_suppliers'] = count($this->plan_details['suppliers'] ?? []);
    }

    public function addOk(): void
    {
        $this->ok++;
        $this->total++;
    }

    public function addOkWithWarning(): void
    {
        $this->ok++;
        $this->warnings++;
        $this->total++;
    }

    public function addSkipped(): void
    {
        $this->skipped++;
        $this->total++;
    }

    public function toArray(): array
    {
        return [
            'apply_outcome' => $this->applyOutcome,
            'total' => $this->total,
            'ok' => $this->ok,
            'skipped' => $this->skipped,
            'err' => $this->errors,
            'warnings' => $this->warnings,
            'errors' => $this->list,
            'warn_list' => $this->warnList,
            'plan' => $this->plan,
            'plan_details' => $this->plan_details,
            'rows' => $this->rows,
        ];
    }
}
