<?php
namespace App\Services\Account;

use App\Repositories\Account\OrderRepository;

class OrderService
{
    public function __construct(private OrderRepository $repo) {}

    /** @return array<int, array<string,mixed>> */
    public function listForUserView(int $userId, int $limit = 50): array
    {
        $rows = $this->repo->listByUser($userId, $limit);
        return array_map(fn(array $o) => $this->mapOrderRow($o), $rows);
    }

    /** @return array<string,mixed>|null */
    public function latestForUserView(int $userId): ?array
    {
        $row = $this->repo->latestByUser($userId);
        return $row ? $this->mapOrderRow($row) : null;
    }

    /** @return array<string,mixed> */
    public function orderView(array $order): array
    {
        return $this->mapOrderRow($order);
    }

    public function detailView(array $order): array
    {
        $v = $this->mapOrderRow($order);

        // Totales (en pesos enteros)
        $subtotal = (int)($order['subtotal'] ?? 0);
        $shipping = (int)($order['shipping'] ?? 0);
        $discount = (int)($order['discount'] ?? 0);
        $total    = (int)($order['total'] ?? 0);

        $v['created_at'] = (string)($order['created_at'] ?? '');
        $v['subtotal_fmt'] = $this->money($subtotal);
        $v['shipping_fmt'] = $this->money($shipping);
        $v['discount_fmt'] = $this->money($discount);
        $v['total_fmt']    = $this->money($total);

        $pm = (string)($order['payment_method'] ?? '');
        $v['payment_method'] = $pm;
        $v['payment_method_label'] = match ($pm) {
            'tarjeta_credito' => 'Tarjeta credito',
            'tarjeta_debito' => 'Tarjeta debito',
            'mercadopago_card', 'card', 'tarjeta' => 'Tarjeta',
            'mercadopago_pro', 'checkout_pro' => 'Mercado Pago (Checkout)',
            'transferencia', 'transfer' => 'Transferencia bancaria',
            '' => '—',
            default => ucfirst(str_replace('_', ' ', $pm)),
        };

        // Direccion snapshot
        $full = trim((string)($order['shipping_full_name'] ?? ''));
        $street = trim((string)($order['shipping_street'] ?? ''));
        $number = trim((string)($order['shipping_number'] ?? ''));
        $floor = trim((string)($order['shipping_floor'] ?? ''));
        $apt = trim((string)($order['shipping_apartment'] ?? ''));
        $city = trim((string)($order['shipping_city'] ?? ''));
        $state = trim((string)($order['shipping_state'] ?? ''));
        $zip = trim((string)($order['shipping_zip'] ?? ''));
        $phone = trim((string)($order['shipping_phone'] ?? ''));

        $line1 = trim($street . ' ' . $number);
        $line2Parts = [];
        if ($floor !== '') {
            $line2Parts[] = 'Piso ' . $floor;
        }
        if ($apt !== '') {
            $line2Parts[] = 'Dpto ' . $apt;
        }
        $line2 = implode(' · ', $line2Parts);

        $loc = trim(implode(', ', array_filter([$city, $state, $zip])));

        $v['shipping_name'] = $full !== '' ? $full : '—';
        $v['shipping_phone'] = $phone !== '' ? $phone : '—';
        $v['shipping_line1'] = $line1 !== '' ? $line1 : '—';
        $v['shipping_line2'] = $line2;
        $v['shipping_location'] = $loc !== '' ? $loc : '—';
        $v['shipping_city'] = $city !== '' ? $city : '—';
        $v['shipping_state'] = $state !== '' ? $state : '—';
        $v['shipping_zip'] = $zip;
        $v['shipping_address'] = trim($line1 . ($line2 !== '' ? ' · ' . $line2 : ''));
        $v['shipping_street'] = $street;
        $v['shipping_number'] = $number;
        $v['shipping_floor'] = $floor;
        $v['shipping_apartment'] = $apt;
        $v['shipping_notes'] = trim((string)($order['shipping_notes'] ?? ''));

        return $v;
    }

    /** @return array<int, array<string,mixed>> */
    public function itemsView(int $orderId): array
    {
        $items = $this->repo->itemsForOrder($orderId);
        return array_map(function (array $it) {
            $qty = (int)($it['qty'] ?? 0);
            $price = (int)($it['price'] ?? 0);
            $sub = $qty * $price;

            $baseName = (string)($it['name'] ?? '—');
            $variantTitle = trim((string)($it['variant_title'] ?? ''));
            $displayName = $baseName;
            if ($variantTitle !== '') {
                $displayName = $baseName . ' — ' . $variantTitle;
            }

            $productSlug = trim((string)($it['product_slug'] ?? ''));
            $variantImagePath = trim((string)($it['variant_image_path'] ?? ''));
            $productImagePath = trim((string)($it['product_image_path'] ?? ''));
            $imagePath = $variantImagePath !== '' ? $variantImagePath : $productImagePath;

            return [
                'sku' => (string)($it['sku'] ?? '—'),
                'name' => $displayName,
                'model_label' => $variantTitle,
                'qty' => $qty,
                'price_fmt' => $this->money($price),
                'sub_fmt' => $this->money($sub),
                'product_slug' => $productSlug,
                'image_url' => $imagePath,
                'product_href' => ($productSlug !== '')
                    ? (base_url('/producto') . '?slug=' . rawurlencode($productSlug))
                    : '#',
            ];
        }, $items);
    }

    /** @return array<string,mixed> */
    private function mapOrderRow(array $o): array
    {
        $id = (int)($o['id'] ?? 0);
        $status = (string)($o['status'] ?? 'pending_payment');
        $label = function_exists('order_status_label_user') ? order_status_label_user($status) : $status;
        $pill = function_exists('order_status_pill_class_user') ? order_status_pill_class_user($status) : 'pill';

        return [
            'id' => $id,
            'status' => $status,
            'status_label' => $label,
            'badge_class' => $pill,
            'items_count' => (int)($o['items_count'] ?? 0),
            'created_short' => substr((string)($o['created_at'] ?? ''), 0, 16),
            'total_fmt' => $this->money((int)($o['total'] ?? 0)),
            'href' => url('/cuenta/pedidos/ver') . '?id=' . $id,
        ];
    }

    private function money(int $pesos): string
    {
        return '$ ' . number_format((float)$pesos, 0, ',', '.');
    }
}
