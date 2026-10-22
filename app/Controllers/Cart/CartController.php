<?php
namespace App\Controllers\Cart;

use App\Repositories\Settings\AppSettingsRepository;
use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Catalog\CategoryRepository;
use App\Services\Catalog\RecommendedProductsService;
use App\Services\Cart\CartReadService;
use App\Services\Cart\CartSelectionService;
use App\Services\Cart\CartViewModelService;
use App\Services\Cart\CartWriteService;
use App\Services\Pricing\CommercialPriceService;
use App\Services\Public\HomeSectionsViewService;

/**
 * Carrito
 * - Persistente (DB) por user_id o cookie token
 * - Sin JS obligatorio (solo mejora UX)
 */
final class CartController
{
    public function index(): void
    {
        $db = db();
        $writeSvc = new CartWriteService($db);
        $mergeRecovered = false;
        $recoveryTitle = '';
        $recoveryCopy = '';
        $mergeRequested = (string)($_GET['merge'] ?? '') === '1';
        if ($mergeRequested && !empty($_SESSION['cart_merge_pending'])) {
            [$mergedOk, $_] = $writeSvc->applyMergeDecision('unify');
            $mergeRecovered = $mergedOk;
            if ($mergedOk) {
                $recoveryTitle = 'Recuperamos productos de tu cuenta';
                $recoveryCopy = 'Los estamos agregando a tu carrito.';
            }
        }

        $repeatRequested = (string)($_GET['repeat'] ?? '') === '1';
        if ($repeatRequested && !empty($_SESSION['repeat_order_items']) && is_array($_SESSION['repeat_order_items'])) {
            $requestedCount = count($_SESSION['repeat_order_items']);
            $added = $writeSvc->mergeItems($_SESSION['repeat_order_items']);
            unset($_SESSION['repeat_order_items']);
            if ($added > 0) {
                $mergeRecovered = true;
                $recoveryTitle = 'Recuperamos productos de tu pedido';
                $recoveryCopy = 'Los estamos agregando a tu carrito.';
            }
            if ($added < $requestedCount) {
                $omitted = max(0, $requestedCount - $added);
                flash('warn', 'No se pudieron agregar ' . $omitted . ' producto(s) (sin stock o no disponibles).');
            }
        }

        $readSvc = new CartReadService($db);
        $rawCart = $readSvc->getCartView();
        $cart = (new CartViewModelService($db))->build($rawCart);
        foreach ((array)($cart['items'] ?? []) as $item) {
            if (is_array($item) && empty($item['is_publicly_available'])) {
                flash('warn', 'Hay productos que dejaron de estar disponibles. Permanecen en tu carrito para que puedas revisarlos o quitarlos.');
                break;
            }
        }
        $selectionSvc = new CartSelectionService();
        $selectedKeys = $selectionSvc->selectedKeys($cart);
        $selectedCart = $selectionSvc->apply($cart);
        $selectedSummary = $this->buildSummaryData($selectedCart);
        $viewItems = $this->prepareViewItems($cart, $selectedKeys);
        $related = (new RecommendedProductsService($db))->forCart($cart, 5, 6);
        $homeSections = new HomeSectionsViewService(
            new BrandRepository($db),
            new CategoryRepository($db)
        );
        $checkoutChanged = (int)($_GET['checkout_cambio'] ?? 0) === 1;
        $selectableItemsCount = 0;
        foreach ((array)($cart['items'] ?? []) as $item) {
            if (is_array($item) && !empty($item['is_selectable'])) {
                $selectableItemsCount++;
            }
        }
        $allSelected = ($selectableItemsCount > 0 && count($selectedKeys) === $selectableItemsCount);

        render_page('carrito/index', [
            'pageId' => 'cart',
            'cart'   => $cart,
            'viewItems' => $viewItems,
            'selectedCart' => $selectedCart,
            'selectedKeys' => $selectedKeys,
            'selectedSummary' => $selectedSummary,
            'related' => $related,
            'homeBrands' => $homeSections->brands(6),
            'homeCategories' => $homeSections->categories(4),
            'checkoutChanged' => $checkoutChanged,
            'mergeRecovered' => $mergeRecovered,
            'recoveryTitle' => $recoveryTitle,
            'recoveryCopy' => $recoveryCopy,
            'allSelected' => $allSelected,
        ]);
    }

