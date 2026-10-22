<?php
namespace App\Services\Checkout;

use App\Repositories\Account\AddressRepository;
use App\Repositories\Checkout\CheckoutIntentRepository;
use App\Repositories\Payments\PaymentRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Pricing\CommercialPriceService;
use App\Support\Security\CheckoutFiscal;
use App\Support\Format\Tax;
use PDO;

final class CheckoutIntentService
{
    public function __construct(private PDO $db) {}

    /** @return array{0:bool,1:string,2:array<string,mixed>} */
    public function createIntent(int $userId, int $addressId, string $paymentMethod = 'transferencia'): array
    {
        $cartSvc = new CartReadService($this->db);
        $selectionSvc = new CartSelectionService();
        $raw = $cartSvc->getCartView();
        foreach ((array)($raw['items'] ?? []) as $item) {
            if (is_array($item) && array_key_exists('is_publicly_available',$item)
                && empty($item['is_publicly_available'])) {
                return [false,CheckoutProductEligibilityGuard::ERROR,
                    ['code'=>'b1_unavailable']];
            }
        }
        $cart = $selectionSvc->apply($raw);
        $pricing = new CommercialPriceService(new AppSettingsRepository($this->db));
        $effectiveCart = $pricing->buildCheckoutCart($cart, $paymentMethod);

        if (empty($effectiveCart['items'])) {
            return [false, 'Tu carrito está vacío.', []];
        }
        try {
            [$eligible, $reason] = (new CheckoutProductEligibilityGuard($this->db))
                ->checkItems((array)$effectiveCart['items']);
            if (!$eligible) return [false,$reason,['code'=>'b1_unavailable']];
        } catch (\Throwable $e) {
            return [false,'No pudimos verificar la disponibilidad de tus productos.',['code'=>'b1_validation_error']];
        }

        $addrRepo = new AddressRepository($this->db);
        $addr = $addrRepo->findForUser($addressId, $userId);
        if (!$addr) {
            return [false, 'Dirección inválida.', []];
        }

        $subtotal = (int)($effectiveCart['subtotal'] ?? 0);
        $shipping = (int)($effectiveCart['shipping'] ?? 0);

        // Envío (FIX516): snapshot elegido en checkout (sesión). Si existe, prevalece.
        $shipSnap = $_SESSION['checkout_shipping'] ?? null;
        if (is_array($shipSnap)) {
            $shipping = (int)($shipSnap['cost'] ?? $shipping);
            if ($shipping < 0) $shipping = 0;
        }
        $discount = (int)($effectiveCart['discount'] ?? 0);
        $total = (int)($effectiveCart['total'] ?? max(0, $subtotal + $shipping - $discount));

        // Snapshot fiscal del vendedor (Ajustes)
        $settingsRepo = new AppSettingsRepository($this->db);
        $sellerTaxRegime = (string)($settingsRepo->get('fiscal_seller_tax_regime', 'monotributo') ?? 'monotributo');
        $sellerTaxRegime = strtolower(trim($sellerTaxRegime));
        if ($sellerTaxRegime === '') $sellerTaxRegime = 'monotributo';
        $sellerCuit = (string)($settingsRepo->get('fiscal_seller_cuit', '') ?? '');
        $sellerBusinessName = (string)($settingsRepo->get('fiscal_seller_business_name', '') ?? '');
        $sellerFiscalAddress = (string)($settingsRepo->get('fiscal_seller_fiscal_address', '') ?? '');

        // Moneda base (MVP)
        $currencyCode = (string)($settingsRepo->get('currency_base_code', 'ARS') ?? 'ARS');
        $currencyCode = strtoupper(trim($currencyCode));
        if ($currencyCode === '') $currencyCode = 'ARS';

        // Snapshot fiscal del cliente (Paso 1: sesión)
        $cust = CheckoutFiscal::getSession();
        $customerTaxCond = (string)($cust['tax_condition'] ?? 'consumidor_final');
        $customerCuit = isset($cust['cuit']) && is_string($cust['cuit']) && trim($cust['cuit']) !== '' ? trim($cust['cuit']) : null;

        // Enriquecemos items con neto/IVA según IVA de producto (para que el webhook no dependa de sesión)
        $productIds = [];
        foreach ($effectiveCart['items'] as $it) {
            $pid = (int)($it['id'] ?? ($it['product_id'] ?? 0));
            if ($pid > 0) $productIds[$pid] = true;
        }
        $productIds = array_keys($productIds);

        $vatRateMap = [];
        if (!empty($productIds)) {
            $ph = implode(',', array_fill(0, count($productIds), '?'));
            $st = $this->db->prepare("SELECT id, iva_rate FROM products WHERE id IN ($ph)");
            $st->execute($productIds);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $pid = (int)($r['id'] ?? 0);
                $rate = (float)($r['iva_rate'] ?? 0);
                $rate = ($rate > 1.0) ? ($rate / 100.0) : $rate;
                if ($rate < 0.0) $rate = 0.0;
                $vatRateMap[$pid] = $rate;
            }
        }

