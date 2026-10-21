<?php
namespace App\Services\Orders;

use App\Repositories\Checkout\CheckoutIntentRepository;
use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Orders\OrderItemRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Mail\Orders\TransactionalMail;
use App\Services\Loyalty\LoyaltyOrderEventsService;
use PDO;
use PDOException;
use Throwable;

final class OrderFromIntentService
{
    public function __construct(private PDO $db) {}

    /** @return array{0:bool,1:string,2:int} */
    public function createOrderFromIntent(string $token): array
    {
        $intentRepo = new CheckoutIntentRepository($this->db);
        $invRepo = new InventoryRepository($this->db);
        $orderRepo = new OrderRepository($this->db);
        $itemRepo = new OrderItemRepository($this->db);

        try {
            $this->db->beginTransaction();

            // Lock intent (cierra carreras de webhooks / doble procesamiento)
            $intent = $intentRepo->lockByToken($token);
            if (!$intent) {
                $this->db->rollBack();
                return [false, 'Intent inválido.', 0];
            }

            // Si ya hay order_id asociado, devolver (idempotente)
            $existingOrderId = (int)($intent['order_id'] ?? 0);
            if (($intent['status'] ?? '') === 'used' && $existingOrderId > 0) {
                $this->db->commit();
                return [true, 'Ya procesado.', $existingOrderId];
            }

            // Red de seguridad: si el pedido ya existe por checkout_token, devolverlo (unique DB)
            $byToken = $orderRepo->findIdByCheckoutToken($token);
            if ($byToken > 0) {
                if ($existingOrderId <= 0) $intentRepo->attachOrder((int)$intent['id'], $byToken);
                if (($intent['status'] ?? '') !== 'used') $intentRepo->markUsed((int)$intent['id']);
                $this->db->commit();
                return [true, 'Ya procesado.', $byToken];
            }

            $userId = (int)($intent['user_id'] ?? 0);
            if ($userId <= 0) throw new \RuntimeException('Usuario inválido.');

            $items = json_decode((string)($intent['items_json'] ?? '[]'), true);
            if (!is_array($items) || empty($items)) throw new \RuntimeException('Ítems inválidos.');

            $totals = json_decode((string)($intent['totals_json'] ?? '{}'), true);
            if (!is_array($totals)) $totals = [];
            $subtotal = (int)($totals['subtotal'] ?? 0);
            $shipping = (int)($totals['shipping'] ?? 0);
            $discount = (int)($totals['discount'] ?? 0);
            $total = (int)($totals['total'] ?? 0);

            // Snapshot fiscal/contable desde intent (si existe). Fallback seguro a Ajustes.
            $settingsRepo = new AppSettingsRepository($this->db);

            // Intent puede traer snapshot en columnas o dentro de totals_json (MVP sin migración).
            $sellerTaxRegime = strtolower(trim((string)($intent['seller_tax_regime'] ?? ($totals['seller_tax_regime'] ?? ($settingsRepo->get('fiscal_seller_tax_regime', 'monotributo') ?? 'monotributo')))));
            if ($sellerTaxRegime === '') $sellerTaxRegime = 'monotributo';
            $sellerCuit = (string)($intent['seller_cuit'] ?? ($totals['seller_cuit'] ?? ($settingsRepo->get('fiscal_seller_cuit', '') ?? '')));
            $sellerBusinessName = (string)($intent['seller_business_name'] ?? ($totals['seller_business_name'] ?? ($settingsRepo->get('fiscal_seller_business_name', '') ?? '')));
            $sellerFiscalAddress = (string)($intent['seller_fiscal_address'] ?? ($totals['seller_fiscal_address'] ?? ($settingsRepo->get('fiscal_seller_fiscal_address', '') ?? '')));

            $customerTaxCond = (string)($intent['customer_tax_condition'] ?? ($totals['customer_tax_condition'] ?? 'consumidor_final'));
            $customerCuit = $intent['customer_cuit'] ?? ($totals['customer_cuit'] ?? null);
            if (is_string($customerCuit)) {
                $customerCuit = trim($customerCuit);
                if ($customerCuit === '') $customerCuit = null;
            } else {
                $customerCuit = null;
            }

            $currencyCode = strtoupper(trim((string)($intent['currency_code'] ?? ($totals['currency_code'] ?? ($intent['currency'] ?? ($settingsRepo->get('currency_base_code','ARS') ?? 'ARS'))))));
            if ($currencyCode === '') $currencyCode = 'ARS';

            // Totales neto/IVA: si el intent no trae, los recalculamos desde items enriquecidos.
            $orderTotalNet = isset($intent['total_net']) ? (int)$intent['total_net'] : (int)($totals['total_net'] ?? 0);
            $orderTotalVat = isset($intent['total_vat']) ? (int)$intent['total_vat'] : (int)($totals['total_vat'] ?? 0);

            // Régimen: Monotributo / no RI => no discrimina IVA en facturación.
            $sellerIsRI = in_array($sellerTaxRegime, ['responsable_inscripto', 'ri'], true);

            // Si no es RI, guardamos neto=total, iva=0 (registro contable consistente).
            if (!$sellerIsRI) {
                $orderTotalNet = max(0, $total);
                $orderTotalVat = 0;
            }

            $addressJson = (string)($intent['address_json'] ?? '{}');
            $addrSnap = json_decode($addressJson, true);
            if (!is_array($addrSnap)) $addrSnap = [];

            // 1) Validar y lockear stock por cada ítem
            foreach ($items as $it) {
                $pid = (int)($it['id'] ?? $it['product_id'] ?? 0);
                $qty = (int)($it['qty'] ?? 0);
                if ($pid <= 0 || $qty <= 0) throw new \RuntimeException('Carrito inválido.');

                $vid = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);
                $stock = ($vid > 0)
                    ? $invRepo->lockVariantStockForUpdate($vid)
                    : $invRepo->lockStockForUpdate($pid);
                $stock = $stock === null ? 0 : $stock;
                if ($stock < $qty) {
                    throw new \RuntimeException('Sin stock para "'.(string)($it['name'] ?? 'producto').'".');
                }
            }

            // 2) Crear pedido (nace pagado → preparación)
            $intentPaymentMethod = strtolower(trim((string)($totals['payment_method'] ?? 'tarjeta_credito')));
            if ($intentPaymentMethod === '') $intentPaymentMethod = 'tarjeta_credito';

            try {
                $orderId = $orderRepo->create([
                    'user_id' => $userId,
                    'status'  => 'processing',
                    'payment_method' => $intentPaymentMethod,
                    'checkout_token' => $token,
                    'marketing_campaign_id' => (int)($intent['marketing_campaign_id'] ?? 0),
                    'email_campaign_id' => (int)($intent['email_campaign_id'] ?? 0),
                    'subtotal'=> max(0, $subtotal),
                    'shipping'=> max(0, $shipping),
                    'discount'=> max(0, $discount),
                    'total'   => max(0, $total),

                    // Snapshot fiscal (opcional, depende de migración)
                    'seller_tax_regime' => $sellerTaxRegime,
                    'seller_cuit' => (trim($sellerCuit) !== '' ? trim($sellerCuit) : null),
                    'seller_business_name' => (trim($sellerBusinessName) !== '' ? trim($sellerBusinessName) : null),
                    'seller_fiscal_address' => (trim($sellerFiscalAddress) !== '' ? trim($sellerFiscalAddress) : null),
                    'customer_tax_condition' => $customerTaxCond,
                    'customer_cuit' => $customerCuit,
                    'currency_code' => $currencyCode,
                    'total_net' => $sellerIsRI ? max(0, $orderTotalNet) : max(0, $total),
                    'total_vat' => $sellerIsRI ? max(0, $orderTotalVat) : 0,

                    'address_id' => (int)($intent['address_id'] ?? 0),
                    'shipping_full_name' => $addrSnap['full_name'] ?? null,
                    'shipping_phone'     => $addrSnap['phone'] ?? null,
                    'shipping_street'    => $addrSnap['street'] ?? null,
                    'shipping_number'    => $addrSnap['number'] ?? null,
                    'shipping_floor'     => $addrSnap['floor'] ?? null,
                    'shipping_apartment' => $addrSnap['apartment'] ?? null,
                    'shipping_city'      => $addrSnap['city'] ?? null,
                    'shipping_state'     => $addrSnap['state'] ?? null,
                    'shipping_zip'       => $addrSnap['zip'] ?? null,
                    'shipping_notes'     => $addrSnap['notes'] ?? null,
                    'shipping_address_json' => $addressJson,
                ]);
            } catch (PDOException $e) {
                // Si ya existe por unique checkout_token, devolver el existente (idempotente)
                if (($e->getCode() ?? '') === '23000') {
                    $orderId = $orderRepo->findIdByCheckoutToken($token);
                    if ($orderId > 0) {
                        $intentRepo->attachOrder((int)$intent['id'], (int)$orderId);
                        $intentRepo->markUsed((int)$intent['id']);
                        $this->db->commit();
                        return [true, 'Ya procesado.', (int)$orderId];
                    }
                }
                throw $e;
            }

            if ($orderId <= 0) throw new \RuntimeException('No se pudo crear el pedido.');

            // 3) Items + descuento de stock
            $rows = [];
            $sumNet = 0;
            $sumVat = 0;
            foreach ($items as $it) {
                $pid = (int)($it['id'] ?? $it['product_id'] ?? 0);
                $qty = (int)($it['qty'] ?? 0);
                $price = (int)($it['price'] ?? 0);
                $vid = (int)($it['variant_id'] ?? $it['variantId'] ?? 0);

                // IVA por ítem: si no viene enriquecido, queda null.
                $vatRate = isset($it['vat_rate']) ? (float)$it['vat_rate'] : null;
                $unitNet = isset($it['unit_net']) ? (int)$it['unit_net'] : null;
                $unitVat = isset($it['unit_vat']) ? (int)$it['unit_vat'] : null;
                $lineNet = isset($it['line_net']) ? (int)$it['line_net'] : null;
                $lineVat = isset($it['line_vat']) ? (int)$it['line_vat'] : null;

                // Para monotributo/no RI: no discriminar IVA (neto = total, IVA=0)
                if (!$sellerIsRI) {
                    $vatRate = 0.0;
                    $unitNet = $price;
                    $unitVat = 0;
                    $lineNet = $price * $qty;
                    $lineVat = 0;
                }

                if (is_int($lineNet)) $sumNet += $lineNet;
                if (is_int($lineVat)) $sumVat += $lineVat;

                $rows[] = [
                    'product_id' => $pid,
                    'variant_id' => $vid > 0 ? $vid : null,
                    'qty' => $qty,
                    'price' => $price,

                    // Snapshot fiscal por ítem (opcional)
                    'vat_rate' => $vatRate,
                    'unit_net' => $unitNet,
                    'unit_vat' => $unitVat,
                    'line_net' => $lineNet,
                    'line_vat' => $lineVat,

                    // snapshots
                    'product_sku' => $it['product_sku'] ?? ($it['sku'] ?? null),
                    'product_name'=> $it['product_name'] ?? ($it['name'] ?? null),
                    'variant_sku' => $it['variant_sku'] ?? ($it['variantSku'] ?? null),
                    'variant_title' => $it['variant_title'] ?? ($it['variant_label'] ?? ($it['variantLabel'] ?? null)),
                    'variant_attributes_json' => $it['variant_attributes_json'] ?? ($it['variantAttributes'] ?? null),

                    // opcionales
                    'unit_list_price' => $it['unit_list_price'] ?? ($it['list_price'] ?? null),
                    'unit_cost' => $it['unit_cost'] ?? ($it['cost'] ?? null),
                ];

                if ($vid > 0) {
                    $invRepo->decrementVariantStock($vid, $qty);
                } else {
                    $invRepo->decrementStock($pid, $qty);
                }
            }
            $itemRepo->bulkInsert($orderId, $rows);

            // Si el intent no traía totales neto/IVA, y estamos en RI, actualizamos con lo sumado.
            if ($sellerIsRI && ($orderTotalNet <= 0 && $orderTotalVat <= 0) && ($sumNet > 0 || $sumVat > 0)) {
                try {
                    $st = $this->db->prepare("UPDATE orders SET total_net=:n, total_vat=:v WHERE id=:id");
                    $st->execute([':n' => max(0, $sumNet), ':v' => max(0, $sumVat), ':id' => (int)$orderId]);
                } catch (Throwable $e3) { /* best-effort */ }
            }

            // 4) Cerrar intent (vincular + used)
            $intentRepo->attachOrder((int)$intent['id'], (int)$orderId);
            $intentRepo->markUsed((int)$intent['id']);

            $this->db->commit();

            // Lealtad: registrar evento de puntos del pedido pagado (best-effort).
            try {
                (new LoyaltyOrderEventsService($this->db))->onOrderStatusTransition(
                    (int)$orderId,
                    (int)$userId,
                    (int)$total,
                    'pending_payment',
                    'processing',
                    'order_from_intent'
                );
            } catch (Throwable $e) {
                // no romper
            }

            // Email transaccional (best-effort)
            try {
                $tm = new TransactionalMail($this->db);
                $tm->sendPaymentConfirmed($userId, (int)$orderId, $intentPaymentMethod);
            } catch (Throwable $e) {}

            return [true, 'Pedido creado.', (int)$orderId];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();

            // Marcar failed si corresponde (y si el intent existe y no fue usado)
            try {
                $intent = $intentRepo->findByToken($token);
                if ($intent && (($intent['status'] ?? '') === 'created')) {
                    $intentRepo->markFailed((int)$intent['id'], substr($e->getMessage(), 0, 255));
                }
            } catch (Throwable $e2) {}

            return [false, $e->getMessage(), 0];
        }
    }
}