    public function add(): void
    {
        verify_csrf_or_abort();

        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $buyNow = !empty($_POST['buy_now']);

        $writeSvc = new CartWriteService(db());
        $readSvc = new CartReadService(db());
        [$ok, $msg] = $writeSvc->add($pid, $qty, $variantId);

        // Analíticas: agregar al carrito (solo éxito)
        if ($ok) {
            try {
                if (class_exists(\App\Services\Analytics\AnalyticsService::class)) {
                    (new \App\Services\Analytics\AnalyticsService(db()))->trackAddToCart($pid, $variantId, $qty);
                }
            } catch (\Throwable $e) { /* no bloqueante */ }
        }
 
        // Si viene por fetch/AJAX, respondemos JSON (para catálogo / quick-add / UX sin recargar)
        if ($this->wantsJson()) {
            $cart = $readSvc->getCartView();
            json([
                'ok' => (bool)$ok,
                'message' => (string)$msg,
                'cart' => [
                    'total_qty' => (int)($cart['total_qty'] ?? 0),
                    'subtotal' => (int)($cart['subtotal'] ?? 0),
                ],
            ], $ok ? 200 : 422);
            return;
        }

        flash($ok ? 'ok' : 'error', $msg);

        if ($ok && $buyNow) {
            redirect('/checkout');
            return;
        }

        // Volver a donde estaba (si viene)
        $back = (string)($_POST['_back'] ?? $_SERVER['HTTP_REFERER'] ?? '');
        if ($back) {
            redirect($back);
            return;
        }

        redirect('/carrito');
    }

    public function update(): void
    {
        verify_csrf_or_abort();

        /** @var array<string,string|int> $qtys */
        $qtys = $_POST['qty'] ?? [];
        $writeSvc = new CartWriteService(db());
        $readSvc = new CartReadService(db());
        [$ok, $msg] = $writeSvc->bulkUpdate($qtys);

        if ($this->wantsJson()) {
            $rawCart = $readSvc->getCartView();
            $cart = (new CartViewModelService(db()))->build($rawCart);
            $selectionSvc = new CartSelectionService();
            $selectedCart = $selectionSvc->apply($cart);
            $selectedSummary = $this->buildSummaryData($selectedCart);

            // si vino un solo item, devolvemos snapshot de ese item
            $key = null;
            if (is_array($qtys) && count($qtys) === 1) {
                $key = (string)array_key_first($qtys);
            }

            $item = null;
            if ($key !== null) {
                foreach (($cart['items'] ?? []) as $it) {
                    if ((string)($it['key'] ?? '') === $key) { $item = $it; break; }
                }
            }

            if (is_array($item)) {
                [$hintText, $hintClass] = $this->itemHint($item);
                $item['hint'] = ['text' => $hintText, 'class' => $hintClass];
            }

            if ($key !== null && $item === null) {
                json([
                    'ok' => false,
                    'message' => 'El producto ya no esta en el carrito.',
                    'cart' => [
                        'subtotal' => (int)($cart['subtotal'] ?? 0),
                        'total_qty' => (int)($cart['total_qty'] ?? 0),
                    ],
                    'selected_cart' => [
                        'subtotal' => (int)($selectedCart['subtotal'] ?? 0),
                        'total_qty' => (int)($selectedCart['total_qty'] ?? 0),
                        'items_count' => (int)($selectedCart['items_count'] ?? count($selectedCart['items'] ?? [])),
                    ],
                    'selected_summary' => $selectedSummary,
                    'item' => null,
                ], 404);
            }

            json([
                'ok' => (bool)$ok,
                'message' => (string)$msg,
                'cart' => [
                    'subtotal' => (int)($cart['subtotal'] ?? 0),
                    'total_qty' => (int)($cart['total_qty'] ?? 0),
                ],
                'selected_cart' => [
                    'subtotal' => (int)($selectedCart['subtotal'] ?? 0),
                    'total_qty' => (int)($selectedCart['total_qty'] ?? 0),
                    'items_count' => (int)($selectedCart['items_count'] ?? count($selectedCart['items'] ?? [])),
                ],
                'selected_summary' => $selectedSummary,
                'item' => $item,
            ], $ok ? 200 : 422);
        }

        flash($ok ? 'ok' : 'error', $msg);
        redirect('/carrito');
    }

