<?php
namespace App\Services\Checkout;

use App\Repositories\Account\AddressRepository;
use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Orders\OrderItemRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Mail\Orders\TransactionalMail;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Cart\CartWriteService;
use App\Services\Audit\Audit;
use App\Services\Pricing\CommercialPriceService;
use App\Support\Security\CheckoutFiscal;
use App\Support\Format\Tax;
use PDO;
use Throwable;

final class CheckoutOrderService
{
    public function __construct(private PDO $db) {}

    /**
     * Confirma checkout:
     * - valida carrito
     * - valida dirección (propia)
     * - valida stock con locks (FOR UPDATE)
     * - crea order + order_items con snapshots
     * - descuenta stock (reserva efectiva)
     *
     * @return array{0:bool,1:string,2:int}
     */
    public function confirm(
        int $userId,
        int $addressId,
        ?string $paymentMethod = null,
        ?string $checkoutToken = null,
        array $checkoutSnapshot = []
    ): array
    {
        $cartReadSvc = new CartReadService($this->db);
        $cartWriteSvc = new CartWriteService($this->db);
        $selectionSvc = new CartSelectionService();
        $raw = $cartReadSvc->getCartView();
        foreach ((array)($raw['items'] ?? []) as $item) {
            if (is_array($item) && array_key_exists('is_publicly_available',$item)
                && empty($item['is_publicly_available'])) {
                return [false,CheckoutProductEligibilityGuard::ERROR,0];
            }
        }
        $cart = $selectionSvc->apply($raw);
        $pricing = new CommercialPriceService(new AppSettingsRepository($this->db));
        $effectiveCart = $pricing->buildCheckoutCart($cart, (string)($paymentMethod ?? 'transferencia'));

        if (empty($effectiveCart['items'])) {
            return [false, 'Tu carrito está vacío.', 0];
        }

        $addrRepo = new AddressRepository($this->db);
        $addr = $addrRepo->findForUser($addressId, $userId);
        if (!$addr) {
            return [false, 'Dirección inválida.', 0];
        }

        // Totales (por ahora sin envío/desc)
        $subtotal = array_key_exists('subtotal', $checkoutSnapshot)
            ? (int)$checkoutSnapshot['subtotal']
            : (int)($effectiveCart['subtotal'] ?? 0);

        $shipping = 0;
        $shipSnap = $_SESSION['checkout_shipping'] ?? null;
        if (is_array($shipSnap)) {
            $shipping = (int)($shipSnap['cost'] ?? 0);
            if ($shipping < 0) $shipping = 0;
        }
        if (array_key_exists('shipping', $checkoutSnapshot)) {
            $shipping = max(0, (int)$checkoutSnapshot['shipping']);
        }

        $discount = array_key_exists('discount', $checkoutSnapshot)
            ? (int)$checkoutSnapshot['discount']
            : (int)($effectiveCart['discount'] ?? 0);

        $total = array_key_exists('total', $checkoutSnapshot)
            ? (int)$checkoutSnapshot['total']
            : (int)($effectiveCart['total'] ?? max(0, $subtotal + $shipping - $discount));

        // Snapshot fiscal del vendedor (Ajustes) - no bloqueante
        $settingsRepo = new AppSettingsRepository($this->db);
        $sellerTaxRegime = (string)($settingsRepo->get('fiscal_seller_tax_regime', 'monotributo') ?? 'monotributo');
        $sellerTaxRegime = strtolower(trim($sellerTaxRegime));
        if ($sellerTaxRegime === '') $sellerTaxRegime = 'monotributo';
        $sellerCuit = (string)($settingsRepo->get('fiscal_seller_cuit', '') ?? '');
        $sellerBusinessName = (string)($settingsRepo->get('fiscal_seller_business_name', '') ?? '');
        $sellerFiscalAddress = (string)($settingsRepo->get('fiscal_seller_fiscal_address', '') ?? '');

        // Moneda (por ahora ARS como base del sitio). En el futuro lo conectamos a currencies.
        $currencyCode = (string)($settingsRepo->get('currency_base_code', 'ARS') ?? 'ARS');
        $currencyCode = strtoupper(trim($currencyCode));
        if ($currencyCode === '') $currencyCode = 'ARS';

        // Snapshot fiscal del cliente (Paso 1 del checkout, autosave en sesión)
        $cust = CheckoutFiscal::getSession();
        $customerTaxCond = (string)($cust['tax_condition'] ?? 'consumidor_final');
        $customerCuit = isset($cust['cuit']) && is_string($cust['cuit']) && trim($cust['cuit']) !== '' ? trim($cust['cuit']) : null;

        $invRepo = new InventoryRepository($this->db);
        $orderRepo = new OrderRepository($this->db);

        // Atribución de marketing (campañas internas / UTMs guardadas en sesión)
            $mktCampaignId = (int)($_SESSION['marketing_campaign_id'] ?? 0);
            $emailCampaignId = (int)($_SESSION['email_campaign_id'] ?? 0);

        // Idempotencia (transferencia): mismo user + address + carrito -> mismo checkout_token
        $checkoutToken = is_string($checkoutToken) ? trim($checkoutToken) : null;
        if ($checkoutToken === '') {
            $checkoutToken = null;
        }
        if ($checkoutToken === null && in_array((string)($paymentMethod ?? ''), ['transfer','transferencia'], true)) {
            $itemsJson = json_encode($effectiveCart['items'] ?? [], JSON_UNESCAPED_UNICODE);
            $checkoutToken = substr(hash('sha256', (string)$userId.'|'.(string)$addressId.'|'.(string)$total.'|'.(string)$itemsJson), 0, 64);

            $existing = $orderRepo->findIdByCheckoutToken($checkoutToken);
            if ($existing > 0) {
                return [true, 'Ya tenés un pedido pendiente de pago para este carrito.', (int)$existing];
            }
        }
        if ($checkoutToken !== null) {
            $existing = $orderRepo->findIdByCheckoutToken($checkoutToken);
            if ($existing > 0) {
                return [true, 'Ya tenÃ©s un pedido pendiente de pago para este carrito.', (int)$existing];
            }
        }
        $itemRepo = new OrderItemRepository($this->db);

        try {
            $this->db->beginTransaction();
            // Serializar transferencias del mismo comprador ANTES de tocar stock u ordenes.
            // La consulta previa a la transaccion no protege contra dos POST simultaneos.
            if ($this->db->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'mysql'
                && $checkoutToken !== null && in_array((string)$paymentMethod, ['transfer', 'transferencia'], true)) {
                $lockUser = $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
                $lockUser->execute([$userId]);
                if ((int)$lockUser->fetchColumn() !== $userId) {
                    throw new \RuntimeException('Comprador no disponible.');
                }
                $existingLocked = $orderRepo->findIdByCheckoutToken($checkoutToken);
                if ($existingLocked > 0) {
                    $this->db->commit();
                    return [true, 'Ya tenés un pedido pendiente de pago para este carrito.', $existingLocked];
                }
            }
            [$eligible,$reason] = (new CheckoutProductEligibilityGuard($this->db))
                ->checkItems((array)$effectiveCart['items'],true);
            if (!$eligible) {
                $this->db->rollBack();
                return [false,$reason,0];
            }

            // 1) Validar y lockear stock por cada ítem
            $adjustQtys = []; // key => qty (para ajustar carrito si falta stock)
            foreach ($effectiveCart['items'] as $it) {
                $pid = (int)($it['id'] ?? 0);
                $qty = (int)($it['qty'] ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    throw new \RuntimeException('Carrito inválido.');
                }

                $vid = (int)($it['variant_id'] ?? 0);
                $stock = ($vid > 0)
                    ? $invRepo->lockVariantStockForUpdate($vid)
                    : $invRepo->lockStockForUpdate($pid);
                $stock = $stock === null ? 0 : $stock;
                if ($stock < $qty) {
                    // Ajustamos el carrito al stock disponible (o removemos si es 0)
                    $key = $pid . ':' . $vid;
                    $adjustQtys[$key] = max(0, (int)$stock);
                }
            }

            // Si hubo faltantes de stock: ajustamos carrito y pedimos revisión antes de confirmar
            if (!empty($adjustQtys)) {
                $this->db->rollBack();
                $cartSvc = new CartWriteService($this->db);
                // bulkUpdate clampa y remueve según stock real; acá forzamos a los valores permitidos.
                $cartSvc->bulkUpdate($adjustQtys);
                return [false, 'CART_CHANGED: Actualizamos tu carrito según el stock disponible. Revisá el resumen y volvé a confirmar.', 0];
            }

            // 1b) Snapshot fiscal (neto/IVA) por ítem + totales del pedido
            $productIds = [];
            foreach ($effectiveCart['items'] as $it) {
                $pid = (int)($it['id'] ?? ($it['product_id'] ?? 0));
                if ($pid > 0) $productIds[] = $pid;
            }
            $productIds = array_values(array_unique($productIds));

            $vatRateMap = [];
            if ($productIds) {
                $ph = implode(',', array_fill(0, count($productIds), '?'));
                $stVat = $this->db->prepare("SELECT id, COALESCE(iva_rate,0) AS iva_rate FROM products WHERE id IN ($ph)");
                $stVat->execute($productIds);
                $rows = $stVat->fetchAll(\PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $pid = (int)($r['id'] ?? 0);
                    if ($pid <= 0) continue;
                    $rate = (float)($r['iva_rate'] ?? 0.0);
                    $rate = ($rate > 1.0) ? ($rate / 100.0) : $rate;
                    if ($rate < 0.0) $rate = 0.0;
                    $vatRateMap[$pid] = $rate;
                }
            }

            $taxByKey = []; // "pid:vid" => tax fields
            $orderTotalNet = 0;
            $orderTotalVat = 0;
            foreach ($effectiveCart['items'] as $it) {
                $pid = (int)($it['id'] ?? ($it['product_id'] ?? 0));
                $vid = (int)($it['variant_id'] ?? 0);
                $vidNorm = $vid > 0 ? $vid : 0;
                $key = $pid . ':' . $vidNorm;

                $qty = max(0, (int)($it['qty'] ?? 0));
                $unitTotal = max(0, (int)($it['price'] ?? 0));
                $lineTotal = $unitTotal * $qty;

                $rate = (float)($vatRateMap[$pid] ?? 0.0);
                $unitSplit = Tax::splitTotal($unitTotal, $rate);
                $lineSplit = Tax::splitTotal($lineTotal, $rate);

                $taxByKey[$key] = [
                    'vat_rate' => $rate,
                    'unit_net' => (int)$unitSplit['net'],
                    'unit_vat' => (int)$unitSplit['vat'],
                    'line_net' => (int)$lineSplit['net'],
                    'line_vat' => (int)$lineSplit['vat'],
                ];

                $orderTotalNet += (int)$lineSplit['net'];
                $orderTotalVat += (int)$lineSplit['vat'];
            }

            // 2) Crear pedido con snapshot de dirección
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

            $orderId = $orderRepo->create([
                'user_id' => $userId,
                'status'  => 'pending_payment',
                'payment_method' => $paymentMethod,
                'checkout_token' => $checkoutToken,
                'marketing_campaign_id' => $mktCampaignId,
                'email_campaign_id' => $emailCampaignId,
                // snapshot fiscal vendedor/cliente
                'seller_tax_regime' => $sellerTaxRegime,
                'seller_cuit' => (trim($sellerCuit) !== '' ? trim($sellerCuit) : null),
                'seller_business_name' => (trim($sellerBusinessName) !== '' ? trim($sellerBusinessName) : null),
                'seller_fiscal_address' => (trim($sellerFiscalAddress) !== '' ? trim($sellerFiscalAddress) : null),
                // por ahora el checkout no pide CUIT/condición; guardamos default para dejar listo RI
                'customer_tax_condition' => $customerTaxCond,
                'customer_cuit' => $customerCuit,
                'currency_code' => $currencyCode,
                'subtotal'=> $subtotal,
                'shipping'=> $shipping,
                'discount'=> $discount,
                'total'   => $total,
                // totales contables (si existen columnas)
                'total_net' => $orderTotalNet,
                'total_vat' => $orderTotalVat,
                'address_id' => (int)$addr['id'],
                'shipping_full_name' => $addr['full_name'] ?? null,
                'shipping_phone'     => $addr['phone'] ?? null,
                'shipping_street'    => $addr['street'] ?? null,
                'shipping_number'    => $addr['number'] ?? null,
                'shipping_floor'     => $addr['floor'] ?? null,
                'shipping_apartment' => $addr['apartment'] ?? null,
                'shipping_city'      => $addr['city'] ?? null,
                'shipping_state'     => $addr['state'] ?? null,
                'shipping_zip'       => $addr['zip'] ?? null,
                'shipping_notes'     => $addr['notes'] ?? null,
                'shipping_address_json' => $addressJson,
            ]);

            if ($orderId <= 0) {
                throw new \RuntimeException('No se pudo crear el pedido.');
            }

            // 3) Insertar items (snapshots básicos)
            $itemsToInsert = [];
            foreach ($effectiveCart['items'] as $it) {
                $vid = (int)($it['variant_id'] ?? 0);

                $pid = (int)($it['id'] ?? ($it['product_id'] ?? 0));
                $vidNorm = $vid > 0 ? $vid : 0;
                $key = $pid . ':' . $vidNorm;
                $tax = $taxByKey[$key] ?? ['vat_rate'=>0.0,'unit_net'=>null,'unit_vat'=>null,'line_net'=>null,'line_vat'=>null];

                $itemsToInsert[] = [
                    'product_id' => $pid,
                    'variant_id' => ($vid > 0 ? $vid : null),
                    'variant_sku' => $it['variant_sku'] ?? null,
                    // mostramos una etiqueta human friendly (sin "Variante:")
                    'variant_title' => $it['variant_title'] ?? ($it['variant_label'] ?? null),
                    'variant_attributes_json' => $it['variant_attributes_json'] ?? null,
                    'qty' => (int)$it['qty'],
                    'price' => (int)$it['price'],
                    // snapshot fiscal
                    'vat_rate' => $tax['vat_rate'],
                    'unit_net' => $tax['unit_net'],
                    'unit_vat' => $tax['unit_vat'],
                    'line_net' => $tax['line_net'],
                    'line_vat' => $tax['line_vat'],
                    'product_sku' => (string)($it['sku'] ?? ''),
                    'product_name' => (string)($it['name'] ?? ''),
                ];
            }
            $itemRepo->bulkInsert($orderId, $itemsToInsert);

            // 3b) Compatibilidad: si la orden se creó antes de correr la migración, este UPDATE puede fallar.
            // Dejamos best-effort para instalaciones que agreguen columnas sin reiniciar el flujo.
            try {
                $stUpTax = $this->db->prepare('UPDATE orders SET total_net = :n, total_vat = :v WHERE id = :id');
                $stUpTax->execute([':n' => (int)$orderTotalNet, ':v' => (int)$orderTotalVat, ':id' => (int)$orderId]);
            } catch (\Throwable $_) {}

            // 4) Descontar stock (reserva efectiva)
            foreach ($effectiveCart['items'] as $it) {
                $pid = (int)$it['id'];
                $qty = (int)$it['qty'];
                $vid = (int)($it['variant_id'] ?? 0);
                if ($vid > 0) {
                    $invRepo->decrementVariantStock($vid, $qty);
                } else {
                    $invRepo->decrementStock($pid, $qty);
                }
            }

            $this->db->commit();

            // Auditoría: creación de pedido por transferencia (pending_payment)
            $pmForAudit = (string)($paymentMethod ?? '');
            if (in_array($pmForAudit, ['transfer','transferencia'], true)) {
                try {
                    Audit::log('order_created_transfer','order',(int)$orderId,[
                        'payment_method' => $pmForAudit,
                        'status' => 'pending_payment',
                        'total'  => $total,
                        'address_id' => (int)($addr['id'] ?? 0),
                        'items_count' => (int)count($effectiveCart['items'] ?? []),
                        'checkout_token' => $checkoutToken,
                    ]);
                    cache_forget('feed:audit_recent8');
                } catch (\Throwable $auditError) {
                    if (function_exists('log_error')) {
                        log_error('CheckoutOrderService audit post-commit: '.$auditError->getMessage());
                    }
                }
            }


            // 5) Email transaccional (no bloqueante)
            try {
                $tm = new TransactionalMail($this->db);
                $pm = (string)($paymentMethod ?? '');
                $tm->sendOrderCreated(
                    $userId,
                    (int)$orderId,
                    $itemsToInsert,
                    (int)($effectiveCart['subtotal'] ?? 0),
                    0,
                    0,
                    (int)($effectiveCart['subtotal'] ?? 0),
                    $pm,
                    $addr
                );

                // Si es transferencia, enviamos instrucciones (MVP día 1)
                if ($pm === 'transfer' || $pm === 'transferencia') {
                    $tm->sendTransferInstructions($userId, (int)$orderId, $itemsToInsert, (int)($effectiveCart['subtotal'] ?? 0));
                }
            } catch (\Throwable $_) {
                // silencioso: no frenamos checkout por un email
            }

            try {
                foreach ($effectiveCart['items'] as $it) {
                    $productId = (int)($it['product_id'] ?? $it['id'] ?? 0);
                    $variantId = (int)($it['variant_id'] ?? 0);
                    if ($productId > 0) {
                        $cartWriteSvc->remove($productId, $variantId);
                    }
                }
                $selectionSvc->clear();
            } catch (\Throwable $cartCleanupError) {
                if (function_exists('log_error')) {
                    log_error('CheckoutOrderService cart cleanup post-commit: '.$cartCleanupError->getMessage());
                }
            }

            return [true, 'Pedido creado correctamente.', (int)$orderId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('CheckoutOrderService@confirm: '.$e->getMessage());
            }
            return [false, $e->getMessage(), 0];
        }
    }
}

