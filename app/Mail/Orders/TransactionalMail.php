<?php
namespace App\Mail\Orders;

use App\Repositories\Users\UserRepository;
use App\Mail\Core\Mailer;
use PDO;

final class TransactionalMail
{
    public function __construct(private PDO $db) {}

    private function user(int $userId): ?array
    {
        $repo = new UserRepository($this->db);
        return $repo->findById($userId);
    }

    /** Email: confirmación de pedido (pendiente / creado) */
    public function sendOrderCreated(int $userId, int $orderId, array $items, int $subtotal, int $shipping, int $discount, int $total, string $paymentMethod, array $shippingAddress): bool
    {
        $u = $this->user($userId);
        if (!$u || empty($u['email'])) return false;

        $subject = 'ðŸ“¦ Recibimos tu pedido #' . $orderId . ' – Mi Tienda Pesca';
        $content = Mailer::render('order_created', [
            'user' => $u,
            'orderId' => $orderId,
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'paymentMethod' => $paymentMethod,
            'shippingAddress' => $shippingAddress,
            'orderUrl' => base_url('/cuenta/pedidos/ver?id=' . $orderId),
        ]);

        $html = Mailer::wrapLayout('Pedido recibido', $content);
        return Mailer::sendHtml((string)$u['email'], $subject, $html);
    }

    /** Email: pago confirmado */
    public function sendPaymentConfirmed(int $userId, int $orderId, array $items, int $total, string $paymentMethod): bool
    {
        $u = $this->user($userId);
        if (!$u || empty($u['email'])) return false;

        $subject = 'âœ… Pago confirmado – Pedido #' . $orderId . ' – Mi Tienda Pesca';
        $content = Mailer::render('payment_confirmed', [
            'user' => $u,
            'orderId' => $orderId,
            'items' => $items,
            'total' => $total,
            'paymentMethod' => $paymentMethod,
            'orderUrl' => base_url('/cuenta/pedidos/ver?id=' . $orderId),
        ]);

        $html = Mailer::wrapLayout('Pago confirmado', $content);
        return Mailer::sendHtml((string)$u['email'], $subject, $html);
    }

    /** Email: instrucciones de transferencia */
    public function sendTransferInstructions(int $userId, int $orderId, array $items, int $total): bool
    {
        $u = $this->user($userId);
        if (!$u || empty($u['email'])) return false;

        $subject = 'ðŸ’³ Instrucciones de pago – Pedido #' . $orderId . ' – Mi Tienda Pesca';

        $bank = [
            'titular' => (string)env('BANK_TITULAR', ''),
            'banco' => (string)env('BANK_NAME', ''),
            'cbu' => (string)env('BANK_CBU', ''),
            'alias' => (string)env('BANK_ALIAS', ''),
            'cuit' => (string)env('BANK_CUIT', ''),
        ];

        $content = Mailer::render('transfer_instructions', [
            'user' => $u,
            'orderId' => $orderId,
            'items' => $items,
            'total' => $total,
            'bank' => $bank,
            'orderUrl' => base_url('/cuenta/pedidos/ver?id=' . $orderId),
            'ordersUrl' => base_url('/cuenta/pedidos'),
        ]);

        $html = Mailer::wrapLayout('Instrucciones de transferencia', $content);
        return Mailer::sendHtml((string)$u['email'], $subject, $html);
    }

    /** Email: pedido despachado (tracking) */
    public function sendOrderShipped(int $userId, int $orderId, array $tracking): bool
    {
        $u = $this->user($userId);
        if (!$u || empty($u['email'])) return false;

        $carrierRaw = trim((string)($tracking['carrier'] ?? 'Correo Argentino'));
        // Normalizamos carrier (puede venir como key o como label legacy)
        $carrierKey = mb_strtolower($carrierRaw);
        $carrier = match (true) {
            $carrierKey === 'correo_argentino',
            str_contains($carrierKey, 'correo') => 'Correo Argentino',
            $carrierKey === 'manual',
            str_contains($carrierKey, 'manual') => 'Envío manual',
            default => ($carrierRaw !== '' ? $carrierRaw : 'Correo Argentino'),
        };
        $service = trim((string)($tracking['service'] ?? ''));
        $tn      = trim((string)($tracking['tracking_number'] ?? ''));
        $url     = trim((string)($tracking['tracking_url'] ?? ''));

        $subject = 'ðŸšš Tu pedido #' . $orderId . ' fue despachado – Mi Tienda Pesca';

        $content = Mailer::render('order_shipped', [
            'user' => $u,
            'orderId' => $orderId,
            'carrier' => $carrier !== '' ? $carrier : 'Correo Argentino',
            'service' => $service,
            'trackingNumber' => $tn,
            'trackingUrl' => $url,
        ]);

        $html = Mailer::wrapLayout('Pedido despachado', $content);
        return Mailer::sendHtml((string)$u['email'], $subject, $html);
    }

    /** Email: factura emitida (PDF adjunto) */
    public function sendInvoiceIssued(int $userId, int $orderId, string $invoiceLabel, string $invoiceNumber, string $pdfBytes, string $filename): bool
    {
        $u = $this->user($userId);
        if (!$u || empty($u['email'])) return false;

        $subject = 'ðŸ§¾ ' . $invoiceLabel . ' ' . ($invoiceNumber !== '' ? $invoiceNumber . ' – ' : '') . 'Pedido #' . $orderId . ' – Mi Tienda Pesca';

        $content = Mailer::render('invoice_issued', [
            'user' => $u,
            'orderId' => $orderId,
            'invoiceLabel' => $invoiceLabel,
            'invoiceNumber' => $invoiceNumber,
            'orderUrl' => base_url('/cuenta/pedidos/ver?id=' . $orderId),
        ]);

        $html = Mailer::wrapLayout('Factura emitida', $content);

        return Mailer::sendHtmlWithAttachments((string)$u['email'], $subject, $html, [[
            'filename' => $filename,
            'contentType' => 'application/pdf',
            'bytes' => $pdfBytes,
        ]]);
    }


}

