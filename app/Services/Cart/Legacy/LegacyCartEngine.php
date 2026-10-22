<?php
namespace App\Services\Cart\Legacy;

use App\Repositories\Cart\CartRepository;
use App\Repositories\Catalog\ProductReadRepository;
use App\Repositories\Catalog\VariantRepository;
use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Pricing\PriceService;
use PDO;

/**
 * Carrito persistente (DB) con soporte anónimo por cookie + usuario logueado.
 * - Source of truth: DB (carts + cart_items)
 * - Cookie anónimo: cart_token (180 días)
 * - En sesión solo mantenemos cart_count por conveniencia UI.
 */
final class LegacyCartEngine
{
    private const COOKIE = 'cart_token';
    private const COOKIE_DAYS = 180;

    private CartRepository $cartRepo;
    private ProductReadRepository $productRepo;
    private VariantRepository $variantRepo;
    private InventoryRepository $invRepo;
    private PriceService $priceService;
    private string $userRole = 'customer';

    private int $cartId = 0;
    private int $userId = 0;
    private string $token = '';

    public function __construct(private PDO $db)
    {
        $this->cartRepo = new CartRepository($db);
        $this->productRepo = new ProductReadRepository($db);
        $this->variantRepo = new VariantRepository($db);
        $this->invRepo = new InventoryRepository($db);

$this->userRole = (string)($_SESSION['urole'] ?? $_SESSION['role'] ?? 'customer');
$settingsRepo = new AppSettingsRepository($db);
$this->priceService = new PriceService($settingsRepo);


        $this->userId = (int)($_SESSION['uid'] ?? 0);
        $this->token = $this->readOrCreateToken();

        $this->cartId = $this->resolveCartId();
        $this->recount();
    }

