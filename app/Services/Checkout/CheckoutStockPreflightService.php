<?php
namespace App\Services\Checkout;

use App\Repositories\Account\AddressRepository;
use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Cart\CartWriteService;
use App\Services\Pricing\CommercialPriceService;
use PDO;
use Throwable;

final class CheckoutStockPreflightService
{
    public function __construct(private PDO $db) {}

    /** @return array{0:bool,1:string,2:array<string,mixed>} */
    public function validate(int $userId, int $addressId, string $paymentMethod): array
    {
        $cartReadSvc = new CartReadService($this->db);
        $selectionSvc = new CartSelectionService();
        $raw = $cartReadSvc->getCartView();
        foreach ((array)($raw['items'] ?? []) as $item) {
            if (is_array($item) && array_key_exists('is_publicly_available',$item)
                && empty($item['is_publicly_available'])) {
                return [false,CheckoutProductEligibilityGuard::ERROR,
                    ['code'=>'b1_unavailable','redirect_url'=>url('/carrito?checkout_cambio=1')]];
            }
        }
        $cart = $selectionSvc->apply($raw);
        $pricing = new CommercialPriceService(new AppSettingsRepository($this->db));
        $effectiveCart = $pricing->buildCheckoutCart($cart, $paymentMethod);

        if (empty($effectiveCart['items'])) {
            return [false, 'Tu carrito esta vacio.', ['code' => 'cart_empty']];
        }

        $addrRepo = new AddressRepository($this->db);
        $addr = $addrRepo->findForUser($addressId, $userId);
        if (!$addr) {
            return [false, 'Direccion invalida.', ['code' => 'invalid_address']];
        }

        $invRepo = new InventoryRepository($this->db);

        try {
            $this->db->beginTransaction();
            [$eligible,$reason] = (new CheckoutProductEligibilityGuard($this->db))
                ->checkItems((array)$effectiveCart['items'],true);
            if (!$eligible) {
                $this->db->rollBack();
                return [false,$reason,['code'=>'b1_unavailable',
                    'redirect_url'=>url('/carrito?checkout_cambio=1')]];
            }

            $adjustQtys = [];
            foreach ($effectiveCart['items'] as $it) {
                $pid = (int)($it['id'] ?? ($it['product_id'] ?? 0));
                $qty = max(0, (int)($it['qty'] ?? 0));
                $vid = (int)($it['variant_id'] ?? 0);

                if ($pid <= 0 || $qty <= 0) {
                    throw new \RuntimeException('Carrito invalido.');
                }

                $stock = ($vid > 0)
                    ? $invRepo->lockVariantStockForUpdate($vid)
                    : $invRepo->lockStockForUpdate($pid);
                $stock = $stock === null ? 0 : (int)$stock;

                if ($stock < $qty) {
                    $key = $pid . ':' . $vid;
                    $adjustQtys[$key] = max(0, $stock);
                }
            }

            $this->db->rollBack();

            if (!empty($adjustQtys)) {
                (new CartWriteService($this->db))->bulkUpdate($adjustQtys);
                return [
                    false,
                    'Actualizamos tu carrito segun el stock disponible. Revisa el resumen y vuelve a intentar.',
                    [
                        'code' => 'stock_changed',
                        'redirect_url' => url('/carrito?checkout_cambio=1'),
                    ],
                ];
            }

            return [true, 'Stock disponible.', ['code' => 'ok']];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (function_exists('log_error')) {
                log_error('CheckoutStockPreflightService@validate: ' . $e->getMessage());
            }
            return [false, 'No pudimos validar el stock en este momento.', ['code' => 'stock_preflight_failed']];
        }
    }
}
