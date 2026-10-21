<?php
namespace App\Services\Admin\Audit;

/**
 * Agrupa eventos de productos (especialmente variantes) en "acciones de negocio" para reducir ruido.
 *
 * Importante:
 * - No se pierden eventos: solo se consolidan en UI.
 * - Se aplica SOLO a entity_type=product.
 */
final class ProductBusinessGroupingService
{
    /**
     * Aplica la agrupación sobre items ya listados (ORDER BY id DESC).
     */
    public function groupBusinessProductEvents(array $items): array
    {
        if (empty($items)) return $items;

        // Ventanas de agrupación (UX):
        $creationWindowSeconds = 120;
        $bucketSeconds = 25;
        $out = [];

        $groups = [];
        $order = [];

        $createdIndex = [];
        $createdItemsById = [];
        foreach ($items as $it0) {
            if ((string)($it0['entity_type'] ?? '') !== 'product') continue;
            if ((string)($it0['action'] ?? '') !== 'product_created') continue;
            $uid0 = (int)($it0['user_id'] ?? 0);
            $pid0 = (int)($it0['entity_id'] ?? 0);
            $dt0  = (string)($it0['created_at'] ?? '');
            $ts0  = $dt0 !== '' ? strtotime($dt0) : false;
            if ($uid0 <= 0 || $pid0 <= 0 || $ts0 === false) continue;
            $k0 = $uid0 . '|' . $pid0;
            $createdIndex[$k0][] = [
                'ts' => (int)$ts0,
                'id' => (int)($it0['id'] ?? 0),
                'dt' => $dt0,
            ];
            $cid = (int)($it0['id'] ?? 0);
            if ($cid > 0) {
                $createdItemsById[$cid] = $it0;
            }
        }
        foreach ($createdIndex as $k0 => $list) {
            usort($list, fn($a,$b) => (int)$b['ts'] <=> (int)$a['ts']);
            $createdIndex[$k0] = $list;
        }

        foreach ($items as $it) {
            $etype = (string)($it['entity_type'] ?? '');
            if ($etype !== 'product') {
                $out[] = $it;
                continue;
            }

            $uid = (int)($it['user_id'] ?? 0);
            $pid = (int)($it['entity_id'] ?? 0);
            $createdAt = (string)($it['created_at'] ?? '');
            $ts = $createdAt !== '' ? strtotime($createdAt) : false;
            if ($uid <= 0 || $pid <= 0 || $ts === false) {
                $out[] = $it;
                continue;
            }

            $createdKey = $uid . '|' . $pid;
            $createdMatch = null;
            if (isset($createdIndex[$createdKey])) {
                foreach ($createdIndex[$createdKey] as $c) {
                    $cts = (int)($c['ts'] ?? 0);
                    if ($cts <= 0) continue;
                    if ($ts >= $cts && $ts <= ($cts + $creationWindowSeconds)) {
                        $createdMatch = $c;
                        break;
                    }
                }
            }

            if ($createdMatch) {
                $cid = (int)($createdMatch['id'] ?? 0);
                $gKey = 'created|' . $uid . '|' . $pid . '|' . $cid;
            } else {
                // Bucket por ventana (edición)
                $bucket = (int)floor(((int)$ts) / $bucketSeconds) * $bucketSeconds;
                $gKey = 'edit|' . $uid . '|' . $pid . '|' . $bucket;
            }

            if (!isset($groups[$gKey])) {
                $groups[$gKey] = [
                    'sort_id' => (int)($it['id'] ?? 0),
                    'created_at' => $createdAt,
                    'base' => $it,
                    'has_created' => ((string)($it['action'] ?? '') === 'product_created') || (bool)$createdMatch,
                    'events' => [],
                ];
                $order[] = $gKey;
            }

            // Mantener sort_id (máximo id) para orden descendente estable
            $aid = (int)($it['id'] ?? 0);
            if ($aid > (int)($groups[$gKey]['sort_id'] ?? 0)) {
                $groups[$gKey]['sort_id'] = $aid;
                $groups[$gKey]['base'] = $it;
                $groups[$gKey]['created_at'] = $createdAt;
            }

            $groups[$gKey]['events'][] = $it;
            if ((string)($it['action'] ?? '') === 'product_created') {
                $groups[$gKey]['has_created'] = true;
            }
        }

        // Construir salida: para cada grupo, producimos 1 fila "main" y opcionalmente 1 fila "stock".
        // El orden debe seguir id DESC.
        usort($order, function($a,$b) use ($groups) {
            return (int)($groups[$b]['sort_id'] ?? 0) <=> (int)($groups[$a]['sort_id'] ?? 0);
        });

        foreach ($order as $gKey) {
            $g = $groups[$gKey];
            $events = $g['events'] ?? [];
            if (empty($events)) continue;

            // Separar stock_updated vs resto
            $stockEvents = [];
            $mainEvents = [];
            foreach ($events as $e) {
                if ((string)($e['action'] ?? '') === 'stock_updated') $stockEvents[] = $e;
                else $mainEvents[] = $e;
            }

            // Armado fila principal
            $base = $g['base'];
            $meta = json_decode((string)($base['metadata'] ?? ''), true);
            if (!is_array($meta)) $meta = [];

            $bucket = str_starts_with($gKey, 'created|') ? 'created' : 'main';
            $base['metadata'] = json_encode(array_merge($meta, [
                'biz_group' => [
                    'bucket' => $bucket,
                    'events' => $this->packBizEvents($mainEvents),
                ]
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Elegir acción canónica para la fila principal
            if (!empty($mainEvents)) {
                $base['action'] = $this->pickCanonicalProductAction($mainEvents);
            }

            $out[] = $base;

            // Fila stock (warning) si corresponde
            if (!empty($stockEvents)) {
                // Elegimos como base el stock más reciente
                usort($stockEvents, fn($a,$b) => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
                $stockBase = $stockEvents[0];
                $stockMeta = json_decode((string)($stockBase['metadata'] ?? ''), true);
                if (!is_array($stockMeta)) $stockMeta = [];

                $stockBase['metadata'] = json_encode(array_merge($stockMeta, [
                    'biz_group' => [
                        'bucket' => 'stock',
                        'events' => $this->packBizEvents($stockEvents),
                    ]
                ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $out[] = $stockBase;
            }
        }

        return $out;
    }

    private function pickCanonicalProductAction(array $events): string
    {
        // Prioridad UX
        $prio = [
            'product_created',
            'product_images_bulk_assign','product_images_bulk_delete',
            'product.image_upload','product.image_delete','product.image_cover','product.image_assign',
            'product_updated',
            'stock_updated',
        ];

        $actions = [];
        foreach ($events as $e) {
            $a = (string)($e['action'] ?? '');
            if ($a === '') continue;
            $actions[$a] = ($actions[$a] ?? 0) + 1;
        }
        foreach ($prio as $p) {
            if (isset($actions[$p])) return $p;
        }
        arsort($actions);
        return (string)array_key_first($actions);
    }

    private function packBizEvents(array $events): array
    {
        $out = [];
        foreach ($events as $it) {
            $meta = [];
            $raw = (string)($it['metadata'] ?? '');
            if ($raw !== '') {
                $d = json_decode($raw, true);
                if (is_array($d)) $meta = $d;
            }
            $out[] = [
                'id' => (int)($it['id'] ?? 0),
                'action' => (string)($it['action'] ?? ''),
                'created_at' => (string)($it['created_at'] ?? ''),
                'metadata' => $meta,
            ];
        }
        return $out;
    }
}