    /**
     * Firma determinística del carrito.
     *
     * Propósito: detectar cambios reales de ítems/cantidades sin depender del orden
     * (que puede variar entre requests si vienen de DB sin ORDER BY estable).
     *
     * Solo incluye IDs + cantidades (NO precios/nombres).
     *
     * @param array<int,array<string,mixed>> $items
     */
    public static function buildCartSignature(array $items): string
    {
        try {
            $sigParts = [];
            foreach ($items as $it) {
                if (!is_array($it)) continue;
                $sigParts[] = [
                    (int)($it['product_id'] ?? 0),
                    (int)($it['variant_id'] ?? 0),
                    (int)($it['qty'] ?? $it['quantity'] ?? 0),
                ];
            }

            usort($sigParts, static function(array $a, array $b): int {
                // Orden estable: product_id, variant_id, qty
                if ($a[0] !== $b[0]) return $a[0] <=> $b[0];
                if ($a[1] !== $b[1]) return $a[1] <=> $b[1];
                return $a[2] <=> $b[2];
            });

            return sha1(json_encode($sigParts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (\Throwable $e) {
            return 'err';
        }
    }

    /** Token anónimo (cookie). */
    private function readOrCreateToken(): string
    {
        $t = (string)($_COOKIE[self::COOKIE] ?? '');
        $t = trim($t);

        // Sanitización mínima
        if ($t !== '' && preg_match('/^[a-f0-9]{32,64}$/i', $t)) return $t;

        $t = bin2hex(random_bytes(16)); // 32 chars
        $this->setTokenCookie($t);
        return $t;
    }

    private function setTokenCookie(string $token): void
    {
        $secure = function_exists('is_https') ? is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $exp = time() + (self::COOKIE_DAYS * 86400);

        // PHP < 8.3 compat: array options
        setcookie(self::COOKIE, $token, [
            'expires' => $exp,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Mantener en memoria para el request actual
        $_COOKIE[self::COOKIE] = $token;
    }

    private function resolveCartId(): int
    {
        // 1) si logueado, usamos/creamos por user_id
        if ($this->userId > 0) {
            $cid = $this->cartRepo->findActiveCartIdForUser($this->userId);
            if ($cid > 0) return $cid;
            return $this->cartRepo->createUserCart($this->userId);
        }

        // 2) anónimo por token cookie
        $cid = $this->cartRepo->findActiveCartIdByToken($this->token);
        if ($cid > 0) return $cid;

        // Si el token apunta a un carrito NO activo (abandoned/converted/etc), no podemos re-usar el mismo token
        // porque carts.token es UNIQUE. Rotamos token y creamos un carrito nuevo para este anónimo.
        $any = $this->cartRepo->findAnyCartByToken($this->token);
        if ($any && ((string)$any['status']) !== 'active') {
            $this->token = bin2hex(random_bytes(16));
            $this->setTokenCookie($this->token);
        }

        return $this->cartRepo->createAnonymousCart($this->token);
    }

    /** Permite mergear un carrito anónimo (cookie) al carrito del usuario al loguearse. */
    public function mergeAnonymousToUser(int $userId): void
    {
        $userId = (int)$userId;
        if ($userId <= 0) return;

        $token = (string)($_COOKIE[self::COOKIE] ?? '');
        $token = trim($token);
        if ($token === '') return;

        $anonCartId = $this->cartRepo->findActiveCartIdByToken($token);
        if ($anonCartId <= 0) return;

        $userCartId = $this->cartRepo->findActiveCartIdForUser($userId);
        if ($userCartId <= 0) {
            // Si el usuario no tenía carrito, adoptamos el anónimo
            $this->cartRepo->attachCartToUser($anonCartId, $userId);
            return;
        }

        if ($anonCartId === $userCartId) return;

        // Merge items
        $items = $this->cartRepo->items($anonCartId);
        if ($items) {
            $this->db->beginTransaction();
            try {
                foreach ($items as $it) {
                    $pid = (int)$it['product_id'];
                    $vid = (int)($it['variant_id'] ?? 0);
                    $vid = ($vid > 0) ? $vid : null;
                    $qty = max(1, (int)$it['quantity']);
                    $this->cartRepo->upsertItem($userCartId, $pid, $vid, $qty);
                }
                $this->cartRepo->markMerged($anonCartId, $userCartId);
                $this->db->commit();
            } catch (\Throwable $e) {
                $this->db->rollBack();
                // best effort: no bloquear login
            }
        }
    }

    /** @return string none|adopted|merged|conflict */
    public function prepareMergeOnLogin(int $userId): string
    {
        $userId = (int)$userId;
        if ($userId <= 0) return 'none';

        $token = trim((string)($_COOKIE[self::COOKIE] ?? ''));
        if ($token === '') return 'none';

        $anonCartId = $this->cartRepo->findActiveCartIdByToken($token);
        if ($anonCartId <= 0) return 'none';

        $anonQty = (int)$this->cartRepo->countQty($anonCartId);
        if ($anonQty <= 0) return 'none';

        $userCartId = $this->cartRepo->findActiveCartIdForUser($userId);
        if ($userCartId <= 0) {
            $this->cartRepo->attachCartToUser($anonCartId, $userId);
            return 'adopted';
        }

        if ($anonCartId === $userCartId) return 'none';

        $userQty = (int)$this->cartRepo->countQty($userCartId);
        if ($userQty > 0) {
            $_SESSION['cart_merge_pending'] = [
                'anon_cart_id' => $anonCartId,
                'user_cart_id' => $userCartId,
                'ts' => time(),
            ];
            return 'conflict';
        }

        // user cart existe pero vacío -> merge automático
        $this->mergeAnonymousToUser($userId);
        return 'merged';
    }

    /** Información para mostrar el bloque UX de merge (si hay conflicto pendiente). */
    public function getMergePendingInfo(): ?array
    {
        $p = $_SESSION['cart_merge_pending'] ?? null;
        if (!is_array($p)) return null;

        $anonCartId = (int)($p['anon_cart_id'] ?? 0);
        $userCartId = (int)($p['user_cart_id'] ?? 0);
        if ($anonCartId <= 0 || $userCartId <= 0) return null;

        return [
            'anon_cart_id' => $anonCartId,
            'user_cart_id' => $userCartId,
            'anon_qty' => (int)$this->cartRepo->countQty($anonCartId),
            'user_qty' => (int)$this->cartRepo->countQty($userCartId),
        ];
    }

    /** @return array{0:bool,1:string} */
    public function applyMergeDecision(string $action): array
    {
        $info = $this->getMergePendingInfo();
        if (!$info) return [false, 'No hay una decisión de carrito pendiente.'];

        $anonCartId = (int)$info['anon_cart_id'];
        $userCartId = (int)$info['user_cart_id'];
        $action = strtolower(trim($action));

        if (!in_array($action, ['unify', 'replace', 'discard'], true)) {
            return [false, 'Acción inválida.'];
        }

        try {
            $this->db->beginTransaction();

            $anonItems = $this->cartRepo->items($anonCartId);

            if ($action === 'replace') {
                $this->cartRepo->clearItems($userCartId);
            }

            if ($action === 'unify' || $action === 'replace') {
                foreach ($anonItems as $it) {
                    $pid = (int)$it['product_id'];
                    $vid = (int)($it['variant_id'] ?? 0);
                    $vid = ($vid > 0) ? $vid : null;
                    $qty = max(1, (int)$it['quantity']);
                    $this->cartRepo->upsertItem($userCartId, $pid, $vid, $qty);
                }
            }

            // Si el usuario eligió descartar, el anónimo queda abandoned.
            // Si eligió unificar o reemplazar, el anónimo queda merged para analítica.
            if ($action === 'discard') {
                $this->cartRepo->markAbandoned($anonCartId);
            } else {
                $this->cartRepo->markMerged($anonCartId, $userCartId);
            }
                $this->db->commit();
        } catch (\Throwable $e) {
            try { $this->db->rollBack(); } catch (\Throwable $__) {}
            return [false, 'No pudimos actualizar tu carrito. Intentá nuevamente.'];
        }

        unset($_SESSION['cart_merge_pending']);

        // refrescar contador en sesión
        try {
            $this->cartId = $userCartId;
            $this->recount();
        } catch (\Throwable $e) {}

        return [true, 'OK'];
    }

    /**
     * Compat legacy (product_id => qty) para lugares que aún no diferencian variantes.
     * OJO: para UI de carrito/checkout usamos getCartView(), que sí respeta variant_id.
     * @return array<int,int>
     */
    public function raw(): array
    {
        $items = $this->cartRepo->items($this->cartId);
        $out = [];
        foreach ($items as $it) {
            $pid = (int)$it['product_id'];
            $out[$pid] = ($out[$pid] ?? 0) + (int)$it['quantity'];
        }
        return $out;
    }

    private function currentItemQuantity(int $productId, ?int $variantId = null): int
    {
        $qty = 0;
        foreach ($this->cartRepo->items($this->cartId) as $item) {
            $itemProductId = (int)($item['product_id'] ?? 0);
            $itemVariantId = (int)($item['variant_id'] ?? 0);
            $itemVariantId = $itemVariantId > 0 ? $itemVariantId : null;
            if ($itemProductId !== $productId || $itemVariantId !== $variantId) {
                continue;
            }
            $qty += max(0, (int)($item['quantity'] ?? 0));
        }
        return $qty;
    }

    /** @return array{0:bool,1:string} */
    public function add(int $productId, int $qty, ?int $variantId = null): array
    {
        if ($productId <= 0) return [false, 'Producto inválido.'];
        $qty = max(1, min(999, (int)$qty));

        // Validamos producto activo (sin bloquear si repo no lo trae)
        $p = $this->productRepo->findForCart($productId);
        if (!$p || empty($p['is_active'])) {
            return [false, 'Producto no disponible.'];
        }

        [$eligible,$reason] = (new \App\Services\Checkout\CheckoutProductEligibilityGuard($this->db))
            ->checkItems([['product_id'=>$productId,'variant_id'=>$variantId ?? 0,'qty'=>$qty]]);
        if (!$eligible) return [false,$reason];

        $variantId = (int)($variantId ?? 0);
        $variantId = max(0, $variantId);
        $variantId = ($variantId > 0) ? $variantId : null;
        if ($variantId > 0) {
            // Etapa A: validación backend (la UI aún no envía variantes).
            if (!$this->variantRepo->belongsToProduct($variantId, $productId)) {
                return [false, 'Variante inválida.'];
            }
        }

        // Regla: si stock == 0, no se puede agregar al carrito.
        // - Variantes: stock en inventory_variants
        // - Simples: stock en inventory
        $available = null;
        if ($variantId !== null) {
            $available = $this->invRepo->getVariantStock((int)$variantId);
        } else {
            $available = $this->invRepo->getProductStock($productId);
        }

        // Si conocemos el stock, aplicamos la regla dura.
        $msg = 'Producto agregado al carrito.';
        if ($available !== null) {
            $available = max(0, (int)$available);
            if ($available <= 0) {
                return [false, 'Sin stock.'];
            }
            $currentQty = $this->currentItemQuantity($productId, $variantId);
            $remaining = max(0, $available - $currentQty);
            if ($remaining <= 0) {
                return [false, 'Ya alcanzaste el stock disponible para este producto.'];
            }
            if ($qty > $remaining) {
                $qty = $remaining;
                $msg = ($variantId !== null)
                    ? ('Solo quedan ' . $available . ' disponibles de esta opción. Ajustamos la cantidad.')
                    : ('Solo quedan ' . $available . ' disponibles. Ajustamos la cantidad.');
            }
        }

        $this->cartRepo->upsertItem($this->cartId, $productId, $variantId, $qty);
        $this->recount();
        return [true, $msg];
    }

    /**
     * Actualiza cantidades.
     * Formato esperado de keys:
     * - "{productId}:{variantId}" (preferido)
     * - "{productId}" (legacy, asume variant_id=0)
     * @param array<string,string|int> $qtys
     * @return array{0:bool,1:string}
     */
    public function bulkUpdate(array $qtys): array
    {
        $changed = false;
        $hadAdjust = false;
        $hadNoStock = false;
        $hadUnavailable = false;
        foreach ($qtys as $pidStr => $qtyRaw) {
            $key = trim((string)$pidStr);
            if ($key === '') continue;

            $pid = 0;
            $vid = 0;
            if (strpos($key, ':') !== false) {
                [$a, $b] = array_pad(explode(':', $key, 2), 2, '0');
                $pid = (int)$a;
                $vid = (int)$b;
            } else {
                $pid = (int)$key;
                $vid = 0;
            }
            if ($pid <= 0) continue;
            $qty = (int)$qtyRaw;
            $qty = max(0, min(999, $qty));

            $vidNorm = ($vid > 0) ? $vid : null;
            if ($qty === 0) {
                $this->cartRepo->setQuantity($this->cartId, $pid, $vidNorm, 0);
                $changed = true;
                continue;
            }
            [$eligible] = (new \App\Services\Checkout\CheckoutProductEligibilityGuard($this->db))
                ->checkItems([['product_id'=>$pid,'variant_id'=>$vid,'qty'=>$qty]]);
            if (!$eligible) {
                $hadUnavailable = true;
                continue;
            }
            $product = $this->productRepo->findForCart($pid);
            if (!$product || empty($product['is_active'])) {
                $hadUnavailable = true;
                continue;
            }

            // stock (si está disponible). Para variantes, stock vive en inventory_variants; para simples, en inventory.
$available = null;
if ($vidNorm !== null) {
    $available = $this->invRepo->getVariantStock($vidNorm);
} else {
    $available = $this->invRepo->getProductStock($pid);
}

// si hay stock conocido: clamp
if ($available !== null) {
    $available = max(0, (int)$available);
    if ($available <= 0) {
        $hadNoStock = true;
        continue;
    }
    if ($qty > $available) {
        $qty = $available;
        $hadAdjust = true;
    }
}

// si qty==0 borramos
$this->cartRepo->setQuantity($this->cartId, $pid, $vidNorm, $qty);
$changed = true;

        }

        if ($changed) {
            $this->recount();
            if ($hadUnavailable) {
                return [true, 'Algunos productos ya no están disponibles y no pudimos actualizar esas cantidades.'];
            }
            return [true, ($hadNoStock ? 'Hay productos sin stock y no pudimos actualizar esas cantidades.' : ($hadAdjust ? 'Ajustamos algunas cantidades al stock disponible.' : 'Carrito actualizado.'))];
        }
        if ($hadUnavailable) {
            return [true, 'Algunos productos ya no están disponibles y no pudimos actualizar esas cantidades.'];
        }
        if ($hadNoStock) {
            return [true, 'Hay productos sin stock y no pudimos actualizar esas cantidades.'];
        }
        return [true, 'Sin cambios.'];
    }

    public function remove(int $productId, ?int $variantId = null): bool
    {
        if ($productId <= 0) return false;
        $variantId = (int)($variantId ?? 0);
        $variantId = max(0, $variantId);
        $variantId = ($variantId > 0) ? $variantId : null;
        if (!$this->cartRepo->hasItem($this->cartId, $productId, $variantId)) {
            return false;
        }
        $this->cartRepo->deleteItem($this->cartId, $productId, $variantId);
        $this->recount();
        return true;
    }

    public function clear(): void
    {
        $this->cartRepo->clearItems($this->cartId);
        $this->recount();
    }

    public function hasItems(): bool
    {
        return $this->cartRepo->countQty($this->cartId) > 0;
    }

    /** @param array<int,array{product_id:int,variant_id?:int,quantity:int}> $items */
    public function mergeItems(array $items): int
    {
        $added = 0;
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $vid = (int)($it['variant_id'] ?? 0);
            $qty = (int)($it['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0) continue;
            [$ok, $_] = $this->add($pid, $qty, ($vid > 0 ? $vid : null));
            if ($ok) $added++;
        }
        return $added;
    }

    /** @param array<int,array{product_id:int,variant_id?:int,quantity:int}> $items */
    public function replaceWithItems(array $items): int
    {
        $this->clear();
        return $this->mergeItems($items);
    }

    /**
     * Devuelve carrito para views (items + subtotal).
     * @return array{items:array<int,array<string,mixed>>, subtotal:int, total_qty:int}
     */
    public function getCartView(): array
    {
        $rawItems = $this->cartRepo->items($this->cartId);
        if (!$rawItems) return ['items'=>[], 'subtotal'=>0, 'total_qty'=>0];

        $productIds = [];
        $variantIds = [];
        foreach ($rawItems as $it) {
            $productIds[] = (int)$it['product_id'];

            $vid = (int)($it['variant_id'] ?? 0);
            $vidNorm = ($vid > 0) ? $vid : null;
            if ($vidNorm !== null) {
                $variantIds[] = $vidNorm;
            }
        }
        $productIds = array_values(array_unique(array_filter($productIds, fn($v)=>$v>0)));
        $variantIds = array_values(array_unique(array_filter($variantIds, fn($v)=>$v>0)));

        // Productos base (thumb general + stock base)
        $products = $this->productRepo->findManyForCart($productIds);
        $pmap = [];
        foreach ($products as $p) {
            $pmap[(int)$p['id']] = $p;
        }

        // B1 no elimina líneas existentes: solo informa elegibilidad pública.
        $b1Allowed = (new \App\Services\Checkout\CheckoutProductEligibilityGuard($this->db))
            ->eligibleProductIds($rawItems);

        // Variantes (precio/sku/stock + label)
        $vmap = $this->fetchVariantsForCart($variantIds);
        $labels = $this->fetchVariantLabels($variantIds);
        $attrsMap = $this->fetchVariantAttributes($variantIds);
        $vthumb = $this->fetchVariantThumbs($variantIds);

        $items = [];
        $subtotal = 0;
        $totalQty = 0;

        foreach ($rawItems as $it) {
            $pid = (int)$it['product_id'];

	            $vidRaw = (int)($it['variant_id'] ?? 0);
	            $vidNorm = ($vidRaw > 0) ? $vidRaw : null;

	            $qty = max(0, (int)$it['quantity']);
            if ($qty <= 0) continue;

            $p = $pmap[$pid] ?? null;
            if (!$p) {
                // Limpieza: producto borrado
                $this->cartRepo->deleteItem($this->cartId, $pid, $vidNorm);
                continue;
            }

            $price = (int)($p['price'] ?? 0); // público (base)
            $listPrice = (int)($p['list_price'] ?? 0);
            $variantRow = null;
            $sku = (string)($p['sku'] ?? '');
            $stock = (int)($p['stock'] ?? 0);
            $thumb = (string)($p['thumb'] ?? asset('img/placeholder.jpg'));
            $variantLabel = '';
            $variantTitle = null;
            $variantAttrs = null;
            $variantSku = null;

	            if ($vidNorm !== null) {
	                $v = $vmap[$vidNorm] ?? null;
                if (!$v || (int)($v['product_id'] ?? 0) !== $pid) {
                    // Limpieza: variante inválida
                    $this->cartRepo->deleteItem($this->cartId, $pid, $vidNorm);
                    continue;
                }

                $price = (int)($v['price'] ?? $price); // público (base)
                $listPrice = (int)($v['list_price'] ?? $listPrice);
                $variantRow = $v;
                $sku = (string)($v['sku'] ?? $sku);
                $stock = (int)($v['stock'] ?? 0);
                $variantLabel = (string)($labels[$vidNorm] ?? ($v['title'] ?? ''));
                $variantTitle = $variantLabel !== '' ? $variantLabel : ((string)($v['title'] ?? ''));
                $variantSku = (string)($v['sku'] ?? '');
                $variantAttrs = $attrsMap[$vidNorm] ?? null;
	                if (!empty($vthumb[$vidNorm])) {
	                    $thumb = (string)$vthumb[$vidNorm];
                }
            }

            // Precio final según rol (público/mayorista)
            $unit = (int)$this->priceService->getUnitPriceForUser($p, $variantRow, strtolower(trim($this->userRole)));
            $price = $unit;

            $line = $price * $qty;

            $items[] = [
	                'key' => $pid . ':' . ($vidNorm ?? 0),
	                // Back-compat: algunas vistas usan "id" como product_id.
	                'id' => $pid,
	                'product_id' => $pid,
	                'variant_id' => $vidNorm,
                'variant_label' => $variantLabel,
                // snapshots para pedido/checkout intent
                'variant_sku' => $variantSku,
                'variant_title' => $variantTitle,
                'variant_attributes_json' => $variantAttrs,
                'sku' => $sku,
                'slug' => $p['slug'] ?? '',
                'name' => $p['name'] ?? '',
                'brand_name' => $p['brand_name'] ?? '',
                'short_desc' => $p['short_desc'] ?? '',
                'thumb' => $thumb,
                'price' => $price,
                'list_price' => $listPrice,
                'qty' => $qty,
                'stock' => $stock,
                'line_total' => $line,
                'is_active' => (int)($p['is_active'] ?? 1),
                'is_publicly_available' => isset($b1Allowed[$pid]),
                'public_unavailable_reason' => isset($b1Allowed[$pid])
                    ? '' : 'Producto no disponible por la configuración actual del catálogo.',
            ];
            $subtotal += $line;
            $totalQty += $qty;
        }

        $this->recount();
        return ['items'=>$items, 'subtotal'=>$subtotal, 'total_qty'=>$totalQty];
    }

    /** @return array<int,array<string,mixed>> map variant_id => row */
    private function fetchVariantsForCart(array $variantIds): array
    {
        if (!$variantIds) return [];

        $ph = implode(',', array_fill(0, count($variantIds), '?'));
        $cols = "pv.id, pv.product_id, pv.sku, pv.title, pv.price, pv.list_price";
        if (function_exists('db_has_column') && db_has_column('product_variants','price_wh1_override')) $cols .= ", pv.price_wh1_override";
        if (function_exists('db_has_column') && db_has_column('product_variants','price_wh2_override')) $cols .= ", pv.price_wh2_override";

        $sql = "SELECT {$cols}, COALESCE(iv.stock,0) AS stock
                  FROM product_variants pv
                  LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
                 WHERE pv.id IN ($ph) AND pv.is_active = 1";
        $st = $this->db->prepare($sql);
        $st->execute($variantIds);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['id']] = $r;
        }
        return $out;
    }

    /** @return array<int,string> map variant_id => "Color: Azul · Tamaño: 6.6\"" */
    private function fetchVariantLabels(array $variantIds): array
    {
        if (!$variantIds) return [];

        $ph = implode(',', array_fill(0, count($variantIds), '?'));
        $sql = "SELECT pvv.variant_id,
                       a.id   AS attr_id,
                       a.name AS attr_name,
                       COALESCE(av.value, '') AS val
                  FROM product_variant_values pvv
                  INNER JOIN attributes a ON a.id = pvv.attribute_id
                  INNER JOIN attribute_values av ON av.id = pvv.value_id
                 WHERE pvv.variant_id IN ($ph)
                 ORDER BY pvv.variant_id ASC, a.id ASC";
        $st = $this->db->prepare($sql);
        $st->execute($variantIds);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $acc = [];
        foreach ($rows as $r) {
            $vid = (int)($r['variant_id'] ?? 0);
            if ($vid <= 0) continue;
            $attr = trim((string)($r['attr_name'] ?? ''));
            $val  = trim((string)($r['val'] ?? ''));
            if ($val === '') continue;
            if (!isset($acc[$vid])) $acc[$vid] = [];

            // Si tenemos nombre de atributo, devolvemos "Color: Azul". Si no, caemos a solo el valor.
            $acc[$vid][] = ($attr !== '') ? ($attr . ': ' . $val) : $val;
        }

        $out = [];
        foreach ($acc as $vid => $parts) {
            $parts = array_values(array_filter($parts, fn($x) => trim((string)$x) !== ''));
            if (!$parts) continue;
            $out[(int)$vid] = implode(' · ', $parts);
        }

        return $out;
    }

    /**
     * Snapshot estructurado de atributos para order_items / checkout_intents.
     * @return array<int, array<int, array{attr:string,val:string}>> map variant_id => [{attr,val}, ...]
     */
    private function fetchVariantAttributes(array $variantIds): array
    {
        if (!$variantIds) return [];

        $ph = implode(',', array_fill(0, count($variantIds), '?'));
        $sql = "SELECT pvv.variant_id,
                       COALESCE(a.name, '') AS attr_name,
                       COALESCE(av.value, '') AS val
                  FROM product_variant_values pvv
                  INNER JOIN attributes a ON a.id = pvv.attribute_id
                  INNER JOIN attribute_values av ON av.id = pvv.value_id
                 WHERE pvv.variant_id IN ($ph)
                 ORDER BY pvv.variant_id ASC, a.id ASC";
        $st = $this->db->prepare($sql);
        $st->execute($variantIds);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $vid = (int)($r['variant_id'] ?? 0);
            if ($vid <= 0) continue;
            $attr = trim((string)($r['attr_name'] ?? ''));
            $val  = trim((string)($r['val'] ?? ''));
            if ($val === '') continue;
            if (!isset($out[$vid])) $out[$vid] = [];
            $out[$vid][] = ['attr' => $attr !== '' ? $attr : 'Opción', 'val' => $val];
        }
        return $out;
    }

    /** @return array<int,string> map variant_id => public_url(path) */
    private function fetchVariantThumbs(array $variantIds): array
    {
        if (!$variantIds) return [];
        $out = [];

        // Tomamos 1ra imagen por variante (cover > sort > id)
        $ph = implode(',', array_fill(0, count($variantIds), '?'));
        $sql = "SELECT pi.variant_id,
                       (SELECT pi2.path
                          FROM product_images pi2
                         WHERE pi2.variant_id = pi.variant_id
                         ORDER BY pi2.is_cover DESC, pi2.sort ASC, pi2.id ASC
                         LIMIT 1) AS path
                  FROM product_images pi
                 WHERE pi.variant_id IN ($ph)
                 GROUP BY pi.variant_id";
        $st = $this->db->prepare($sql);
        $st->execute($variantIds);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as $r) {
            $vid = (int)($r['variant_id'] ?? 0);
            $path = (string)($r['path'] ?? '');
            if ($vid <= 0 || $path === '') continue;
            $out[$vid] = function_exists('public_url') ? public_url($path) : asset($path);
        }
        return $out;
    }

    private function recount(): void
    {
        $count = $this->cartRepo->countQty($this->cartId);
        $_SESSION['cart_count'] = $count;
    }
}