    public function remove(): void
    {
        verify_csrf_or_abort();
        $pid = (int)($_POST['product_id'] ?? 0);
        $vid = (int)($_POST['variant_id'] ?? 0);

        $writeSvc = new CartWriteService(db());
        $readSvc = new CartReadService(db());

        $removed = false;
        if ($pid > 0) {
            $removed = $writeSvc->remove($pid, $vid);
        }

        if ($this->wantsJson()) {
            $cart = $readSvc->getCartView();
            $selectionSvc = new CartSelectionService();
            $selectedCart = $selectionSvc->apply($cart);
            $selectedSummary = $this->buildSummaryData($selectedCart);
            json([
                'ok' => $removed,
                'message' => $removed ? 'Producto eliminado del carrito.' : 'El producto ya no estaba en el carrito.',
                'cart' => [
                    'subtotal' => (int)($cart['subtotal'] ?? 0),
                    'total_qty' => (int)($cart['total_qty'] ?? 0),
                    'items_count' => (int)count($cart['items'] ?? []),
                ],
                'selected_cart' => [
                    'subtotal' => (int)($selectedCart['subtotal'] ?? 0),
                    'total_qty' => (int)($selectedCart['total_qty'] ?? 0),
                    'items_count' => (int)($selectedCart['items_count'] ?? count($selectedCart['items'] ?? [])),
                ],
                'selected_summary' => $selectedSummary,
            ], $removed ? 200 : 404);
        }

        flash($removed ? 'ok' : 'warn', $removed ? 'Producto eliminado del carrito.' : 'El producto ya no estaba en el carrito.');
        redirect('/carrito');
    }

