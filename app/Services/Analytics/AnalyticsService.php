<?php
namespace App\Services\Analytics;

use App\Repositories\Analytics\AnalyticsEventRepository;
use PDO;

final class AnalyticsService
{
    private AnalyticsEventRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new AnalyticsEventRepository($db);
    }

    public function trackPageView(string $path, string $referrer = '', array $meta = []): void
    {
        $path = trim($path);
        if ($path === '' || strlen($path) > 255) return;

        // Evitar duplicados de eventos “más ruidosos”
        if ($this->shouldIgnorePath($path)) return;

        // Si capturamos UTMs por sesión (canonical 301), las adjuntamos al primer page_view útil.
        try {
            if (!empty($_SESSION['analytics_utm']) && is_array($_SESSION['analytics_utm'])) {
                $utm = $_SESSION['analytics_utm'];
                $meta['utm_source'] = $meta['utm_source'] ?? trim((string)($utm['utm_source'] ?? ''));
                $meta['utm_medium'] = $meta['utm_medium'] ?? trim((string)($utm['utm_medium'] ?? ''));
                $meta['utm_campaign'] = $meta['utm_campaign'] ?? trim((string)($utm['utm_campaign'] ?? ''));
                $meta['utm_content'] = $meta['utm_content'] ?? trim((string)($utm['utm_content'] ?? ''));
                $meta['utm_term'] = $meta['utm_term'] ?? trim((string)($utm['utm_term'] ?? ''));
                // Limpieza rápida
                foreach (['utm_source','utm_medium','utm_campaign','utm_content','utm_term'] as $k) {
                    if (isset($meta[$k]) && trim((string)$meta[$k]) === '') unset($meta[$k]);
                }
                // Consumimos la campaña para evitar repetir en todas las pages.
                unset($_SESSION['analytics_utm']);
            }
        } catch (\Throwable $e) {
            // No bloqueante
        }

        $metaJson = null;
        if (!empty($meta)) {
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $this->repo->insert([
            'event_key' => 'page_view',
            'path' => $path,
            'referrer' => $this->sanitizeReferrer($referrer),
            'marketing_campaign_id' => $this->marketingCampaignId(),
            'user_id' => (int)($_SESSION['uid'] ?? 0),
            'session_id' => $this->sessionId(),
            'cart_token_hash' => $this->cartTokenHash(),
            'ip_hash' => $this->ipHash(),
            'ua_hash' => $this->uaHash(),
            'meta_json' => $metaJson,
        ]);
    }

    public function trackProductView(int $productId): void
    {
        $productId = (int)$productId;
        if ($productId <= 0) return;

        $this->repo->insert([
            'event_key' => 'product_view',
            'path' => null,
            'referrer' => $this->sanitizeReferrer((string)($_SERVER['HTTP_REFERER'] ?? '')),
            'marketing_campaign_id' => $this->marketingCampaignId(),
            'user_id' => (int)($_SESSION['uid'] ?? 0),
            'session_id' => $this->sessionId(),
            'cart_token_hash' => $this->cartTokenHash(),
            'ip_hash' => $this->ipHash(),
            'ua_hash' => $this->uaHash(),
            'product_id' => $productId,
            'variant_id' => null,
            'meta_json' => null,
        ]);
    }

    public function trackAddToCart(int $productId, int $variantId, int $qty): void
    {
        $productId = (int)$productId;
        if ($productId <= 0) return;

        $qty = max(1, (int)$qty);
        $variantId = (int)$variantId;

        $meta = [
            'qty' => $qty,
            'variant_id' => $variantId > 0 ? $variantId : null,
        ];

        $this->repo->insert([
            'event_key' => 'add_to_cart',
            'path' => null,
            'referrer' => $this->sanitizeReferrer((string)($_SERVER['HTTP_REFERER'] ?? '')),
            'marketing_campaign_id' => $this->marketingCampaignId(),
            'user_id' => (int)($_SESSION['uid'] ?? 0),
            'session_id' => $this->sessionId(),
            'cart_token_hash' => $this->cartTokenHash(),
            'ip_hash' => $this->ipHash(),
            'ua_hash' => $this->uaHash(),
            'product_id' => $productId,
            'variant_id' => $variantId > 0 ? $variantId : null,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function trackCheckoutView(): void
    {
        $this->repo->insert([
            'event_key' => 'checkout_view',
            'path' => null,
            'referrer' => $this->sanitizeReferrer((string)($_SERVER['HTTP_REFERER'] ?? '')),
            'marketing_campaign_id' => $this->marketingCampaignId(),
            'user_id' => (int)($_SESSION['uid'] ?? 0),
            'session_id' => $this->sessionId(),
            'cart_token_hash' => $this->cartTokenHash(),
            'ip_hash' => $this->ipHash(),
            'ua_hash' => $this->uaHash(),
            'meta_json' => null,
        ]);
    }

    public function trackPurchase(int $orderId, string $provider = '', int $amountTotal = 0): void
    {
        $orderId = (int)$orderId;
        if ($orderId <= 0) return;

        $meta = [
            'provider' => $provider !== '' ? $provider : null,
            'amount_total' => $amountTotal > 0 ? $amountTotal : null,
        ];

        // Dedupe: hay un UNIQUE (event_key, order_id) en DB. Usamos INSERT IGNORE.
        $this->repo->insert([
            'event_key' => 'purchase',
            'path' => null,
            'referrer' => $this->sanitizeReferrer((string)($_SERVER['HTTP_REFERER'] ?? '')),
            'marketing_campaign_id' => $this->marketingCampaignId(),
            'user_id' => (int)($_SESSION['uid'] ?? 0),
            'session_id' => $this->sessionId(),
            'cart_token_hash' => $this->cartTokenHash(),
            'ip_hash' => $this->ipHash(),
            'ua_hash' => $this->uaHash(),
            'order_id' => $orderId,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ], true);
    }

    private function marketingCampaignId(): int
    {
        return (int)($_SESSION['marketing_campaign_id'] ?? 0);
    }

    private function shouldIgnorePath(string $path): bool
    {
        // Admin + endpoints técnicos
        if ($path === '/admin' || str_starts_with($path, '/admin/')) return true;
        if (str_starts_with($path, '/webhooks/')) return true;
        if (str_starts_with($path, '/assets/')) return true;
        if (str_starts_with($path, '/uploads/')) return true;
if (str_starts_with($path, '/promo')) return true;

        // Páginas de producto se registran con product_view (para evitar doble conteo)
        if ($path === '/producto' || str_starts_with($path, '/producto/')) return true;

        return false;
    }

    private function sanitizeReferrer(string $ref): ?string
    {
        $ref = trim($ref);
        if ($ref === '') return null;
        if (strlen($ref) > 255) $ref = substr($ref, 0, 255);
        return $ref;
    }

    private function sessionId(): ?string
    {
        try {
            $sid = session_id();
            $sid = trim((string)$sid);
            return $sid !== '' ? $sid : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cartTokenHash(): ?string
    {
        $t = (string)($_COOKIE['cart_token'] ?? '');
        $t = trim($t);
        if ($t === '') return null;
        return hash('sha256', $t);
    }

    private function ipHash(): ?string
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ip = trim($ip);
        if ($ip === '') return null;
        return hash('sha256', $ip);
    }

    private function uaHash(): ?string
    {
        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        $ua = trim($ua);
        if ($ua === '') return null;
        return hash('sha256', $ua);
    }
}
