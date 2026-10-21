<?php
namespace App\Services\Admin\Audit;

final class AuditViewPresenterService
{
    /**
     * @param array<string,mixed> $view
     * @param array<string,mixed> $server
     * @return array<string,mixed>
     */
    public function presentIndex(array $view, array $server): array
    {
        $tipo = (string)($view['tipo'] ?? 'todos');
        $action = (string)($view['action'] ?? '');
        $range = (string)($view['range'] ?? '7');
        $q = (string)($view['q'] ?? '');
        $actor = (string)($view['actor'] ?? '');
        $entityId = (string)($view['entity_id'] ?? '');
        $from = (string)($view['from'] ?? '');
        $to = (string)($view['to'] ?? '');

        $page = max(1, (int)($view['pageNum'] ?? $view['page'] ?? 1));
        $perPage = (int)($view['perPage'] ?? 25);
        $total = (int)($view['total'] ?? 0);
        $pages = (int)ceil(max(1, $total) / max(1, $perPage));
        $fromRow = $total ? (($page - 1) * $perPage + 1) : 0;
        $toRow = min($total, $page * $perPage);

        $hasAdvancedFilters = (
            trim($actor) !== ''
            || ($tipo !== 'todos')
            || trim($action) !== ''
            || ($range === 'custom')
            || trim($from) !== ''
            || trim($to) !== ''
        );

        $itemsPrepared = [];
        foreach ((array)($view['items'] ?? []) as $it) {
            if (!is_array($it)) {
                continue;
            }

            $meta = [];
            if (!empty($it['metadata'])) {
                $decoded = json_decode((string)$it['metadata'], true);
                if (is_array($decoded)) {
                    $meta = $decoded;
                }
            }

            $biz = (isset($meta['biz_group']) && is_array($meta['biz_group'])) ? $meta['biz_group'] : null;
            $bizBucket = is_array($biz) ? (string)($biz['bucket'] ?? '') : '';
            $aKey = (string)($it['action'] ?? '');

            $tone = audit_event_tone($aKey);
            $aTxt = audit_event_human($aKey);

            if (in_array($bizBucket, ['stock', 'sku', 'variant_name'], true)) {
                $tone = 'warn';
            }

            if ($bizBucket === 'sku') {
                $aTxt = 'SKU actualizado';
            } elseif ($bizBucket === 'variant_name') {
                $aTxt = 'Variante actualizada';
            } elseif ($bizBucket === 'created') {
                $aTxt = 'Producto creado';
            } elseif ($bizBucket === 'stock') {
                $aTxt = 'Stock actualizado';
            }

            $fromStatus = $this->statusLabel($meta['from'] ?? null);
            $toStatus = $this->statusLabel($meta['to'] ?? null);

            $toRaw = strtolower(trim((string)($meta['to'] ?? '')));
            $orderStatusRaw = strtolower(trim((string)($it['order_status'] ?? '')));
            if ($aKey === 'order_status_changed' && (
                in_array($toRaw, ['cancelled', 'canceled', 'cancelado'], true)
                || strtolower(trim($toStatus)) === 'cancelado'
                || in_array($orderStatusRaw, ['cancelled', 'canceled'], true)
                || strtolower(trim($this->statusLabel($it['order_status'] ?? null))) === 'cancelado'
            )) {
                $aTxt = 'Pedido cancelado';
                $tone = 'danger';
            }

            $entity = $this->entityLink($it);
            $chip = $this->entityChip((string)($it['entity_type'] ?? ''));
            $actorName = trim((string)($it['actor_name'] ?? ($it['actor'] ?? '')));
            if ($actorName === '') {
                $actorName = 'Sistema';
            }

            $rowId = (int)($it['id'] ?? 0);
            $currentUri = (string)($server['REQUEST_URI'] ?? base_url('/admin/audit'));
            $backParam = urlencode($currentUri);

            $itemsPrepared[] = [
                'raw' => $it,
                'row_id' => $rowId,
                'meta' => $meta,
                'tone' => $tone,
                'action_text' => $aTxt,
                'action_key' => $aKey,
                'status_from' => $fromStatus,
                'status_to' => $toStatus,
                'entity' => $entity,
                'chip' => $chip,
                'actor' => $actorName,
                'created_at' => (string)($it['created_at'] ?? ''),
                'time_ago' => $this->timeAgo($it),
                'detail_url' => base_url('/admin/audit/show?id=' . $rowId . '&back=' . $backParam),
            ];
        }

        $qs = $_GET;
        $prev = max(1, $page - 1);
        $next = min($pages, $page + 1);
        $qsPrev = $qs;
        $qsPrev['page'] = $prev;
        $qsNext = $qs;
        $qsNext['page'] = $next;
        $prevDisabled = ($page <= 1);
        $nextDisabled = ($page >= $pages);

        return [
            'tipo' => $tipo,
            'action' => $action,
            'range' => $range,
            'q' => $q,
            'actor' => $actor,
            'entity_id' => $entityId,
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => $pages,
            'fromRow' => $fromRow,
            'toRow' => $toRow,
            'items_prepared' => $itemsPrepared,
            'hasAdvancedFilters' => $hasAdvancedFilters,
            'actionGroups' => $this->actionGroups(),
            'rangeOptions' => $this->rangeOptions(),
            'groupRid' => (string)($view['groupRid'] ?? ''),
            'groupError' => (string)($view['groupError'] ?? ''),
            'pager' => [
                'prev_disabled' => $prevDisabled,
                'next_disabled' => $nextDisabled,
                'prev_url' => $prevDisabled ? '#' : base_url('/admin/audit?' . http_build_query($qsPrev)),
                'next_url' => $nextDisabled ? '#' : base_url('/admin/audit?' . http_build_query($qsNext)),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $view
     * @return array<string,mixed>
     */
    public function presentShow(array $view): array
    {
        $item = is_array($view['item'] ?? null) ? $view['item'] : [];
        $backUrl = (string)($view['backUrl'] ?? base_url('/admin/audit'));
        $meta = $this->decodeMeta($item['metadata'] ?? null);

        $aKey = (string)($item['action'] ?? '');
        $biz = (isset($meta['biz_group']) && is_array($meta['biz_group'])) ? $meta['biz_group'] : null;
        $bizBucket = is_array($biz) ? (string)($biz['bucket'] ?? '') : '';
        $bizEventsRaw = (is_array($biz) && isset($biz['events']) && is_array($biz['events'])) ? $biz['events'] : [];

        $tone = audit_event_tone($aKey);
        if (in_array($bizBucket, ['stock', 'sku', 'variant_name'], true)) {
            $tone = 'warn';
        }

        $title = audit_event_human($aKey);
        if ($bizBucket === 'created') {
            $title = 'Producto creado';
        } elseif ($bizBucket === 'stock') {
            $title = 'Stock actualizado';
        } elseif ($bizBucket === 'sku') {
            $title = 'SKU actualizado';
        } elseif ($bizBucket === 'variant_name') {
            $title = 'Variante actualizada';
        }

        $statusFrom = $this->statusLabel($meta['from'] ?? null);
        $statusTo = $this->statusLabel($meta['to'] ?? null);
        $toRaw = strtolower(trim((string)($meta['to'] ?? '')));
        $orderStatusRaw = strtolower(trim((string)($item['order_status'] ?? '')));
        if ($aKey === 'order_status_changed' && (
            in_array($toRaw, ['cancelled', 'canceled', 'cancelado'], true)
            || strtolower(trim((string)$statusTo)) === 'cancelado'
            || in_array($orderStatusRaw, ['cancelled', 'canceled'], true)
            || strtolower(trim($this->statusLabel($item['order_status'] ?? null))) === 'cancelado'
        )) {
            $title = 'Pedido cancelado';
            $tone = 'danger';
        }

        $entity = $this->entityLink($item);
        $actor = trim((string)($item['actor_name'] ?? ''));
        if ($actor === '') {
            $actor = 'Sistema';
        }

        $bizEventsPrepared = [];
        foreach ($bizEventsRaw as $ev) {
            if (!is_array($ev)) {
                continue;
            }
            $m = is_array($ev['metadata'] ?? null) ? $ev['metadata'] : [];
            $evAction = (string)($ev['action'] ?? '');
            $evLabel = audit_event_human($evAction);
            $vid = isset($m['variant_id']) ? (int)$m['variant_id'] : 0;
            $sku = (string)($m['variant_sku'] ?? $m['product_sku'] ?? $m['sku'] ?? '');
            $vname = trim((string)($m['variant_name'] ?? ''));
            $who = $sku !== '' ? $sku : ($vid > 0 ? ('#' . $vid) : '');
            if ($vname !== '') {
                $who = ($who !== '' ? ($who . ' · ') : '') . $vname;
            }

            $changes = $this->extractChanges($m);
            $changesPrepared = [];
            foreach ($changes as $k => $c) {
                if (!is_array($c)) {
                    continue;
                }
                $changesPrepared[] = [
                    'field_label' => $this->fieldLabel((string)$k),
                    'before' => $this->formatVal($c['before'] ?? null),
                    'after' => $this->formatVal($c['after'] ?? null),
                ];
            }

            $stockAfter = null;
            $stockBefore = null;
            if (isset($changes['stock']) && is_array($changes['stock'])) {
                $stockBefore = $this->formatVal($changes['stock']['before'] ?? null);
                $stockAfter = $this->formatVal($changes['stock']['after'] ?? null);
            } elseif (isset($m['stock'])) {
                $stockAfter = $this->formatVal($m['stock']);
            }

            $variantLabel = $vname !== '' ? $vname : ($sku !== '' ? $sku : ($vid > 0 ? ('#' . $vid) : 'SKU'));

            $bizEventsPrepared[] = [
                'action_key' => $evAction,
                'action_label' => $evLabel,
                'changes' => $changesPrepared,
                'who' => $who,
                'sku' => $sku,
                'variant_label' => $variantLabel,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
            ];
        }

        $createdCounts = [];
        $createdStockRows = [];
        $createdOtherRows = [];
        foreach ($bizEventsPrepared as $ev) {
            $key = (string)($ev['action_key'] ?? '');
            $createdCounts[$key] = (int)($createdCounts[$key] ?? 0) + 1;
            if ($key === 'stock_updated') {
                $createdStockRows[] = $ev;
            } else {
                $createdOtherRows[] = $ev;
            }
        }

        $createdCountsHuman = [];
        foreach ($createdCounts as $k => $c) {
            $createdCountsHuman[] = audit_event_human((string)$k) . ': ' . (int)$c;
        }

        $changesUI = [];
        foreach ($this->extractChanges($meta) as $k => $chg) {
            if (!is_array($chg)) {
                continue;
            }
            $changesUI[] = [
                'field_label' => $this->fieldLabel((string)$k),
                'before' => $this->formatVal($chg['before'] ?? null),
                'after' => $this->formatVal($chg['after'] ?? null),
            ];
        }

        $receipt = $this->buildReceiptData($item, $meta, $view);
        $snapshotEnabled = (bool)($view['snapshot_enabled'] ?? false);
        $snapshot = $view['snapshot'] ?? null;
        $snapshotState = 'empty';
        $snapshotRows = [];
        if ($snapshot === null) {
            $snapshotState = 'not_found';
        } elseif (is_array($snapshot) && !empty($snapshot)) {
            $snapshotState = 'ok';
            foreach ($snapshot as $k => $v) {
                $snapshotRows[] = [
                    'field_label' => $this->fieldLabel((string)$k),
                    'value' => $this->formatVal($v),
                ];
            }
        }

        return [
            'item' => $item,
            'back_url' => $backUrl,
            'meta' => $meta,
            'title' => $title,
            'tone' => $tone,
            'status_from' => $statusFrom,
            'status_to' => $statusTo,
            'action_key' => $aKey,
            'entity' => $entity,
            'actor' => $actor,
            'created_at' => (string)($item['created_at'] ?? ''),
            'snapshot_enabled' => $snapshotEnabled,
            'snapshot' => $snapshot,
            'snapshot_state' => $snapshotState,
            'snapshot_rows' => $snapshotRows,
            'biz_bucket' => $bizBucket,
            'biz_events' => $bizEventsPrepared,
            'biz_count' => (int)($biz['count'] ?? count($bizEventsPrepared)),
            'created_counts_human' => $createdCountsHuman,
            'created_stock_rows' => $createdStockRows,
            'created_other_rows' => $createdOtherRows,
            'changes_ui' => $changesUI,
            'variant_info' => [
                'id' => (int)($meta['variant_id'] ?? 0),
                'sku' => (string)($meta['variant_sku'] ?? ''),
                'name' => (string)($meta['variant_name'] ?? ''),
            ],
            'receipt' => $receipt,
            'meta_json_pretty' => json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * @param array<string,mixed> $view
     * @return array<string,mixed>
     */
    public function presentLogins(array $view): array
    {
        $items = is_array($view['items'] ?? null) ? $view['items'] : [];
        $page = max(1, (int)($view['pageNum'] ?? 1));
        $perPage = (int)($view['perPage'] ?? 25);
        $total = (int)($view['total'] ?? 0);
        $pages = (int)ceil(max(1, $total) / max(1, $perPage));
        $event = (string)($view['event'] ?? 'todos');
        $method = (string)($view['method'] ?? 'todos');
        $range = (string)($view['range'] ?? '7');
        $q = (string)($view['q'] ?? '');
        $retention = is_array($view['retention'] ?? null) ? $view['retention'] : [];

        $rowsPrepared = [];
        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $uid = (int)($it['user_id'] ?? 0);
            $email = (string)($it['user_email'] ?? '');
            $name = (string)($it['user_full_name'] ?? '');
            $ip = (string)($it['ip'] ?? '');
            $evt = (string)($it['event'] ?? '');
            $met = (string)($it['method'] ?? '');
            $reason = (string)($it['reason'] ?? '');
            $emailHash = (string)($it['email_hash'] ?? '');
            $uaHash = (string)($it['ua_hash'] ?? '');

            $finger = '';
            if ($emailHash !== '') {
                $finger .= 'e:' . substr($emailHash, 0, 8);
            }
            if ($uaHash !== '') {
                $finger .= ($finger ? ' ' : '') . 'ua:' . substr($uaHash, 0, 8);
            }

            $rowsPrepared[] = [
                'created_at' => (string)($it['created_at'] ?? ''),
                'event_label' => $this->loginEventLabel($evt),
                'method_label' => $this->loginMethodLabel($met),
                'user_id' => $uid,
                'user_name' => $name !== '' ? $name : ('Usuario #' . $uid),
                'user_email' => $email,
                'user_href' => $uid > 0 ? base_url('/admin/users/show?id=' . $uid) : '',
                'ip' => $ip !== '' ? $ip : '-',
                'reason_label' => $this->loginReasonLabel($reason),
                'finger' => $finger !== '' ? $finger : '-',
            ];
        }

        $baseParams = [
            'event' => $event !== 'todos' ? $event : null,
            'method' => $method !== 'todos' ? $method : null,
            'range' => $range !== '7' ? $range : null,
            'q' => $q !== '' ? $q : null,
            'per_page' => $perPage !== 25 ? $perPage : null,
        ];

        $buildUrl = function (array $overrides = []) use ($baseParams): string {
            $params = $baseParams;
            foreach ($overrides as $k => $v) {
                $params[$k] = $v;
            }
            $params = array_filter($params, static fn($v) => $v !== null && $v !== '');
            $qs = $params ? ('?' . http_build_query($params)) : '';
            return base_url('/admin/audit/logins' . $qs);
        };

        return [
            'items' => $items,
            'items_prepared' => $rowsPrepared,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'pages' => $pages,
            'event' => $event,
            'method' => $method,
            'range' => $range,
            'q' => $q,
            'retention' => $retention,
            'ret_success' => (int)($retention['login_success'] ?? 365),
            'ret_failed' => (int)($retention['login_failed'] ?? 180),
            'ret_logout' => (int)($retention['logout'] ?? 90),
            'links' => [
                'first' => $buildUrl(['page' => 1]),
                'prev' => $buildUrl(['page' => max(1, $page - 1)]),
                'next' => $buildUrl(['page' => min($pages, $page + 1)]),
                'last' => $buildUrl(['page' => $pages]),
            ],
            'pager' => [
                'from_row' => (int)min($total, (($page - 1) * $perPage) + 1),
                'to_row' => (int)min($total, $page * $perPage),
                'is_first' => ($page === 1),
                'is_last' => ($page === $pages),
            ],
            'current_uri' => (string)($_SERVER['REQUEST_URI'] ?? '/admin/audit/logins'),
        ];
    }

    /**
     * @param array<string,mixed> $it
     * @return array{label:string,href:?string}
     */
    private function entityLink(array $it): array
    {
        $type = (string)($it['entity_type'] ?? '');
        $id = (int)($it['entity_id'] ?? 0);

        if ($type === 'import_job') {
            $file = '';
            $metaRaw = (string)($it['metadata'] ?? '');
            if ($metaRaw !== '') {
                $mm = json_decode($metaRaw, true);
                if (is_array($mm)) {
                    $file = (string)($mm['file_name'] ?? $mm['file'] ?? '');
                }
            }
            $lbl = $file !== '' ? 'Importacion #' . $id . ' ? ' . $file : 'Importacion #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/imports/show?id=' . $id)];
        }
        if ($type === 'order') {
            return ['label' => 'Pedido #' . $id, 'href' => base_url('/admin/orders/show?id=' . $id)];
        }
        if ($type === 'product') {
            $name = (string)($it['product_name'] ?? '');
            $lbl = $name !== '' ? 'Producto #' . $id . ' ? ' . $name : 'Producto #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/products/edit?id=' . $id)];
        }
        if ($type === 'category') {
            $name = (string)($it['category_name'] ?? '');
            $lbl = $name !== '' ? 'Categoria #' . $id . ' ? ' . $name : 'Categoria #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/categories/edit?id=' . $id)];
        }
        if ($type === 'brand') {
            $name = (string)($it['brand_name'] ?? '');
            $lbl = $name !== '' ? 'Marca #' . $id . ' ? ' . $name : 'Marca #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/brands/edit?id=' . $id)];
        }
        if ($type === 'attribute') {
            $name = (string)($it['attribute_name'] ?? '');
            $lbl = $name !== '' ? 'Atributo #' . $id . ' ? ' . $name : 'Atributo #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/attributes/edit?id=' . $id)];
        }
        if ($type === 'attribute_value') {
            $val = (string)($it['attribute_value'] ?? '');
            $lbl = $val !== '' ? 'Valor #' . $id . ' ? ' . $val : 'Valor #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/attributes')];
        }
        if ($type === 'user') {
            $name = (string)($it['user_name'] ?? '');
            $lbl = $name !== '' ? 'Usuario #' . $id . ' ? ' . $name : 'Usuario #' . $id;
            return ['label' => $lbl, 'href' => base_url('/admin/users/show?id=' . $id)];
        }

        return ['label' => ($type !== '' ? ($type . ' #' . $id) : '-'), 'href' => null];
    }

    /**
     * @param mixed $raw
     * @return array<string,mixed>
     */
    private function decodeMeta(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    /**
     * @param array<string,mixed> $meta
     * @return array<string,array<string,mixed>>
     */
    private function extractChanges(array $meta): array
    {
        $changes = [];
        if (isset($meta['changes']) && is_array($meta['changes'])) {
            foreach ($meta['changes'] as $k => $v) {
                if (is_array($v) && (array_key_exists('before', $v) || array_key_exists('after', $v))) {
                    $changes[(string)$k] = ['before' => $v['before'] ?? null, 'after' => $v['after'] ?? null];
                }
            }
        }
        if (empty($changes) && isset($meta['before'], $meta['after']) && is_array($meta['before']) && is_array($meta['after'])) {
            foreach ($meta['after'] as $k => $after) {
                $before = $meta['before'][$k] ?? null;
                if ($before !== $after) {
                    $changes[(string)$k] = ['before' => $before, 'after' => $after];
                }
            }
        }
        return $changes;
    }

    private function fieldLabel(string $key): string
    {
        return match ($key) {
            'name' => 'Nombre',
            'slug' => 'Slug',
            'parent_id' => 'Categoria padre',
            'sku' => 'SKU',
            'price' => 'Precio',
            'stock' => 'Stock',
            'status' => 'Estado',
            'is_active', 'active' => 'Activo',
            'published' => 'Publicado',
            'payment_method' => 'Metodo de pago',
            'payment_status' => 'Estado del pago',
            'subtotal' => 'Subtotal',
            'discount_total' => 'Descuento',
            'shipping_total' => 'Envio',
            'total', 'grand_total' => 'Total',
            'paid_at' => 'Pagado el',
            'created_at' => 'Creado el',
            'updated_at' => 'Actualizado el',
            'title' => 'Titulo',
            'description' => 'Descripcion',
            default => $key,
        };
    }

    private function formatVal(mixed $v): string
    {
        if ($v === null) {
            return '-';
        }
        if (is_bool($v)) {
            return $v ? 'Si' : 'No';
        }
        if (is_array($v)) {
            return (string)json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        return (string)$v;
    }

    private function loginEventLabel(string $v): string
    {
        return match ($v) {
            'login_success' => 'Login exitoso',
            'login_failed' => 'Login fallido',
            'logout' => 'Logout',
            default => $v,
        };
    }

    private function loginMethodLabel(string $v): string
    {
        return match ($v) {
            'password' => 'Contrasena',
            'google' => 'Google',
            default => $v,
        };
    }

    private function loginReasonLabel(?string $v): string
    {
        $val = (string)($v ?? '');
        if ($val === '') {
            return '-';
        }
        return match ($val) {
            'invalid_credentials' => 'Credenciales invalidas',
            'rate_limited' => 'Bloqueado por intentos',
            'csrf_failed' => 'CSRF invalido',
            'validation' => 'Validacion',
            default => $val,
        };
    }

    /**
     * @param array<string,mixed> $item
     * @param array<string,mixed> $meta
     * @param array<string,mixed> $view
     * @return array<string,mixed>
     */
    private function buildReceiptData(array $item, array $meta, array $view): array
    {
        $receipts = is_array($view['receipts'] ?? null) ? $view['receipts'] : [];
        $receiptFile = (string)($view['receipt_file'] ?? '');
        if ($receiptFile === '' && isset($meta['file'])) {
            $receiptFile = (string)$meta['file'];
        }

        $orderId = (int)($item['entity_id'] ?? 0);
        if ($orderId <= 0) {
            $src = (string)($item['entity_label'] ?? ($item['title'] ?? ''));
            if (preg_match('/#\s*(\d+)/', $src, $m)) {
                $orderId = (int)$m[1];
            }
        }

        $picked = null;
        if ($receiptFile !== '') {
            foreach ($receipts as $r) {
                if (is_array($r) && (string)($r['file'] ?? '') === $receiptFile) {
                    $picked = $r;
                    break;
                }
            }
        }
        if (!is_array($picked)) {
            foreach ($receipts as $r) {
                if (is_array($r)) {
                    $picked = $r;
                    break;
                }
            }
        }

        $pickedFile = '';
        if (is_array($picked)) {
            $pickedFile = (string)($picked['file'] ?? '');
            if ($pickedFile === '') {
                $pickedFile = (string)($picked['filename'] ?? ($picked['path'] ?? ($picked['name'] ?? '')));
            }
        }
        if ($pickedFile === '' && $receiptFile !== '') {
            $pickedFile = $receiptFile;
        }

        $pickedSize = is_array($picked) ? (int)($picked['size'] ?? 0) : 0;
        $pickedAt = is_array($picked) ? (string)($picked['created_at'] ?? '') : '';
        $pickedExt = strtolower(pathinfo($pickedFile, PATHINFO_EXTENSION));
        $isPdf = ($pickedExt === 'pdf');
        $canPreview = ($orderId > 0 && $pickedFile !== '' && ($pickedExt === 'pdf' || in_array($pickedExt, ['jpg', 'jpeg', 'png', 'webp'], true)));
        $previewUrl = $canPreview ? base_url('/admin/orders/comprobante/ver?order_id=' . $orderId . '&file=' . urlencode($pickedFile)) : '';
        $downloadUrl = $canPreview ? base_url('/admin/orders/comprobante/descargar?order_id=' . $orderId . '&file=' . urlencode($pickedFile)) : '';

        return [
            'show' => ((string)($item['action'] ?? '') === 'transfer_receipt_uploaded' && ($pickedFile !== '' || !empty($receipts))),
            'file' => $pickedFile,
            'size_kb' => $pickedSize > 0 ? number_format($pickedSize / 1024, 0, ',', '.') : '',
            'at' => $pickedAt,
            'is_pdf' => $isPdf,
            'preview_url' => $previewUrl,
            'download_url' => $downloadUrl,
        ];
    }

    /**
     * @return array{label:string,tone:string}
     */
    private function entityChip(string $type): array
    {
        return match ($type) {
            'system' => ['label' => 'Sistema', 'tone' => 'neutral'],
            'order' => ['label' => 'Pedido', 'tone' => 'info'],
            'product' => ['label' => 'Producto', 'tone' => 'neutral'],
            'category' => ['label' => 'Categoria', 'tone' => 'neutral'],
            'brand' => ['label' => 'Marca', 'tone' => 'neutral'],
            'user' => ['label' => 'Usuario', 'tone' => 'neutral'],
            'attribute' => ['label' => 'Atributo', 'tone' => 'neutral'],
            'attribute_value' => ['label' => 'Valor', 'tone' => 'neutral'],
            'import_job' => ['label' => 'Importacion', 'tone' => 'neutral'],
            'checkout' => ['label' => 'Checkout', 'tone' => 'neutral'],
            'settings' => ['label' => 'Ajustes', 'tone' => 'neutral'],
            'shipping' => ['label' => 'Envios', 'tone' => 'neutral'],
            default => ['label' => 'Entidad', 'tone' => 'neutral'],
        };
    }

    private function statusLabel(mixed $status): string
    {
        if (!is_string($status) || trim($status) === '') {
            return '';
        }
        $map = [
            'pending_payment' => 'Pendiente de pago',
            'payment_failed' => 'Pago fallido',
            'payment_expired' => 'Pago expirado',
            'paid' => 'Pagado',
            'processing' => 'Preparando pedido',
            'packed' => 'Empaquetado',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
        ];
        if (isset($map[$status])) {
            return $map[$status];
        }
        if (function_exists('order_status_label')) {
            $lbl = order_status_label($status);
            if (is_string($lbl) && $lbl !== '') {
                return $lbl;
            }
        }
        return $status;
    }

    /**
     * @param array<string,mixed> $it
     */
    private function timeAgo(array $it): string
    {
        $diff = null;
        if (isset($it['age_seconds']) && is_numeric($it['age_seconds'])) {
            $diff = (int)$it['age_seconds'];
        } else {
            $dt = (string)($it['created_at'] ?? '');
            $ts = strtotime($dt);
            if ($ts) {
                $diff = time() - $ts;
            }
        }

        if ($diff === null) {
            return '';
        }
        if ($diff < 0) {
            $diff = 0;
        }
        if ($diff < 60) {
            return 'hace ' . max(1, $diff) . ' s';
        }
        if ($diff < 3600) {
            return 'hace ' . max(1, (int)floor($diff / 60)) . ' min';
        }
        if ($diff < 86400) {
            return 'hace ' . max(1, (int)floor($diff / 3600)) . ' h';
        }
        if ($diff < 172800) {
            return 'ayer';
        }
        if ($diff < 604800) {
            return 'hace ' . max(2, (int)floor($diff / 86400)) . ' dias';
        }

        $dt = (string)($it['created_at'] ?? '');
        $ts = strtotime($dt);
        return $ts ? date('d/m/Y', $ts) : '';
    }

    /**
     * @return array<string,array<string,string>>
     */
    private function actionGroups(): array
    {
        return [
            'Pedidos y pagos' => [
                'order_created_transfer' => 'Pedido creado (transferencia)',
                'order_status_changed' => 'Cambio de estado',
                'transfer_receipt_uploaded' => 'Comprobante adjunto',
                'order_mark_paid_mp' => 'Pago marcado (Mercado Pago)',
                'order_mark_paid_transfer' => 'Pago marcado (transferencia)',
                'order_payment_failed' => 'Pago fallido',
                'order_refunded' => 'Reembolso',
                'order_shipment_updated' => 'Envio actualizado',
                'order_shipped_email_sent' => 'Email de envio enviado',
                'order_shipped_email_failed' => 'Email de envio fallido',
            ],
            'Catalogo e inventario' => [
                'product_created' => 'Producto creado',
                'product_updated' => 'Producto actualizado',
                'product.toggle' => 'Estado de producto',
                'stock_updated' => 'Stock actualizado',
                'category.save' => 'Categoria guardada',
                'category.toggle' => 'Estado de categoria',
                'brand.save' => 'Marca guardada',
                'brand.toggle' => 'Estado de marca',
                'attribute_created' => 'Atributo creado',
                'attribute_updated' => 'Atributo actualizado',
                'attribute_value_created' => 'Valor de atributo creado',
                'attribute_value_updated' => 'Valor de atributo actualizado',
                'attribute_value_deleted' => 'Valor de atributo eliminado',
            ],
            'Imagenes' => [
                'product.image_upload' => 'Imagen subida',
                'product.image_delete' => 'Imagen eliminada',
                'product.image_cover' => 'Portada actualizada',
                'product.image_assign' => 'Imagen asignada a variante',
                'product_images_bulk_assign' => 'Imagenes asignadas (masivo)',
                'product_images_bulk_delete' => 'Imagenes eliminadas (masivo)',
            ],
            'Seguridad' => [
                'password_changed' => 'Contrasena cambiada',
                'password_reset_requested' => 'Recupero solicitado',
                'password_reset_token_sent' => 'Link de recupero enviado',
                'password_reset_oauth_notice' => 'Recupero (cuenta Google)',
                'password_reset_completed' => 'Contrasena restablecida',
                'logins_cleanup_executed' => 'Limpieza de logins (ejecutada)',
                'user.role_change' => 'Rol actualizado',
            ],
            'Importaciones' => [
                'import_finished' => 'Importacion aplicada',
                'import_reverted' => 'Importacion revertida',
                'import_reverted_warn' => 'Importacion revertida (con advertencias)',
                'import_revert_blocked' => 'Reversion de importacion bloqueada',
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    private function rangeOptions(): array
    {
        return [
            'today' => 'Hoy',
            '7' => 'Ultimos 7 dias',
            '30' => 'Ultimos 30 dias',
            'all' => 'Todo',
            'custom' => 'Personalizado',
        ];
    }
}
