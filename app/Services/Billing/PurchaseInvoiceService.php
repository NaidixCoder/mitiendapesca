<?php
namespace App\Services\Billing;

use App\Repositories\Billing\PurchaseInvoiceRepository;
use App\Repositories\Suppliers\ProveedorRepository;
use App\Support\Format\Number;
use PDO;

final class PurchaseInvoiceService
{
    private PurchaseInvoiceRepository $repo;
    private ProveedorRepository $suppliers;

    public function __construct(private PDO $db)
    {
        $this->repo = new PurchaseInvoiceRepository($db);
        $this->suppliers = new ProveedorRepository($db);
    }

    public function list(array $filters = []): array
    {
        return $this->repo->list($filters);
    }

    public function findById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function suppliersForSelect(string $q = ''): array
    {
        return $this->suppliers->list([
            'q' => $q,
            'limit' => 500,
                    ]);
    }

    /**
     * @return array{ok:bool,errors:array<string,string>,id?:int}
     */
    public function save(array $input, ?int $id = null): array
    {
        $errors = [];

        $supplierId = (int)($input['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            $errors['supplier_id'] = 'Seleccioná un proveedor.';
        }

        $issuedAt = trim((string)($input['issued_at'] ?? ''));
        if ($issuedAt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $issuedAt)) {
            $errors['issued_at'] = 'Fecha de emisión inválida.';
        }

        $cbteTipo = (int)($input['cbte_tipo'] ?? 0);
        $allowed = [1,6,11,3,8,13];
        if (!in_array($cbteTipo, $allowed, true)) {
            $errors['cbte_tipo'] = 'Tipo de comprobante inválido.';
        }

        $ptoVta = Number::toInt($input['pto_vta'] ?? 0);
        if ($ptoVta < 0) $ptoVta = 0;

        $cbteNro = Number::toInt($input['cbte_nro'] ?? 0);
        if ($cbteNro <= 0) {
            $errors['cbte_nro'] = 'Número de comprobante inválido.';
        }

        $impNeto = Number::toInt($input['imp_neto'] ?? 0);
        $impIva  = Number::toInt($input['imp_iva'] ?? 0);
        $percIva = Number::toInt($input['perc_iva'] ?? 0);
        $percIibb = Number::toInt($input['perc_iibb'] ?? 0);
        $retGanancias = Number::toInt($input['ret_ganancias'] ?? 0);
        $impTotal = Number::toInt($input['imp_total'] ?? 0);

        // Regla recomendada: el total incluye percepciones y retenciones
        // total = neto + iva + perc_iva + perc_iibb - ret_ganancias
        $expectedTotal = $impNeto + $impIva + $percIva + $percIibb - $retGanancias;

        if ($impTotal <= 0) {
            // Si no lo pasaron, calculamos
            $impTotal = max(0, $expectedTotal);
        }

        if ($impNeto < 0) $errors['imp_neto'] = 'Neto inválido.';
        if ($impIva < 0) $errors['imp_iva'] = 'IVA inválido.';
        if ($percIva < 0) $errors['perc_iva'] = 'Percepción IVA inválida.';
        if ($percIibb < 0) $errors['perc_iibb'] = 'Percepción IIBB inválida.';
        if ($retGanancias < 0) $errors['ret_ganancias'] = 'Retención Ganancias inválida.';
        if ($impTotal < 0) $errors['imp_total'] = 'Total inválido.';
        // Reglas contables mínimas

        // Si el usuario escribió un total > 0, debe coincidir con la fórmula (tolerancia 0/1 peso por redondeo).
        if ($impTotal > 0 && abs($impTotal - $expectedTotal) > 1) {
            $errors['imp_total'] = 'El total no coincide con Neto + IVA + percepciones - retenciones.';
        }

        // Comprobantes C (Mono) no llevan IVA discriminado: Factura C (11) / NC C (13)
        if (in_array($cbteTipo, [11,13], true) && $impIva !== 0) {
            $errors['imp_iva'] = 'Para comprobantes C, el IVA debe ser 0.';
        }

        $kind = in_array($cbteTipo, [3,8,13], true) ? 'credit_note' : 'invoice';

        $status = trim((string)($input['status'] ?? 'loaded'));
        if (!in_array($status, ['loaded','validated','void'], true)) {
            $status = 'loaded';
        }
        if ($status === 'void') {
            // No permitimos setear "void" por el form (anulación tiene endpoint)
            $status = 'loaded';
        }

        $notes = trim((string)($input['notes'] ?? ''));

        // Evitar duplicados: mismo proveedor + tipo + pv + nro (aunque esté anulada)
        $dup = $this->repo->findDuplicateDoc($supplierId, $cbteTipo, $ptoVta, $cbteNro, $id && $id > 0 ? $id : null);
        if ($dup) {
            $errors['cbte_nro'] = 'Ya existe una compra con ese comprobante para este proveedor (ID ' . (int)$dup['id'] . ').';
        }

        if ($errors) {
            return ['ok' => false, 'errors' => $errors];
        }

        $data = [
            'supplier_id' => $supplierId,
            'kind' => $kind,
            'cbte_tipo' => $cbteTipo,
            'pto_vta' => $ptoVta,
            'cbte_nro' => $cbteNro,
            'issued_at' => $issuedAt,
            'imp_neto' => $impNeto,
            'imp_iva' => $impIva,
            'perc_iva' => $percIva,
            'perc_iibb' => $percIibb,
            'ret_ganancias' => $retGanancias,
            'imp_total' => $impTotal,
            'status' => $status,
            'notes' => $notes,
        ];

        try {
            if ($id && $id > 0) {
                $this->repo->update($id, $data);
                return ['ok' => true, 'errors' => [], 'id' => $id];
            }

            $newId = $this->repo->insert($data);
            return ['ok' => true, 'errors' => [], 'id' => $newId];
        } catch (\PDOException $e) {
            // Duplicado por UNIQUE (proveedor+tipo+pv+nro)
            if ((string)$e->getCode() === '23000') {
                return ['ok' => false, 'errors' => ['cbte_nro' => 'Ya existe una compra con ese comprobante para este proveedor.']];
            }
            throw $e;
        }
    }
    public function validate(int $id): void
    {
        $this->repo->setStatusValidated($id);
    }


    public function void(int $id): void
    {
        $this->repo->void($id);
    }
}