        $hashItems = [];
        $enriched = [];
        $orderTotalNet = 0;
        $orderTotalVat = 0;
        foreach ($effectiveCart['items'] as $it) {
            $pid = (int)($it['id'] ?? ($it['product_id'] ?? 0));
            $vid = (int)($it['variant_id'] ?? 0);
            $qty = max(0, (int)($it['qty'] ?? 0));
            $unitTotal = max(0, (int)($it['price'] ?? 0));
            $lineTotal = $unitTotal * $qty;

            $rate = (float)($vatRateMap[$pid] ?? 0.0);
            $unitSplit = Tax::splitTotal($unitTotal, $rate);
            $lineSplit = Tax::splitTotal($lineTotal, $rate);

            $it['vat_rate'] = $rate;
            $it['unit_net'] = (int)$unitSplit['net'];
            $it['unit_vat'] = (int)$unitSplit['vat'];
            $it['line_net'] = (int)$lineSplit['net'];
            $it['line_vat'] = (int)$lineSplit['vat'];
            $enriched[] = $it;

            $orderTotalNet += (int)$lineSplit['net'];
            $orderTotalVat += (int)$lineSplit['vat'];

            $hashItems[] = [
                'product_id' => $pid,
                'variant_id' => $vid,
                'qty' => $qty,
                'price' => $unitTotal,
            ];
        }

        $addressJson = json_encode([
            'id' => (int)$addr['id'],
            'label' => $addr['label'] ?? null,
            'full_name' => $addr['full_name'] ?? null,
            'phone' => $addr['phone'] ?? null,
            'street' => $addr['street'] ?? null,
            'number' => $addr['number'] ?? null,
            'floor' => $addr['floor'] ?? null,
            'apartment' => $addr['apartment'] ?? null,
            'city' => $addr['city'] ?? null,
            'state' => $addr['state'] ?? null,
            'zip' => $addr['zip'] ?? null,
            'notes' => $addr['notes'] ?? null,
        ], JSON_UNESCAPED_UNICODE);

        // Guardamos items enriquecidos (incluye neto/IVA por línea)
        $itemsJson = json_encode($enriched, JSON_UNESCAPED_UNICODE);
        // Totales + snapshot fiscal (también dentro de totals_json para no depender de columnas)
        $totalsJson = json_encode([
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'shipping_snapshot' => (is_array($shipSnap) ? $shipSnap : null),
            'discount' => $discount,
            'total'    => $total,
            'payment_method' => $paymentMethod,
            'total_net' => $orderTotalNet,
            'total_vat' => $orderTotalVat,
            // Snapshot fiscal vendedor/cliente (para que el webhook no dependa de sesión)
            'seller_tax_regime' => $sellerTaxRegime,
            'seller_cuit' => (trim($sellerCuit) !== '' ? trim($sellerCuit) : null),
            'seller_business_name' => (trim($sellerBusinessName) !== '' ? trim($sellerBusinessName) : null),
            'seller_fiscal_address' => (trim($sellerFiscalAddress) !== '' ? trim($sellerFiscalAddress) : null),
            'customer_tax_condition' => $customerTaxCond,
            'customer_cuit' => $customerCuit,
            'currency_code' => $currencyCode,
        ], JSON_UNESCAPED_UNICODE);

