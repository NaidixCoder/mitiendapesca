<?php
namespace App\Services\Pricing;

use App\Repositories\Settings\AppSettingsRepository;

/**
 * PriceService (FIX556 - Etapa 2)
 * Dueño único del cálculo de precios según rol (público / mayoristas),
 * con overrides y fallback a % global + redondeo.
 *
 * Nota: usa enteros (mismo formato que products.price / product_variants.price).
 */
class PriceService {

  private AppSettingsRepository $settings;

  public function __construct(AppSettingsRepository $settings) {
    $this->settings = $settings;
  }

  public function isWholesaleEnabled(): bool {
    return ((string)($this->settings->get('wh_enabled', '0') ?? '0')) === '1';
  }

  /**
   * @param array $product  Row de products
   * @param array|null $variant Row de product_variants (si aplica)
   * @param string $userRole role del usuario (customer/admin/mayorista1/mayorista2)
   */
  public function getUnitPriceForUser(array $product, ?array $variant, string $userRole): int {
    $public = $this->getPublicPrice($product, $variant);

    if (!$this->isWholesaleEnabled()) {
      return $public;
    }

    $role = strtolower(trim($userRole));
    if ($role !== 'mayorista1' && $role !== 'mayorista2') {
      return $public;
    }

    $override = $this->getWholesaleOverride($role, $product, $variant);
    if ($override !== null) {
      return $this->applyRounding($override);
    }

    $pct = $this->getDiscountPercent($role);
    $calced = $this->applyDiscountPercent($public, $pct);

    return $this->applyRounding($calced);
  }

  private function getPublicPrice(array $product, ?array $variant): int {
    $v = $variant['price'] ?? null;
    if ($v !== null && $v !== '') return (int)$v;
    return (int)($product['price'] ?? 0);
  }

  private function getWholesaleOverride(string $role, array $product, ?array $variant): ?int {
    $col = ($role === 'mayorista2') ? 'price_wh2_override' : 'price_wh1_override';

    // Herencia: variante > producto
    if ($variant) {
      $v = $variant[$col] ?? null;
      if ($v !== null && $v !== '') return (int)$v;
    }

    $p = $product[$col] ?? null;
    if ($p !== null && $p !== '') return (int)$p;

    return null;
  }

  private function getDiscountPercent(string $role): float {
    $key = ($role === 'mayorista2') ? 'wh2_discount_percent' : 'wh1_discount_percent';
    $raw = trim((string)($this->settings->get($key, '0') ?? '0'));
    $raw = str_replace(',', '.', $raw);
    $pct = (float)$raw;
    if ($pct < 0) $pct = 0;
    if ($pct > 100) $pct = 100;
    return $pct;
  }

  private function applyDiscountPercent(int $publicPrice, float $pct): int {
    if ($publicPrice <= 0) return 0;
    $mult = (100.0 - $pct) / 100.0;
    $val = (int)round($publicPrice * $mult);
    if ($val < 0) $val = 0;
    return $val;
  }

  private function applyRounding(int $price): int {
    $enabled = ((string)($this->settings->get('wh_round_enabled', '0') ?? '0')) === '1';
    if (!$enabled) return $price;

    $to = (int)trim((string)($this->settings->get('wh_round_to', '0') ?? '0'));
    if ($to <= 1) return $price;

    $mode = strtolower(trim((string)($this->settings->get('wh_round_mode', 'round') ?? 'round')));
    if (!in_array($mode, ['round','ceil','floor'], true)) $mode = 'round';

    $x = $price / $to;

    if ($mode === 'ceil')  return (int)(ceil($x) * $to);
    if ($mode === 'floor') return (int)(floor($x) * $to);
    return (int)(round($x) * $to);
  }
}
