<?php
namespace App\Services\Admin\Audit;

use PDO;
use App\Repositories\Admin\AuditSearchRepository;

/**
 * Reconstruye la fila agrupada (biz_group) para /admin/audit/show cuando aplica.
 *
 * Se mantiene consistente con la agrupación aplicada en el listado.
 */
final class AuditShowHydratorService
{
    public function __construct(
        private AuditSearchRepository $repo,
        private AuditEnrichmentService $enricher,
        private ProductBusinessGroupingService $grouping
    ) {}

    public function hydrateBusinessGroupingForShow(PDO $db, array $item, int $id): array
    {
        if (($item['entity_type'] ?? '') !== 'product') return $item;

        $pid = (int)($item['entity_id'] ?? 0);
        if ($pid <= 0) return $item;

        $uid = (int)($item['user_id'] ?? 0);
        $dt = (string)($item['created_at'] ?? '');
        $ts = $dt !== '' ? strtotime($dt) : false;
        if ($ts === false) return $item;

        $from = date('Y-m-d H:i:s', $ts - 180);
        $to   = date('Y-m-d H:i:s', $ts + 180);

        $windowItems = $this->repo->listProductEventsWindow($pid, $from, $to, 250);
        if (!$windowItems) return $item;

        foreach ($windowItems as &$wi) {
            $wi['user_id'] = $uid;
        }
        unset($wi);

        $this->enricher->enrichItems($db, $windowItems);

        $grouped = $this->grouping->groupBusinessProductEvents($windowItems);

        foreach ($grouped as $g) {
            if ((int)($g['id'] ?? 0) !== $id) continue;
            $meta = json_decode((string)($g['metadata'] ?? ''), true);
            if (is_array($meta) && isset($meta['biz_group'])) return $g;
        }

        $want = (string)($item['action'] ?? '');
        $foundCreated = null;
        $foundStock = null;
        $foundMain = null;

        foreach ($grouped as $g) {
            if (($g['entity_type'] ?? '') !== 'product') continue;
            if ((int)($g['entity_id'] ?? 0) !== $pid) continue;

            $meta = json_decode((string)($g['metadata'] ?? ''), true);
            if (!is_array($meta) || !isset($meta['biz_group']) || !is_array($meta['biz_group'])) continue;
            $bucket = (string)($meta['biz_group']['bucket'] ?? '');

            if ($bucket === 'created') $foundCreated = $g;
            elseif ($bucket === 'stock') $foundStock = $g;
            elseif ($bucket === 'main') $foundMain = $g;
        }

        if ($foundCreated) return $foundCreated;
        if ($want === 'stock_updated' && $foundStock) return $foundStock;
        if ($foundMain) return $foundMain;

        return $item;
    }
}
