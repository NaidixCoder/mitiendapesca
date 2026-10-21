<?php
namespace App\Services\Pricing;

use App\Repositories\Settings\AppSettingsRepository;

final class CommercialPriceService
{
    public function __construct(private AppSettingsRepository $settings)
    {
    }

    public function isDebitMethod(string $method): bool
    {
        $method = strtolower(trim($method));
        return in_array($method, ['tarjeta_debito'], true);
    }

    public function isCreditMethod(string $method): bool
    {
        $method = strtolower(trim($method));
        return in_array($method, ['tarjeta_credito', 'tarjeta', 'mercadopago'], true);
    }

    public function isFinancedMethod(string $method): bool
    {
        return $this->isCreditMethod($method);
    }

    public function isInstallmentsEnabled(): bool
    {
        return ((string)($this->settings->get('pricing_installments_enabled', '1') ?? '1')) === '1';
    }

    public function installmentsCount(): int
    {
        $count = (int)($this->settings->get('pricing_installments_count', '3') ?? '3');
        if ($count < 2) $count = 3;
        if ($count > 24) $count = 24;
        return $count;
    }

    public function interestRatePercent(): float
    {
        $raw = trim((string)($this->settings->get('pricing_interest_rate_percent', '0') ?? '0'));
        $raw = str_replace(',', '.', $raw);
        $pct = (float)$raw;
        return $pct < 0 ? 0.0 : $pct;
    }

    public function debitDiscountPercent(): float
    {
        $raw = trim((string)($this->settings->get('pricing_debit_discount_percent', '0') ?? '0'));
        $raw = str_replace(',', '.', $raw);
        $pct = (float)$raw;
        if ($pct < 0) $pct = 0.0;
        if ($pct > 100) $pct = 100.0;
        return $pct;
    }

    public function resolveTransferPrice(?int $transferPrice): int
    {
        return max(0, (int)($transferPrice ?? 0));
    }

    public function resolveFinancedPrice(?int $transferPrice, ?int $financedPrice): int
    {
        $transfer = $this->resolveTransferPrice($transferPrice);
        $financed = max(0, (int)($financedPrice ?? 0));
        return $financed > 0 ? $financed : $transfer;
    }

    public function resolveDebitPrice(?int $transferPrice, ?int $financedPrice): int
    {
        $base = $this->resolveFinancedPrice($transferPrice, $financedPrice);
        $pct = $this->debitDiscountPercent();
        if ($base <= 0 || $pct <= 0) {
            return $base;
        }

        return max(0, (int)round($base * (1 - ($pct / 100))));
    }

    public function resolveEffectivePrice(?int $transferPrice, ?int $financedPrice, string $method): int
    {
        if ($this->isDebitMethod($method)) {
            return $this->resolveDebitPrice($transferPrice, $financedPrice);
        }
        if ($this->isFinancedMethod($method)) {
            return $this->resolveFinancedPrice($transferPrice, $financedPrice);
        }
        return $this->resolveTransferPrice($transferPrice);
    }

    public function discountPercent(?int $transferPrice, ?int $financedPrice): int
    {
        $transfer = $this->resolveTransferPrice($transferPrice);
        $financed = $this->resolveFinancedPrice($transferPrice, $financedPrice);
        if ($transfer <= 0 || $financed <= $transfer) {
            return 0;
        }
        return (int)round((1 - ($transfer / $financed)) * 100);
    }

    public function installmentAmount(?int $financedPrice): int
    {
        $price = max(0, (int)($financedPrice ?? 0));
        $count = $this->installmentsCount();
        if ($price <= 0 || $count < 2 || !$this->isInstallmentsEnabled()) {
            return 0;
        }
        return (int)round($price / $count);
    }

    public function installmentLabel(?int $financedPrice): string
    {
        $amount = $this->installmentAmount($financedPrice);
        if ($amount <= 0) {
            return '';
        }

        $count = $this->installmentsCount();
        return $count . ' cuotas sin interes de ' . money($amount);
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<string,mixed>
     */
    public function buildCheckoutCart(array $cart, string $method): array
    {
        $items = [];
        $subtotal = 0;

        foreach ((array)($cart['items'] ?? []) as $item) {
            if (!is_array($item)) continue;
            $qty = max(0, (int)($item['qty'] ?? 0));
            if ($qty <= 0) continue;

            $unit = $this->resolveEffectivePrice(
                isset($item['price']) ? (int)$item['price'] : 0,
                isset($item['list_price']) ? (int)$item['list_price'] : 0,
                $method
            );
            $line = $unit * $qty;
            $item['unit_price_effective'] = $unit;
            $item['line_total_effective'] = $line;
            $items[] = $item;
            $subtotal += $line;
        }

        $shipping = max(0, (int)($cart['shipping'] ?? 0));
        $discount = max(0, (int)($cart['discount'] ?? 0));

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'discount' => $discount,
            'total' => max(0, $subtotal + $shipping - $discount),
        ];
    }
}
