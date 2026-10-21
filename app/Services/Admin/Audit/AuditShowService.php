<?php
namespace App\Services\Admin\Audit;

use PDO;
use App\Repositories\Admin\AuditSearchRepository;
use App\Services\Receipts\TransferReceiptService;

/**
 * Caso de uso /admin/audit/show.
 */
final class AuditShowService
{
    private function decodeMetadata(mixed $raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return [];
    }

    public function __construct(
        private AuditSearchRepository $repo,
        private AuditEnrichmentService $enricher,
        private AuditShowHydratorService $hydrator,
        private AuditSnapshotService $snapshot
    ) {}

    /**
     * @return array View data para render_page('admin/audit/show')
     */
    public function buildViewData(PDO $db, int $id, string $backUrl): array
    {
        $item = $this->repo->findEventById($id);
        if (!$item) {
            return ['__NOT_FOUND__' => true];
        }

        // Enriquecer (SKU variante, etc.)
        $items = [$item];
        $this->enricher->enrichItems($db, $items);
        $item = $items[0] ?? $item;

        // Agrupación coherente con index
        $item = $this->hydrator->hydrateBusinessGroupingForShow($db, $item, $id);

        $view = [
            'item' => $item,
            'backUrl' => $backUrl,
        ];

        // Snapshot UX (no rompe)
        $etype = (string)($item['entity_type'] ?? '');
        $eid = (int)($item['entity_id'] ?? 0);
        if ($eid > 0 && in_array($etype, ['brand','category','order','product'], true)) {
            try {
                $view['snapshot'] = $this->snapshot->getEntitySnapshot($db, $etype, $eid);
                $view['snapshot_enabled'] = true;
            } catch (\Throwable $e) {
                $view['snapshot'] = [];
                $view['snapshot_enabled'] = true;
            }
        }

        // Preview de comprobante en detalle de auditoría
        // Importante: metadata suele venir como JSON string desde DB.
        // Fix robusto: aunque entity_type no sea 'order', el entity_id sigue siendo el pedido.
        if ((string)($item['action'] ?? '') === 'transfer_receipt_uploaded') {
            $meta = $this->decodeMetadata($item['metadata'] ?? null);

            // Resolver order_id con fallbacks (entity_id -> metadata -> label)
            $orderId = $eid > 0 ? $eid : 0;
            if ($orderId <= 0) {
                $orderId = (int)($meta['order_id'] ?? ($meta['orderId'] ?? 0));
            }
            if ($orderId <= 0) {
                $label = (string)($item['entity_label'] ?? ($item['title'] ?? ''));
                if (preg_match('/#\s*(\d+)/', $label, $m)) $orderId = (int)$m[1];
            }

            if ($orderId > 0) {
                // receipt_file con múltiples keys posibles (se resuelve SIEMPRE, incluso si falla listForOrder)
                $receiptFile = (string)($meta['file'] ?? '');
                if ($receiptFile === '') $receiptFile = (string)($meta['path'] ?? '');
                if ($receiptFile === '') $receiptFile = (string)($meta['filename'] ?? '');
                if ($receiptFile === '') $receiptFile = (string)($meta['name'] ?? '');
                if ($receiptFile === '') $receiptFile = (string)($meta['receipt_file'] ?? '');
                if ($receiptFile === '' && isset($meta['receipt']) && is_array($meta['receipt'])) {
                    $receiptFile = (string)($meta['receipt']['file'] ?? ($meta['receipt']['path'] ?? ($meta['receipt']['filename'] ?? '')));
                }
                $view['receipt_file'] = $receiptFile;

                // Intentar listar desde storage; si falla o viene vacío, igual mostramos fallback desde metadata
                $view['receipts'] = [];
                try {
                    $svc = new TransferReceiptService();
                    $view['receipts'] = $svc->listForOrder($orderId);
                } catch (\Throwable $e) {
                    // Mantener vacío; no bloquear el fallback.
                    $view['receipts'] = [];
                }

                // Fallback: si no hay receipts listados, pero metadata trae filename, generamos un receipt mínimo
                if (empty($view['receipts']) && $view['receipt_file'] !== '') {
                    $view['receipts'] = [[
                        'file' => $view['receipt_file'],
                        'name' => basename($view['receipt_file']),
                        'size' => (int)($meta['size'] ?? ($meta['bytes'] ?? 0)),
                        'created_at' => (string)($item['created_at'] ?? ''),
                    ]];
                }
            }
        }

        return $view;
    }
}
