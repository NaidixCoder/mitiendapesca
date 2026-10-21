<?php
declare(strict_types=1);

namespace App\Services\Checkout;

use App\Repositories\Settings\AppSettingsRepository;

final class CheckoutFlowService
{
    public function __construct(private \PDO $db)
    {
    }

    public function resolveCurrentStep(int $step): int
    {
        if ($step < 1 || $step > 4) {
            return 1;
        }

        return $step;
    }

    public function resolveFreeMinAmount(): int
    {
        try {
            $settingsRepo = new AppSettingsRepository($this->db);
            $freeMinAmount = (int)($settingsRepo->get('shipping_free_min_amount', '0') ?? 0);
            return max(0, $freeMinAmount);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function invalidateSelectionIfCartChanged(int $currentStep, array $cart, string $cartSig, int $freeMinAmount): ?string
    {
        if (!isset($_SESSION['checkout_shipping']) || !is_array($_SESSION['checkout_shipping'])) {
            return null;
        }

        $snapSig = (string)($_SESSION['checkout_shipping']['cart_sig'] ?? '');
        $snapMode = strtolower(trim((string)($_SESSION['checkout_shipping']['mode'] ?? '')));
        $sigMissing = ($snapSig === '' && $snapMode !== 'free');
        $sigMismatch = ($snapSig !== '' && $snapSig !== $cartSig);

        if (!$sigMissing && !$sigMismatch) {
            return null;
        }

        unset($_SESSION['checkout_shipping'], $_SESSION['checkout_shipping_calc'], $_SESSION['checkout_payment']);

        if ($currentStep < 3) {
            return null;
        }

        $subtotalNow = (int)($cart['subtotal'] ?? 0);
        $isFreeByThreshold = ($freeMinAmount > 0 && $subtotalNow >= $freeMinAmount);
        if (!$isFreeByThreshold) {
            $lastWarnedSig = (string)($_SESSION['checkout_cart_sig_warned'] ?? '');
            if ($lastWarnedSig !== $cartSig) {
                flash('warn', 'Tu carrito cambió. Se actualizó el envío.');
                $_SESSION['checkout_cart_sig_warned'] = $cartSig;
            }
        }

        return '/checkout?step=2';
    }

    public function ensurePaymentSelectionForStep(int $currentStep): ?string
    {
        if ($currentStep < 4) {
            return null;
        }

        $pm = (string)($_SESSION['checkout_payment']['method'] ?? '');
        if ($pm !== '' && in_array($pm, ['tarjeta_credito', 'tarjeta_debito', 'transferencia'], true)) {
            return null;
        }

        flash('warn', 'Selecciona un metodo de pago para continuar.');
        return '/checkout?step=3';
    }

    public function validateShippingSnapshot(mixed $snapshot): bool
    {
        if (!is_array($snapshot)) return false;
        if (!array_key_exists('cost', $snapshot)) return false;

        $cost = (int)($snapshot['cost'] ?? -1);
        if ($cost < 0) return false;

        $mode = strtolower(trim((string)($snapshot['mode'] ?? '')));
        if ($mode === '') return false;

        if ($mode === 'free') {
            return $cost === 0;
        }

        if (!in_array($mode, ['d', 's', 'l'], true)) return false;

        $serviceCode = trim((string)($snapshot['service_code'] ?? ''));
        if ($serviceCode === '') return false;

        if ($mode === 's') {
            $agency = trim((string)($snapshot['agency_id'] ?? ''));
            if ($agency === '') return false;
        }

        return true;
    }

    /**
     * @return array{0:bool,1:?array<string,mixed>}
     */
    public function normalizeShippingSnapshot(): array
    {
        $shipSnap = $_SESSION['checkout_shipping'] ?? null;
        $hasShipSnap = $this->validateShippingSnapshot($shipSnap);

        if (!$hasShipSnap && isset($_SESSION['checkout_shipping'])) {
            unset($_SESSION['checkout_shipping'], $_SESSION['checkout_shipping_calc']);
            $shipSnap = null;
        }

        return [$hasShipSnap, is_array($shipSnap) ? $shipSnap : null];
    }

    public function applyShippingToCart(array &$cart, ?array $shipSnap): void
    {
        if (!$this->validateShippingSnapshot($shipSnap)) {
            return;
        }

        $cart['shipping'] = (int)($shipSnap['cost'] ?? 0);
        $cart['total'] = max(
            0,
            (int)($cart['subtotal'] ?? 0) + (int)($cart['shipping'] ?? 0) - (int)($cart['discount'] ?? 0)
        );
    }

    public function enforceStep3Shipping(int $currentStep, array &$cart, bool $hasShipSnap, int $freeMinAmount, string $cartSig): ?string
    {
        if ($currentStep < 3 || $hasShipSnap) {
            return null;
        }

        $subtotal = (int)($cart['subtotal'] ?? 0);
        if ($freeMinAmount > 0 && $subtotal >= $freeMinAmount) {
            $_SESSION['checkout_shipping'] = [
                'mode' => 'free',
                'service_code' => 'FREE',
                'label' => 'Envio gratis',
                'cost' => 0,
                'cart_sig' => $cartSig,
                'created_at' => time(),
            ];
            $cart['shipping'] = 0;
            $cart['total'] = max(0, $subtotal - (int)($cart['discount'] ?? 0));
            return null;
        }

        flash('error', 'Para continuar, recalcula y confirma el envio.');
        return '/checkout?step=2';
    }
}
