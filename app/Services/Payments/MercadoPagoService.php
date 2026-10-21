<?php
namespace App\Services\Payments;

use RuntimeException;

/**
 * MercadoPagoService (mínimo viable PRO)
 * - Crea una "preference" y retorna init_point/sandbox_init_point.
 * - Consulta un pago por id para validar status.
 *
 * Seguridad: el webhook se valida además con un secret propio (ver controller).
 */
final class MercadoPagoService
{
    private string $accessToken;
    private bool $sandbox;

    public function __construct()
    {
        $this->accessToken = (string)env('MP_ACCESS_TOKEN','');
        $this->sandbox = (bool)env('MP_SANDBOX', false);
        if ($this->accessToken === '') {
            throw new RuntimeException('MP_ACCESS_TOKEN no configurado.');
        }
    }

    /** @param array<int,array<string,mixed>> $items */
    public function createPreference(array $items, string $externalReference, string $notificationUrl, string $backUrl): array
    {
        $payload = [
            'items' => array_values($items),
            'external_reference' => $externalReference,
            'notification_url' => $notificationUrl,
            'back_urls' => [
                'success' => $backUrl,
                'pending' => $backUrl,
                'failure' => $backUrl,
            ],
        ];

        // En local / hosts no publicos Mercado Pago puede rechazar auto_return aunque back_urls exista.
        if ($this->supportsAutoReturn($backUrl)) {
            $payload['auto_return'] = 'approved';
        }

        return $this->requestJson('POST', 'https://api.mercadopago.com/checkout/preferences', $payload);
    }

    /** @param array<string,mixed> $payload */
    public function createPayment(array $payload, string $idempotencyKey = ''): array
    {
        $headers = [];

        if ($idempotencyKey !== '') {
            $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;
        }

        return $this->requestJson('POST', 'https://api.mercadopago.com/v1/payments', $payload, $headers);
    }


    /** @return array<string,mixed> */
    public function getPayment(string $paymentId): array
    {
        return $this->requestJson('GET', 'https://api.mercadopago.com/v1/payments/'.rawurlencode($paymentId), null);
    }

    /** @param array<string,mixed>|null $payload */
    private function requestJson(string $method, string $url, ?array $payload, array $extraHeaders = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) throw new RuntimeException('No se pudo inicializar cURL.');

        $headers = array_merge([
            // Base

            'Authorization: Bearer '.$this->accessToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ], $extraHeaders);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Error cURL Mercado Pago: '.$err);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Respuesta inválida Mercado Pago. HTTP '.$code);
        }

        if ($code >= 400) {
            $msg = (string)($data['message'] ?? ($data['error'] ?? 'Error Mercado Pago'));
            $cause = '';
            if (!empty($data['cause']) && is_array($data['cause'])) {
                $firstCause = $data['cause'][0] ?? null;
                if (is_array($firstCause)) {
                    $causeCode = trim((string)($firstCause['code'] ?? ''));
                    $causeDesc = trim((string)($firstCause['description'] ?? ''));
                    if ($causeCode !== '' || $causeDesc !== '') {
                        $cause = trim($causeCode . ($causeDesc !== '' ? (': ' . $causeDesc) : ''));
                    }
                }
            }
            if ($cause !== '') {
                $msg .= ' | cause: ' . $cause;
            }
            $rawPreview = trim(substr((string)$raw, 0, 1000));
            if ($rawPreview !== '') {
                $msg .= ' | http=' . $code . ' | raw=' . $rawPreview;
            } else {
                $msg .= ' | http=' . $code;
            }
            throw new RuntimeException($msg);
        }

        return $data;
    }

    private function supportsAutoReturn(string $backUrl): bool
    {
        $host = strtolower(trim((string)(parse_url($backUrl, PHP_URL_HOST) ?? '')));
        if ($host === '') {
            return false;
        }

        if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return true;
    }
}
