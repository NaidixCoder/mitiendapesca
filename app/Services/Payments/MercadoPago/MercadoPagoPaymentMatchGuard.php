<?php
declare(strict_types=1);

namespace App\Services\Payments\MercadoPago;

/**
 * Barrera antes de transformar un pago "approved" en un pedido pagado.
 * Compara exclusivamente datos confirmados por MP contra el intent persistido.
 */
final class MercadoPagoPaymentMatchGuard
{
    /** @return array{0:bool,1:string} */
    public static function check(array $payment, ?array $intent, string $expectedPaymentId = ''): array
    {
        if (!is_array($intent) || (int)($intent['id'] ?? 0) <= 0) {
            return [false, 'Intent no encontrado para el pago aprobado.'];
        }
        $token = trim((string)($intent['token'] ?? ''));
        $reference = trim((string)($payment['external_reference'] ?? ''));
        if ($token === '' || $reference === '' || !hash_equals($token, $reference)) {
            return [false, 'Referencia de checkout no coincide con el pago.'];
        }
        $providerId = trim((string)($payment['id'] ?? ''));
        if ($providerId === '' || ($expectedPaymentId !== '' && !hash_equals($expectedPaymentId, $providerId))) {
            return [false, 'Identidad del pago no verificable.'];
        }
        $amount = $payment['transaction_amount'] ?? null;
        $expectedAmount = (int)($intent['amount'] ?? 0);
        if (!is_numeric($amount) || !is_finite((float)$amount)
            || $expectedAmount <= 0 || (float)$amount <= 0
            || (int)round(((float)$amount) * 100) !== $expectedAmount * 100) {
            return [false, 'Importe de pago distinto al importe del checkout.'];
        }
        $currency = strtoupper(trim((string)($payment['currency_id'] ?? '')));
        $expectedCurrency = strtoupper(trim((string)($intent['currency'] ?? '')));
        if ($currency === '' || $expectedCurrency === '' || $currency !== $expectedCurrency) {
            return [false, 'Moneda de pago distinta a la del checkout.'];
        }
        if (strtolower(trim((string)($payment['status'] ?? ''))) !== 'approved') {
            return [false, 'El pago no está aprobado.'];
        }
        return [true, ''];
    }
}
