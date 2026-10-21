<?php
namespace App\Services\Payments\MercadoPago;

final class MercadoPagoStatusMapper
{
    /**
     * Normaliza la respuesta cruda de Mercado Pago a un contrato de UI seguro.
     *
     * @param array<string,mixed> $payment
     * @param array<string,mixed> $context
     * @return array<string,mixed>
     */
    public function map(array $payment, array $context = []): array
    {
        $status = $this->normalize((string)($payment['status'] ?? $context['status'] ?? 'unknown'));
        $detail = $this->normalize((string)($payment['status_detail'] ?? $context['status_detail'] ?? ''));
        $paymentId = trim((string)($payment['id'] ?? $context['payment_id'] ?? ''));
        $orderId = max(0, (int)($context['order_id'] ?? 0));

        $checkoutUrl = $this->url('/checkout?step=4');
        $cartUrl = $this->url('/carrito');
        $ordersUrl = $this->url('/cuenta/pedidos');
        $paymentUrl = $paymentId !== '' ? ($ordersUrl . '?pago=' . rawurlencode($paymentId)) : $ordersUrl;
        $orderUrl = $orderId > 0 ? ($this->url('/cuenta/pedidos/ver') . '?id=' . $orderId) : $paymentUrl;

        $base = [
            'status' => $status,
            'status_detail' => $detail,
            'payment_id' => $paymentId,
            'order_id' => $orderId,
            'tone' => 'info',
            'icon' => 'clock',
            'badge' => 'En proceso',
            'title' => 'Pago recibido',
            'lines' => [
                'Estamos procesando tu pago.',
            ],
            'actions' => [
                'primary' => null,
                'secondary' => null,
            ],
            'flags' => [
                'clear_cart' => false,
                'lock_submit' => true,
                'allow_retry' => false,
                'keep_brick_visible' => false,
            ],
        ];

        if ($status === 'approved') {
            return array_replace_recursive($base, [
                'tone' => 'success',
                'icon' => 'shield-solid',
                'badge' => 'Acreditado',
                'title' => 'Pago acreditado',
                'lines' => [
                    'Recibimos y validamos tu pago.',
                    'Ya estamos preparando tu pedido.',
                ],
                'actions' => [
                    'primary' => [
                        'kind' => 'link',
                        'label' => 'Ver pedido',
                        'href' => $orderUrl,
                        'variant' => 'brand',
                    ],
                    'secondary' => [
                        'kind' => 'link',
                        'label' => 'Ir a pedidos',
                        'href' => $ordersUrl,
                        'variant' => 'ghost',
                    ],
                ],
                'flags' => [
                    'clear_cart' => true,
                    'lock_submit' => true,
                    'allow_retry' => false,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        if ($status === 'in_process') {
            $detailLines = $this->processingLines($detail);
            return array_replace_recursive($base, [
                'tone' => 'warning',
                'icon' => 'clock',
                'badge' => 'En revisión',
                'title' => 'Pago en proceso',
                'lines' => $detailLines,
                'actions' => [
                    'primary' => [
                        'kind' => 'link',
                        'label' => 'Ver estado del pago',
                        'href' => $paymentUrl,
                        'variant' => 'brand',
                    ],
                    'secondary' => [
                        'kind' => 'link',
                        'label' => 'Volver al checkout',
                        'href' => $checkoutUrl,
                        'variant' => 'ghost',
                    ],
                ],
                'flags' => [
                    'clear_cart' => false,
                    'lock_submit' => true,
                    'allow_retry' => false,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        if ($status === 'pending') {
            $detailLines = $this->pendingLines($detail);
            return array_replace_recursive($base, [
                'tone' => 'warning',
                'icon' => 'clock',
                'badge' => 'Pendiente',
                'title' => 'Pago pendiente',
                'lines' => $detailLines,
                'actions' => [
                    'primary' => [
                        'kind' => 'link',
                        'label' => 'Ver estado del pago',
                        'href' => $paymentUrl,
                        'variant' => 'brand',
                    ],
                    'secondary' => [
                        'kind' => 'link',
                        'label' => 'Volver al checkout',
                        'href' => $checkoutUrl,
                        'variant' => 'ghost',
                    ],
                ],
                'flags' => [
                    'clear_cart' => false,
                    'lock_submit' => true,
                    'allow_retry' => false,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        if ($status === 'authorized') {
            return array_replace_recursive($base, [
                'tone' => 'warning',
                'icon' => 'credit-card',
                'badge' => 'Autorizado',
                'title' => 'Pago autorizado',
                'lines' => [
                    'La operación fue autorizada.',
                    'Queda pendiente la confirmación final.',
                ],
                'actions' => [
                    'primary' => [
                        'kind' => 'link',
                        'label' => 'Ver estado del pago',
                        'href' => $paymentUrl,
                        'variant' => 'brand',
                    ],
                    'secondary' => [
                        'kind' => 'link',
                        'label' => 'Volver al checkout',
                        'href' => $checkoutUrl,
                        'variant' => 'ghost',
                    ],
                ],
                'flags' => [
                    'clear_cart' => false,
                    'lock_submit' => true,
                    'allow_retry' => false,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        if ($status === 'cancelled') {
            return array_replace_recursive($base, [
                'tone' => 'neutral',
                'icon' => 'warning-triangle',
                'badge' => 'Cancelado',
                'title' => 'Pago cancelado',
                'lines' => [
                    'La operación fue cancelada o expiró.',
                    'Podés volver a intentarlo cuando quieras.',
                ],
                'actions' => [
                    'primary' => [
                        'kind' => 'button',
                        'action' => 'retry',
                        'label' => 'Intentar nuevamente',
                        'variant' => 'brand',
                    ],
                    'secondary' => [
                        'kind' => 'link',
                        'label' => 'Volver al carrito',
                        'href' => $cartUrl,
                        'variant' => 'ghost',
                    ],
                ],
                'flags' => [
                    'clear_cart' => false,
                    'lock_submit' => true,
                    'allow_retry' => true,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        if ($status === 'rejected') {
            $variant = $this->isRecoverableRejection($detail) ? 'recoverable' : 'final';
            $lines = $this->rejectionLines($detail, $variant);
            $secondary = $variant === 'recoverable'
                ? [
                    'kind' => 'link',
                    'label' => 'Elegir otro medio de pago',
                    'href' => $this->url('/checkout?step=3'),
                    'variant' => 'ghost',
                ]
                : [
                    'kind' => 'link',
                    'label' => 'Volver al carrito',
                    'href' => $cartUrl,
                    'variant' => 'ghost',
                ];

            return array_replace_recursive($base, [
                'tone' => 'error',
                'icon' => 'circle-xmark-solid',
                'badge' => $variant === 'recoverable' ? 'Reintentar' : 'Rechazado',
                'title' => $variant === 'recoverable' ? 'No pudimos aprobar el pago' : 'El pago fue rechazado',
                'lines' => $lines,
                'actions' => [
                    'primary' => [
                        'kind' => 'button',
                        'action' => 'retry',
                        'label' => 'Intentar nuevamente',
                        'variant' => 'brand',
                    ],
                    'secondary' => $secondary,
                ],
                'flags' => [
                    'clear_cart' => false,
                    'lock_submit' => true,
                    'allow_retry' => true,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        if (in_array($status, ['refunded', 'charged_back', 'in_mediation'], true)) {
            return array_replace_recursive($base, [
                'tone' => 'info',
                'icon' => 'info-circle',
                'badge' => ucfirst(str_replace('_', ' ', $status)),
                'title' => 'Estado del pago actualizado',
                'lines' => [
                    'El pago cambió de estado después de la confirmación inicial.',
                    'Revisá el detalle de tu pedido para ver el historial completo.',
                ],
                'actions' => [
                    'primary' => [
                        'kind' => 'link',
                        'label' => 'Ver pedido',
                        'href' => $orderUrl,
                        'variant' => 'brand',
                    ],
                    'secondary' => [
                        'kind' => 'link',
                        'label' => 'Ir a pedidos',
                        'href' => $ordersUrl,
                        'variant' => 'ghost',
                    ],
                ],
                'flags' => [
                    'clear_cart' => false,
                    'lock_submit' => true,
                    'allow_retry' => false,
                    'keep_brick_visible' => false,
                ],
            ]);
        }

        return array_replace_recursive($base, [
            'tone' => 'neutral',
            'icon' => 'info-circle',
            'badge' => 'Estado desconocido',
            'title' => 'No pudimos interpretar el resultado',
            'lines' => [
                'Recibimos una respuesta inesperada de Mercado Pago.',
                'No vuelvas a pagar hasta revisar el estado del pedido.',
            ],
            'actions' => [
                'primary' => [
                    'kind' => 'link',
                    'label' => 'Ver estado del pago',
                    'href' => $paymentUrl,
                    'variant' => 'brand',
                ],
                'secondary' => [
                    'kind' => 'link',
                    'label' => 'Ir a pedidos',
                    'href' => $ordersUrl,
                    'variant' => 'ghost',
                ],
            ],
            'flags' => [
                'clear_cart' => false,
                'lock_submit' => true,
                'allow_retry' => false,
                'keep_brick_visible' => false,
            ],
        ]);
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace([' ', '-'], '_', $value);
        return preg_replace('/[^a-z0-9_]+/', '', $value) ?: '';
    }

    private function url(string $path): string
    {
        if (function_exists('url')) {
            return (string)url($path);
        }
        if (function_exists('base_url')) {
            return (string)base_url($path);
        }
        return $path;
    }

    /**
     * @return array<int,string>
     */
    private function processingLines(string $detail): array
    {
        return match ($detail) {
            'pending_review_manual' => [
                'Mercado Pago está revisando tu pago manualmente.',
                'Te avisaremos apenas cambie de estado.',
            ],
            'offline_process' => [
                'El pago sigue un proceso diferido fuera de línea.',
                'No vuelvas a pagarlo mientras se confirma.',
            ],
            'deferred_retry' => [
                'Mercado Pago está reintentando la confirmación.',
                'No vuelvas a enviar el pago.',
            ],
            default => [
                'Mercado Pago está validando tu pago.',
                'No vuelvas a enviarlo mientras esperamos la confirmación.',
            ],
        };
    }

    /**
     * @return array<int,string>
     */
    private function pendingLines(string $detail): array
    {
        return match ($detail) {
            'pending_waiting_transfer' => [
                'Todavía falta completar la transferencia o acreditar el dinero.',
                'Cuando se confirme, actualizaremos tu pedido automáticamente.',
            ],
            'pending_waiting_payment' => [
                'Todavía falta completar o acreditar el pago.',
                'Cuando Mercado Pago lo procese, actualizaremos el pedido.',
            ],
            'pending_challenge' => [
                'Falta completar una validación adicional.',
                'No vuelvas a enviar el pago hasta resolver ese paso.',
            ],
            default => [
                'Todavía falta completar o confirmar el pago.',
                'Cuando Mercado Pago lo acredite, actualizaremos tu pedido automáticamente.',
            ],
        };
    }

    /**
     * @return array<int,string>
     */
    private function rejectionLines(string $detail, string $variant): array
    {
        if ($variant === 'final') {
            return [
                'Mercado Pago no pudo aprobar la operación.',
                'Probá con otro medio de pago o comunicate con el emisor de la tarjeta.',
            ];
        }

        return match ($detail) {
            'cc_rejected_bad_filled_card_number' => [
                'Revisá el número de la tarjeta.',
                'Si estaba bien, probá con otra tarjeta.',
            ],
            'cc_rejected_bad_filled_date' => [
                'Revisá la fecha de vencimiento.',
                'Si estaba bien, probá otra vez o con otra tarjeta.',
            ],
            'cc_rejected_bad_filled_security_code' => [
                'Revisá el código de seguridad.',
                'Si estaba bien, probá con otro medio de pago.',
            ],
            'cc_rejected_call_for_authorize' => [
                'Tu tarjeta requiere autorización adicional.',
                'Contactá con el emisor y luego intentá nuevamente.',
            ],
            'cc_rejected_insufficient_amount' => [
                'La tarjeta no tiene fondos suficientes.',
                'Probá con otra tarjeta o con otro medio de pago.',
            ],
            'cc_rejected_invalid_installments' => [
                'La tarjeta no permite la cantidad de cuotas seleccionada.',
                'Probá con menos cuotas o con otro medio de pago.',
            ],
            'cc_rejected_duplicated_payment' => [
                'Ya existe un pago similar por este importe.',
                'Revisá el estado de tu pedido antes de volver a intentar.',
            ],
            default => [
                'No pudimos aprobar el pago.',
                'Revisá los datos o probá con otro medio de pago.',
            ],
        };
    }

    private function isRecoverableRejection(string $detail): bool
    {
        return in_array($detail, [
            'cc_rejected_bad_filled_card_number',
            'cc_rejected_bad_filled_date',
            'cc_rejected_bad_filled_security_code',
            'cc_rejected_bad_filled_other',
            'cc_rejected_call_for_authorize',
            'cc_rejected_insufficient_amount',
            'cc_rejected_invalid_installments',
            'cc_rejected_card_disabled',
            'cc_rejected_other_reason',
            'cc_rejected_time_out',
            'cc_rejected_duplicated_payment',
        ], true);
    }
}