    public function clear(): void
    {
        verify_csrf_or_abort();
        $writeSvc = new CartWriteService(db());
        $writeSvc->clear();
        (new CartSelectionService())->clear();

        // Si viene por fetch/AJAX, respondemos JSON (checkout necesita vaciar carrito sin redirect)
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $ajax   = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
        if ($ajax || stripos($accept, 'application/json') !== false) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true]);
            return;
        }

        flash('ok', 'Carrito vaciado.');
        redirect('/carrito');
    }

    public function selection(): void
    {
        verify_csrf_or_abort();

        $readSvc = new CartReadService(db());
        $selectionSvc = new CartSelectionService();
        $cart = $readSvc->getCartView();
        $selectedKeys = $_POST['selected_keys'] ?? [];
        if (!is_array($selectedKeys)) {
            $selectedKeys = [];
        }

        $savedKeys = $selectionSvc->save($cart, $selectedKeys);
        $selectedCart = $selectionSvc->apply($cart);
        $selectedSummary = $this->buildSummaryData($selectedCart);

        json([
            'ok' => true,
            'selected_keys' => $savedKeys,
            'selected_cart' => [
                'subtotal' => (int)($selectedCart['subtotal'] ?? 0),
                'total_qty' => (int)($selectedCart['total_qty'] ?? 0),
                'items_count' => (int)($selectedCart['items_count'] ?? count($selectedCart['items'] ?? [])),
            ],
            'selected_summary' => $selectedSummary,
        ]);
    }

    // POST /carrito/repeat/merge
    public function repeatMerge(): void
    {
        verify_csrf_or_abort();
        $items = $_SESSION['repeat_order_items'] ?? null;
        if (!is_array($items) || empty($items)) {
            flash('warn', 'No hay un pedido pendiente para repetir.');
            redirect('/carrito');
            return;
        }

        $writeSvc = new CartWriteService(db());
        $added = $writeSvc->mergeItems($items);

        unset($_SESSION['repeat_order_items']);
        if ($added > 0) flash('ok', 'Sumamos los productos del pedido a tu carrito.');
        else flash('error', 'No pudimos sumar los productos del pedido (no disponibles).');

        redirect('/carrito');
    }

    // POST /carrito/repeat/replace
    public function repeatReplace(): void
    {
        verify_csrf_or_abort();
        $items = $_SESSION['repeat_order_items'] ?? null;
        if (!is_array($items) || empty($items)) {
            flash('warn', 'No hay un pedido pendiente para repetir.');
            redirect('/carrito');
            return;
        }

        $writeSvc = new CartWriteService(db());
        $added = $writeSvc->replaceWithItems($items);

        unset($_SESSION['repeat_order_items']);
        if ($added > 0) flash('ok', 'Reemplazamos tu carrito por los productos del pedido.');
        else flash('error', 'No pudimos cargar los productos del pedido (no disponibles).');

        redirect('/carrito');
    }

    // POST /carrito/repeat/cancel
    public function repeatCancel(): void
    {
        verify_csrf_or_abort();
        unset($_SESSION['repeat_order_items']);
        flash('ok', 'Listo, no hicimos cambios en tu carrito.');
        redirect('/carrito');
    }

// POST /carrito/merge/unify
public function mergeUnify(): void
{
    auth_required();
    verify_csrf_or_abort();

    $writeSvc = new CartWriteService(db());
    [$ok, $msg] = $writeSvc->applyMergeDecision('unify');

    flash($ok ? 'ok' : 'error', $ok ? 'Listo. Unificamos tus carritos.' : $msg);
    redirect('/carrito');
}

// POST /carrito/merge/replace
public function mergeReplace(): void
{
    auth_required();
    verify_csrf_or_abort();

    $writeSvc = new CartWriteService(db());
    [$ok, $msg] = $writeSvc->applyMergeDecision('replace');

    flash($ok ? 'ok' : 'error', $ok ? 'Listo. Usaremos solo los productos que agregaste recién.' : $msg);
    redirect('/carrito');
}