        // Hash reutilizable (no depende de campos "decorativos" del item)
        $hashItemsJson = json_encode($hashItems, JSON_UNESCAPED_UNICODE);
        $cartHash = hash('sha256',
            (string)$addressId.
            '|'.(string)$total.
            '|'.(string)$hashItemsJson.
            '|'.(string)$customerTaxCond.
            '|'.(string)($customerCuit ?? '')
        );

        $repo = new CheckoutIntentRepository($this->db);

        // Atribución: /promo (campañas internas) y Email Marketing (click tracking)
        $mktCampaignId = (int)($_SESSION['marketing_campaign_id'] ?? 0);
        $emailCampaignId = (int)($_SESSION['email_campaign_id'] ?? 0);

        // Reutilización: mismo user+address+car_hash en ventana corta (evita tokens múltiples por doble submit)
        $reuseLock = $repo->acquireReuseLock($userId,$addressId,$cartHash,$mktCampaignId ?: null,$emailCampaignId ?: null);
        try {
        // Aun si la respuesta de MP se perdiÃ³ antes de persistir payments,
        // no ofrecer un nuevo cobro hasta revisar el intento en vuelo.
        if ($repo->findInFlightForCart($userId,$addressId,$cartHash)) {
            return [false,
                'Hay un cobro cuyo resultado debe verificarse. No vuelvas a pagar.',
                ['code'=>'payment_requires_review']];
}
        $outstanding=(new PaymentRepository($this->db))->findOutstandingForCart(
            $userId,$addressId,$cartHash
        );
        if ($outstanding) {
            return [false,
                'Ya existe un pago en proceso o aprobado para este carrito. No vuelvas a pagar hasta verificarlo.',
                ['code'=>'payment_requires_review']];
        }
        $reuse = $repo->findReusable($userId, $addressId, $cartHash, 30, $mktCampaignId ?: null, $emailCampaignId ?: null);
        if ($reuse) {
            return [true, 'Intent reutilizado.', [
                'id' => (int)$reuse['id'],
                'token' => (string)$reuse['token'],
                'amount' => (int)$reuse['amount'],
                'currency' => (string)($reuse['currency'] ?? 'ARS'),
                'items' => $effectiveCart['items'],
                'totals' => ['subtotal'=>$subtotal,'shipping'=>$shipping,'discount'=>$discount,'total'=>$total],
            ]];
        }

        $token = bin2hex(random_bytes(16));

        $id = $repo->create([
            'user_id' => $userId,
            'address_id' => $addressId,
            'token' => $token,
            'cart_hash' => $cartHash,
            'marketing_campaign_id' => $mktCampaignId,
            'email_campaign_id' => $emailCampaignId,
            'status' => 'created',
            'amount' => $total,
            'currency' => $currencyCode,
            // snapshot fiscal vendedor/cliente (si existen columnas)
            'seller_tax_regime' => $sellerTaxRegime,
            'seller_cuit' => (trim($sellerCuit) !== '' ? trim($sellerCuit) : null),
            'seller_business_name' => (trim($sellerBusinessName) !== '' ? trim($sellerBusinessName) : null),
            'seller_fiscal_address' => (trim($sellerFiscalAddress) !== '' ? trim($sellerFiscalAddress) : null),
            'customer_tax_condition' => $customerTaxCond,
            'customer_cuit' => $customerCuit,
            'currency_code' => $currencyCode,
            'total_net' => $orderTotalNet,
            'total_vat' => $orderTotalVat,
            'address_json' => $addressJson ?: '{}',
            'items_json' => $itemsJson ?: '[]',
            'totals_json' => $totalsJson ?: '{}',
        ]);

        return [true, 'Intent creado.', [
            'id' => $id,
            'token' => $token,
            'amount' => $total,
            'currency' => $currencyCode,
            'payment_method' => $paymentMethod,
            'items' => $effectiveCart['items'],
            'totals' => ['subtotal'=>$subtotal,'shipping'=>$shipping,'discount'=>$discount,'total'=>$total,'total_net'=>$orderTotalNet,'total_vat'=>$orderTotalVat],
        ]];
        } finally {
            $repo->releaseReuseLock($reuseLock);
        }
    }
}

