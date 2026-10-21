<?php
namespace App\Services\Admin\Orders\ViewModels;

final class OrdersShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $order = is_array($input['orderView'] ?? null) ? $input['orderView'] : [];
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $shipment = is_array($input['shipment'] ?? null) ? $input['shipment'] : [];
        $correo = is_array($input['correo'] ?? null) ? $input['correo'] : [];
        $receipts = is_array($input['receipts'] ?? null) ? $input['receipts'] : [];
        $orderFiscalRequest = is_array($input['orderFiscalRequest'] ?? null) ? $input['orderFiscalRequest'] : null;
        $userFiscalProfile = is_array($input['userFiscalProfile'] ?? null) ? $input['userFiscalProfile'] : null;

        $orderId = (int)($order['id'] ?? 0);
        $status = (string)($order['status'] ?? '');
        $paymentMethod = (string)($order['payment_method'] ?? '');
        $isBankTransfer = $this->isBankTransfer($paymentMethod);
        $orderUnpaid = in_array($status, ['pending', 'pending_payment', 'payment_failed', 'payment_expired'], true);

        $orderPrepared = [
            'status_options' => $this->statusOptions($order),
            'status_label' => (string)($order['status_label'] ?? $this->statusLabel($status)),
            'payment_method_label' => $this->paymentMethodLabel($paymentMethod),
            'is_bank_transfer' => $isBankTransfer,
            'order_unpaid' => $orderUnpaid,
            'audit_url' => base_url('/admin/audit?' . http_build_query([
                'tipo' => 'order',
                'q' => '#' . $orderId,
                'range' => '30',
            ])),
        ];
        $fiscalView = $this->fiscalRequestView($order, $orderFiscalRequest, $userFiscalProfile);
        $receiptsView = $this->receiptsView($orderId, $receipts);
        $deliveryView = $this->deliveryView($order);
        $shippingView = $this->shippingView($shipment, $correo, $orderUnpaid);
        $showPage = $this->showPageView(
            $order,
            $items,
            $orderPrepared,
            $fiscalView,
            $receiptsView,
            $deliveryView,
            $shippingView
        );

        return [
            'order_view_prepared' => $orderPrepared,
            'fiscal_request_view' => $fiscalView,
            'receipts_view' => $receiptsView,
            'delivery_view' => $deliveryView,
            'shipping_view' => $shippingView,
            'show_page' => $showPage,
            'show_view' => $this->showView($showPage),
            'show_payload' => [
                'order' => $order,
                'items' => $items,
                'status_options' => is_array($orderPrepared['status_options'] ?? null) ? $orderPrepared['status_options'] : [],
                'status_label' => (string)($orderPrepared['status_label'] ?? ''),
                'payment_method_label' => (string)($orderPrepared['payment_method_label'] ?? '—'),
                'is_bank_transfer' => !empty($orderPrepared['is_bank_transfer']),
                'order_unpaid' => !empty($orderPrepared['order_unpaid']),
                'audit_url' => (string)($orderPrepared['audit_url'] ?? ''),
                'payment' => is_array($order['payment'] ?? null) ? $order['payment'] : null,
                'status' => $status,
                'fiscal_request' => $fiscalView,
                'receipts' => $receiptsView,
                'delivery' => $deliveryView,
                'shipping' => $shippingView,
            ],
        ];
    }

    /**
     * Flattened view payload to keep the page as a thin assembler.
     *
     * @param array<string,mixed> $showPage
     * @return array<string,mixed>
     */
    private function showView(array $showPage): array
    {
        $orderView = is_array($showPage['order'] ?? null) ? $showPage['order'] : [];
        $items = is_array($showPage['items'] ?? null) ? $showPage['items'] : [];
        $opts = is_array($showPage['status_options'] ?? null) ? $showPage['status_options'] : [];

        $fiscalView = is_array($showPage['fiscal_request'] ?? null) ? $showPage['fiscal_request'] : ['has' => false];
        $receiptsView = is_array($showPage['receipts'] ?? null) ? $showPage['receipts'] : ['rows' => [], 'preview' => ['show' => false]];
        $deliveryView = is_array($showPage['delivery'] ?? null) ? $showPage['delivery'] : [];
        $shippingView = is_array($showPage['shipping'] ?? null) ? $showPage['shipping'] : [];

        return [
            'orderView' => $orderView,
            'items' => $items,
            'opts' => $opts,
            'statusLabel' => (string)($showPage['status_label'] ?? ''),
            'paymentMethodLabel' => (string)($showPage['payment_method_label'] ?? '-'),
            'isBankTransfer' => !empty($showPage['is_bank_transfer']),
            'orderUnpaid' => !empty($showPage['order_unpaid']),
            'auditUrl' => (string)($showPage['audit_url'] ?? url('/admin/audit')),
            'payment' => is_array($showPage['payment'] ?? null) ? $showPage['payment'] : null,
            'status' => (string)($showPage['status'] ?? ''),
            'fiscalView' => $fiscalView,
            'receiptRows' => is_array($receiptsView['rows'] ?? null) ? $receiptsView['rows'] : [],
            'receiptPreview' => is_array($receiptsView['preview'] ?? null) ? $receiptsView['preview'] : ['show' => false],
            'customerLabel' => (string)($deliveryView['customer_label'] ?? ''),
            'shipEmail' => (string)($deliveryView['ship_email'] ?? ''),
            'shipPhone' => (string)($deliveryView['ship_phone'] ?? ''),
            'shipNotes' => (string)($deliveryView['ship_notes'] ?? ''),
            'shipAddress' => (string)($deliveryView['ship_address'] ?? ''),
            'hasDeliveryData' => !empty($deliveryView['has_delivery_data']),
            'shipStatus' => (string)($shippingView['ship_status'] ?? 'preparando'),
            'trackingStatus' => (string)($shippingView['tracking_status'] ?? ''),
            'trackingLabel' => (string)($shippingView['tracking_label'] ?? '-'),
            'shipCarrierKey' => (string)($shippingView['carrier_key'] ?? 'correo_argentino'),
            'shipService' => (string)($shippingView['service'] ?? ''),
            'shipTn' => (string)($shippingView['tracking_number'] ?? ''),
            'shipUrl' => (string)($shippingView['tracking_url'] ?? ''),
            'shipExternalId' => (string)($shippingView['external_shipment_id'] ?? ''),
            'shipAt' => (string)($shippingView['shipped_at'] ?? ''),
            'servicePlaceholder' => (string)($shippingView['service_placeholder'] ?? ''),
            'serviceReadonly' => !empty($shippingView['service_readonly']),
            'trackingReadonly' => !empty($shippingView['tracking_readonly']),
            'trackingUrlReadonly' => !empty($shippingView['tracking_url_readonly']),
            'serviceHelp' => (string)($shippingView['service_help'] ?? ''),
            'trackingNumberHelp' => (string)($shippingView['tracking_number_help'] ?? ''),
            'trackingUrlHelp' => (string)($shippingView['tracking_url_help'] ?? ''),
            'correoCanImport' => !empty($shippingView['correo_can_import']),
            'correoBlocked' => (string)($shippingView['correo_blocked_reason'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $order
     * @param array<int,array<string,mixed>> $items
     * @param array<string,mixed> $prepared
     * @param array<string,mixed> $fiscal
     * @param array<string,mixed> $receipts
     * @param array<string,mixed> $delivery
     * @param array<string,mixed> $shipping
     * @return array<string,mixed>
     */
    private function showPageView(
        array $order,
        array $items,
        array $prepared,
        array $fiscal,
        array $receipts,
        array $delivery,
        array $shipping
    ): array {
        $receipts = array_merge(
            ['rows' => [], 'preview' => ['show' => false]],
            $receipts
        );
        $delivery = array_merge(
            [
                'customer_label' => '',
                'ship_email' => '',
                'ship_phone' => '',
                'ship_notes' => '',
                'ship_address' => '',
                'has_delivery_data' => false,
            ],
            $delivery
        );
        $shipping = array_merge(
            [
                'ship_status' => 'preparando',
                'tracking_status' => '',
                'tracking_label' => '—',
                'carrier_key' => 'correo_argentino',
                'service' => '',
                'tracking_number' => '',
                'tracking_url' => '',
                'external_shipment_id' => '',
                'shipped_at' => '',
                'service_placeholder' => '',
                'service_readonly' => false,
                'tracking_readonly' => false,
                'tracking_url_readonly' => false,
                'service_help' => '',
                'tracking_number_help' => '',
                'tracking_url_help' => '',
                'correo_can_import' => false,
                'correo_blocked_reason' => '',
            ],
            $shipping
        );

        return [
            'order' => $order,
            'items' => $items,
            'status_options' => is_array($prepared['status_options'] ?? null) ? $prepared['status_options'] : [],
            'status_label' => (string)($prepared['status_label'] ?? ''),
            'status' => (string)($order['status'] ?? ''),
            'payment_method_label' => (string)($prepared['payment_method_label'] ?? '—'),
            'is_bank_transfer' => !empty($prepared['is_bank_transfer']),
            'order_unpaid' => !empty($prepared['order_unpaid']),
            'audit_url' => (string)($prepared['audit_url'] ?? ''),
            'payment' => is_array($order['payment'] ?? null) ? $order['payment'] : null,
            'fiscal_request' => $fiscal,
            'receipts' => $receipts,
            'delivery' => $delivery,
            'shipping' => $shipping,
        ];
    }

    /**
     * @param array<string,mixed> $order
     * @return array<int,array<string,mixed>>
     */
    private function statusOptions(array $order): array
    {
        $current = (string)($order['status'] ?? '');
        $opts = is_array($order['status_options'] ?? null) ? $order['status_options'] : [];
        $out = [];
        foreach ($opts as $opt) {
            $value = (string)$opt;
            $out[] = [
                'value' => $value,
                'label' => $this->statusLabel($value),
                'selected' => ($value === $current),
            ];
        }
        return $out;
    }

    private function paymentMethodLabel(string $paymentMethod): string
    {
        $paymentMethod = trim($paymentMethod);
        $map = [
            'tarjeta_credito' => 'Tarjeta credito',
            'tarjeta_debito' => 'Tarjeta debito',
            'card_mp' => 'Tarjeta',
            'mercadopago_card' => 'Tarjeta',
            'transfer_bank' => 'Transferencia',
            'transferencia' => 'Transferencia',
            'transfer' => 'Transferencia',
            'bank_transfer' => 'Transferencia',
            'transfer_mp' => 'Transferencia (MP)',
        ];
        return $map[$paymentMethod] ?? ($paymentMethod !== '' ? $paymentMethod : '—');
    }

    private function isBankTransfer(string $paymentMethod): bool
    {
        return in_array($paymentMethod, ['transfer_bank', 'transferencia', 'transfer', 'bank_transfer'], true);
    }

    private function statusLabel(string $status): string
    {
        if (function_exists('order_status_label_admin')) {
            return (string)order_status_label_admin($status);
        }
        return $status;
    }

    /**
     * @param array<string,mixed> $order
     * @param array<string,mixed>|null $request
     * @param array<string,mixed>|null $profile
     * @return array<string,mixed>
     */
    private function fiscalRequestView(array $order, ?array $request, ?array $profile): array
    {
        $has = (is_array($request) && isset($request['status']));
        if (!$has) {
            return ['has' => false];
        }

        $reqStatus = trim((string)($request['status'] ?? ''));
        $padronStatus = trim((string)($profile['validation_status'] ?? ''));

        [$reqLabel, $reqClass] = $this->fiscalBadge($reqStatus);
        [$padronLabel, $padronClass] = $this->padronBadge($padronStatus);

        $canApprove = ($padronStatus === 'valid') && in_array($reqStatus, ['pending', 'invalid'], true);
        $canEmitA = ($reqStatus === 'approved');
        $alreadyDone = ($reqStatus === 'done');

        return [
            'has' => true,
            'uid' => (int)($order['user_id'] ?? 0),
            'request_label' => $reqLabel,
            'request_class' => $reqClass,
            'padron_label' => $padronLabel,
            'padron_class' => $padronClass,
            'cuit' => (string)($profile['cuit'] ?? $request['customer_cuit'] ?? ''),
            'legal_name' => (string)($profile['legal_name'] ?? $request['customer_legal_name'] ?? ''),
            'notes' => (string)($request['notes'] ?? ''),
            'can_approve' => $canApprove,
            'can_emit_a' => $canEmitA,
            'already_done' => $alreadyDone,
            'approve_hint' => $this->fiscalHint($canApprove, $canEmitA, $alreadyDone),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function fiscalBadge(string $status): array
    {
        return match ($status) {
            'approved' => ['Aprobada', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'rejected' => ['Rechazada', 'bg-rose-50 text-rose-700 border-rose-200'],
            'invalid' => ['Inválida', 'bg-rose-50 text-rose-700 border-rose-200'],
            'done' => ['Completada', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            default => ['Pendiente', 'bg-amber-50 text-amber-800 border-amber-200'],
        };
    }

    /**
     * @return array{0:string,1:string}
     */
    private function padronBadge(string $status): array
    {
        return match ($status) {
            'valid' => ['Validado', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
            'invalid' => ['Inválido', 'bg-rose-50 text-rose-700 border-rose-200'],
            'pending' => ['Pendiente', 'bg-amber-50 text-amber-800 border-amber-200'],
            default => ['Sin validar', 'bg-slate-50 text-slate-700 border-slate-200'],
        };
    }

    private function fiscalHint(bool $canApprove, bool $canEmitA, bool $alreadyDone): string
    {
        if ($alreadyDone) {
            return 'Ya se emitió la Factura A (solicitud cerrada).';
        }
        if (!$canApprove) {
            return 'Para aprobar, el padrón debe estar Validado.';
        }
        if (!$canEmitA) {
            return 'Para emitir, primero aprobá la solicitud.';
        }
        return '';
    }

    /**
     * @param array<int,array<string,mixed>> $receipts
     * @return array<string,mixed>
     */
    private function receiptsView(int $orderId, array $receipts): array
    {
        $rows = [];
        foreach ($receipts as $receipt) {
            if (!is_array($receipt)) {
                continue;
            }
            $file = (string)($receipt['file'] ?? '');
            $rows[] = [
                'name' => (string)($receipt['name'] ?? 'comprobante'),
                'created_at' => (string)($receipt['created_at'] ?? ''),
                'size_kb' => (int)round(((int)($receipt['size'] ?? 0)) / 1024),
                'view_url' => url('/admin/orders/comprobante/ver') . '?order_id=' . $orderId . '&file=' . urlencode($file),
                'download_url' => url('/admin/orders/comprobante/descargar') . '?order_id=' . $orderId . '&file=' . urlencode($file),
                'file' => $file,
            ];
        }

        $preview = [
            'show' => false,
        ];
        if (!empty($rows)) {
            $latestFile = (string)($rows[0]['file'] ?? '');
            $ext = strtolower(pathinfo($latestFile, PATHINFO_EXTENSION));
            $isPdf = ($ext === 'pdf');
            $isImg = in_array($ext, ['jpg', 'jpeg', 'png'], true);
            if ($latestFile !== '' && ($isPdf || $isImg)) {
                $openUrl = url('/admin/orders/comprobante/ver') . '?order_id=' . $orderId . '&file=' . urlencode($latestFile);
                $preview = [
                    'show' => true,
                    'is_img' => $isImg,
                    'is_pdf' => $isPdf,
                    'open_url' => $openUrl,
                    'img_url' => $openUrl,
                ];
            }
        }

        return [
            'rows' => $rows,
            'preview' => $preview,
        ];
    }

    /**
     * @param array<string,mixed> $order
     * @return array<string,mixed>
     */
    private function deliveryView(array $order): array
    {
        $shipName = trim((string)($order['shipping_full_name'] ?? ''));
        $shipPhone = trim((string)($order['shipping_phone'] ?? ''));
        $shipNotes = trim((string)($order['shipping_notes'] ?? ''));
        $shipEmail = trim((string)($order['customer_email'] ?? ''));

        $addrParts = [];
        $street = trim((string)($order['shipping_street'] ?? ''));
        $number = trim((string)($order['shipping_number'] ?? ''));
        if ($street !== '') {
            $addrParts[] = $street . ($number !== '' ? ' ' . $number : '');
        }

        $floor = trim((string)($order['shipping_floor'] ?? ''));
        $apt = trim((string)($order['shipping_apartment'] ?? ''));
        $extra = trim(implode(' ', array_filter([
            $floor !== '' ? ('Piso ' . $floor) : '',
            $apt !== '' ? ('Dpto ' . $apt) : '',
        ], static fn($x) => $x !== '')));
        if ($extra !== '') {
            $addrParts[] = $extra;
        }

        $city = trim((string)($order['shipping_city'] ?? ''));
        $state = trim((string)($order['shipping_state'] ?? ''));
        $zip = trim((string)($order['shipping_zip'] ?? ''));
        $cityLine = trim(implode(' ', array_filter([
            $zip !== '' ? ('(' . $zip . ')') : '',
            $city,
            $state,
        ], static fn($x) => $x !== '')));
        if ($cityLine !== '') {
            $addrParts[] = $cityLine;
        }

        $shipAddress = trim(implode(' · ', $addrParts));
        $hasDeliveryData = ($shipName !== '' || $shipPhone !== '' || $shipAddress !== '' || $shipNotes !== '');

        return [
            'customer_label' => (string)($order['customer_label'] ?? ''),
            'ship_email' => $shipEmail,
            'ship_phone' => $shipPhone,
            'ship_notes' => $shipNotes,
            'ship_address' => $shipAddress,
            'has_delivery_data' => $hasDeliveryData,
        ];
    }

    /**
     * @param array<string,mixed> $shipment
     * @param array<string,mixed> $correo
     * @return array<string,mixed>
     */
    private function shippingView(array $shipment, array $correo, bool $orderUnpaid): array
    {
        $shipStatus = (string)($shipment['status'] ?? 'preparando');
        $trackingStatus = (string)($shipment['tracking_status'] ?? '');
        $legacyTrackingStates = ['en_transito', 'en_sucursal', 'entregado', 'fallido'];
        if ($trackingStatus === '' && in_array($shipStatus, $legacyTrackingStates, true)) {
            $trackingStatus = $shipStatus;
            $shipStatus = 'despachado';
        }

        $shipCarrier = (string)($shipment['carrier'] ?? 'Correo Argentino');
        $carrierRaw = mb_strtolower(trim($shipCarrier));
        $carrierKey = 'correo_argentino';
        if ($carrierRaw === 'manual' || str_contains($carrierRaw, 'manual')) {
            $carrierKey = 'manual';
        } elseif ($carrierRaw === 'correo_argentino' || str_contains($carrierRaw, 'correo')) {
            $carrierKey = 'correo_argentino';
        }

        return [
            'ship_status' => $shipStatus,
            'tracking_status' => $trackingStatus,
            'tracking_label' => $this->trackingLabel($trackingStatus),
            'order_unpaid' => $orderUnpaid,
            'carrier_key' => $carrierKey,
            'carrier_label' => $carrierKey === 'manual' ? 'Envío manual' : 'Correo Argentino',
            'service' => (string)($shipment['service'] ?? ''),
            'tracking_number' => (string)($shipment['tracking_number'] ?? ''),
            'tracking_url' => (string)($shipment['tracking_url'] ?? ''),
            'external_shipment_id' => (string)($shipment['external_shipment_id'] ?? ''),
            'shipped_at' => (string)($shipment['shipped_at'] ?? ''),
            'service_placeholder' => $carrierKey === 'correo_argentino'
                ? 'Paq.AR (viene del import)'
                : 'Ej: OCA / Moto / Retiro en local',
            'service_readonly' => ($carrierKey === 'correo_argentino'),
            'tracking_readonly' => ($carrierKey === 'correo_argentino'),
            'tracking_url_readonly' => ($carrierKey === 'correo_argentino'),
            'service_help' => $carrierKey === 'correo_argentino'
                ? 'En Correo Argentino viene del import (solo lectura).'
                : 'En manual podés escribir el método/transportista (opcional).',
            'tracking_number_help' => $carrierKey === 'correo_argentino'
                ? 'En Correo Argentino lo devuelve el import/tracking (solo lectura).'
                : 'En manual cargalo para que el cliente pueda seguir el envío.',
            'tracking_url_help' => $carrierKey === 'correo_argentino'
                ? 'Para Correo Argentino se deriva del número de seguimiento (no se edita).'
                : 'En manual podés pegar el link de tracking del transportista (opcional).',
            'correo_can_import' => (bool)($correo['can_import'] ?? false),
            'correo_blocked_reason' => (string)($correo['blocked_reason'] ?? ''),
        ];
    }

    private function trackingLabel(string $status): string
    {
        return match ($status) {
            'en_transito' => 'En tránsito',
            'en_sucursal' => 'En sucursal',
            'entregado' => 'Entregado',
            'fallido' => 'Fallido',
            default => ($status !== '' ? $status : '—'),
        };
    }
}



