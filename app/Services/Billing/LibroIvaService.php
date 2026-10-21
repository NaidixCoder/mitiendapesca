<?php
namespace App\Services\Billing;

use App\Repositories\Billing\LibroIvaRepository;
use App\Repositories\Billing\ComprobanteFiscalRepository;
use PDO;

final class LibroIvaService
{
    private LibroIvaRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new LibroIvaRepository($db);
    }

    /**
     * @param array{from?:string,to?:string,arca_env?:string,kind?:string} $filters
     * @return array{filters:array<string,string>, rows:array<int,array<string,mixed>>, totals:array<string,int>, totals_by_type:array<int,array<string,mixed>>}
     */
    public function build(array $filters): array
    {
        $from = trim((string)($filters['from'] ?? ''));
        $to   = trim((string)($filters['to'] ?? ''));
        $env  = trim((string)($filters['arca_env'] ?? ''));
        $kind = trim((string)($filters['kind'] ?? ''));

        if (!$from) $from = date('Y-m-01');
        if (!$to)   $to   = date('Y-m-d');

        // Sanitiza (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

        if ($env !== 'homologacion' && $env !== 'produccion') $env = '';
        if ($kind !== 'invoice' && $kind !== 'credit_note') $kind = '';


        // 6.3.B: normaliza importes legacy desde request_json (solo si hay imp_* en 0).
        $cfRepo = new ComprobanteFiscalRepository($this->db);
        $cfRepo->backfillAmountsFromRequestJsonForIssued($from, $to, $env);

        $rows = $this->repo->listIssuedByEmission($from, $to, $env, $kind);
        $byType = $this->repo->totalsByType($from, $to, $env);

        // 6.3.A: En reportes contables, las Notas de Crédito deben restar (signo negativo)
        $rows = $this->applyCreditNoteSignToRows($rows);
        $byType = $this->applyCreditNoteSignToTotalsByType($byType);

        $totTotal = 0; $totNeto = 0; $totIva = 0;
        foreach ($rows as $r) {
            $totTotal += (int)($r['imp_total'] ?? 0);
            $totNeto  += (int)($r['imp_neto'] ?? 0);
            $totIva   += (int)($r['imp_iva'] ?? 0);
        }

        return [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'arca_env' => $env,
                'kind' => $kind,
            ],
            'rows' => $rows,
            'totals' => [
                'total' => $totTotal,
                'neto' => $totNeto,
                'iva' => $totIva,
            ],
            'totals_by_type' => $byType,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function exportRows(string $from, string $to, string $env = '', string $kind = ''): array
    {
        if ($env !== 'homologacion' && $env !== 'produccion') $env = '';
        if ($kind !== 'invoice' && $kind !== 'credit_note') $kind = '';


        // 6.3.B: normaliza importes legacy desde request_json (solo si hay imp_* en 0).
        $cfRepo = new ComprobanteFiscalRepository($this->db);
        $cfRepo->backfillAmountsFromRequestJsonForIssued($from, $to, $env);

        $rows = $this->repo->listIssuedByEmission($from, $to, $env, $kind);
        return $this->applyCreditNoteSignToRows($rows);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applyCreditNoteSignToRows(array $rows): array
    {
        foreach ($rows as &$r) {
            $kind = (string)($r['kind'] ?? '');
            if ($kind === 'credit_note') {
                $r['imp_total'] = -1 * (int)($r['imp_total'] ?? 0);
                $r['imp_neto']  = -1 * (int)($r['imp_neto'] ?? 0);
                $r['imp_iva']   = -1 * (int)($r['imp_iva'] ?? 0);
            } else {
                // Normaliza a int por seguridad
                $r['imp_total'] = (int)($r['imp_total'] ?? 0);
                $r['imp_neto']  = (int)($r['imp_neto'] ?? 0);
                $r['imp_iva']   = (int)($r['imp_iva'] ?? 0);
            }
        }
        unset($r);
        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function applyCreditNoteSignToTotalsByType(array $rows): array
    {
        foreach ($rows as &$r) {
            $kind = (string)($r['kind'] ?? '');
            if ($kind === 'credit_note') {
                $r['total'] = -1 * (int)($r['total'] ?? 0);
                $r['neto']  = -1 * (int)($r['neto'] ?? 0);
                $r['iva']   = -1 * (int)($r['iva'] ?? 0);
            } else {
                $r['total'] = (int)($r['total'] ?? 0);
                $r['neto']  = (int)($r['neto'] ?? 0);
                $r['iva']   = (int)($r['iva'] ?? 0);
            }
        }
        unset($r);
        return $rows;
    }
}