// POST /carrito/merge/discard
public function mergeDiscard(): void
{
    auth_required();
    verify_csrf_or_abort();

    $writeSvc = new CartWriteService(db());
    [$ok, $msg] = $writeSvc->applyMergeDecision('discard');

    flash($ok ? 'ok' : 'error', $ok ? 'Listo. Mantuvimos tu carrito guardado.' : $msg);
    redirect('/carrito');
}


    private function wantsJson(): bool
    {
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        if (stripos($accept, 'application/json') !== false) return true;

        $xrw = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        if (strcasecmp($xrw, 'XMLHttpRequest') === 0) return true;

        // ⚠️ No usar flags de formulario tipo _ajax para decidir JSON.
        // Si el usuario hace submit normal, debe volver por redirect.
        return false;
    }

    private function stockHint(int $stock, int $qty): array
    {
        // Mensajes suaves tipo “premium”
        if ($stock <= 0) return ['Sin stock para esta opción.', 'cart-item__stock--error'];
        if ($stock === 1) return ['Última unidad disponible.', 'cart-item__stock--warn'];
        if ($qty > $stock) return ['Sin stock suficiente para la cantidad elegida.', 'cart-item__stock--error'];
        if ($stock > 1 && $stock <= 3) return ['Quedan pocas unidades para esta opción.', 'cart-item__stock--warn'];
        return ['', ''];
    }

    /**
     * @param array<string,mixed> $item
     * @return array{0:string,1:string}
     */
    private function itemHint(array $item): array
    {
        if (array_key_exists('is_publicly_available', $item) && empty($item['is_publicly_available'])) {
            return ['Este producto está oculto para nuevas compras. Se conserva en tu carrito; podés quitarlo.',
                'cart-item__stock--error'];
        }
        $isActive = !empty($item['is_active']);
        if (!$isActive) {
            return ['Este producto ya no está disponible para comprar. Lo mantenemos en tu carrito, pero no se sumará al pedido.', 'cart-item__stock--error'];
        }

        return $this->stockHint((int)($item['stock'] ?? 0), (int)($item['qty'] ?? 0));
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<string,mixed>
     */
    private function buildSummaryData(array $cart): array
    {
        $settings = new AppSettingsRepository(db());
        $pricing = new CommercialPriceService($settings);

        $transferSubtotal = max(0, (int)($cart['subtotal'] ?? 0));
        $financedSubtotal = 0;

        foreach ((array)($cart['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $qty = max(0, (int)($item['qty'] ?? 0));
            if ($qty <= 0) {
                continue;
            }

            $transferUnit = (int)($item['price'] ?? 0);
            $financedUnit = $pricing->resolveFinancedPrice(
                $transferUnit,
                isset($item['list_price']) ? (int)$item['list_price'] : 0
            );
            $financedSubtotal += $financedUnit * $qty;
        }

        $installmentsCopy = '';
        if ($financedSubtotal > 0 && $pricing->isInstallmentsEnabled() && $pricing->installmentsCount() >= 2) {
            $installmentsCopy = 'En ' . $pricing->installmentsCount() . ' cuotas sin interes';
        }

        $freeMinAmount = max(0, (int)($settings->get('shipping_free_min_amount', '0') ?? 0));
        $showFreeShipping = ($freeMinAmount > 0 && $transferSubtotal >= $freeMinAmount);

        return [
            'financed_subtotal' => $financedSubtotal,
            'transfer_subtotal' => $transferSubtotal,
            'transfer_savings' => max(0, $financedSubtotal - $transferSubtotal),
            'installments_copy' => $installmentsCopy,
            'show_free_shipping' => $showFreeShipping,
            'free_shipping_label' => $showFreeShipping ? 'Gratis' : '',
        ];
    }

    /**
     * @param array<string,mixed> $cart
     * @param array<int,string> $selectedKeys
     * @return array<int,array<string,mixed>>
     */
    private function prepareViewItems(array $cart, array $selectedKeys): array
    {
        $items = [];
        $selectedMap = array_flip($selectedKeys);

        foreach ((array)($cart['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $stock = (int)($item['stock'] ?? 0);
            $qty = (int)($item['qty'] ?? 0);
            $itemKey = (string)($item['key'] ?? (((int)($item['id'] ?? 0)) . ':' . (int)($item['variant_id'] ?? 0)));
            $isSelectable = !empty($item['is_selectable']);
            [$hintText, $hintClass] = $this->itemHint($item);

            $maxQty = $stock > 0 ? $stock : 12;
            $maxQty = max(1, min(12, $maxQty));

            $item['view_item_key'] = $itemKey;
            $item['view_is_selectable'] = $isSelectable;
            $item['view_is_selected'] = ($isSelectable && isset($selectedMap[$itemKey]));
            $item['view_hint_text'] = $hintText;
            $item['view_hint_class'] = $hintClass;
            $item['view_discount_pct'] = (int)($item['display_discount_pct'] ?? 0);
            $item['view_max_qty'] = $maxQty;
            $item['view_line_total'] = (int)($item['price'] ?? 0) * $qty;
            $items[] = $item;
        }

        return $items;
    }
}
