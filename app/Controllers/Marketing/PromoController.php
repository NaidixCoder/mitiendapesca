<?php
namespace App\Controllers\Marketing;

use App\Services\Marketing\MarketingCampaignService;
use Throwable;

final class PromoController
{
    public function redirect(): void
    {
        $slug = trim((string)($_GET['slug'] ?? ''));
        if ($slug === '') {
            http_response_code(404);
            render_page('errors/404', ['path' => '/promo', 'method' => 'GET']);
            return;
        }

        $db = db();
        $svc = new MarketingCampaignService($db);
        $campaign = $svc->findActiveBySlug($slug);
        if (!$campaign) {
            http_response_code(404);
            render_page('errors/404', ['path' => '/promo/'.$slug, 'method' => 'GET']);
            return;
        }

        // Atribución mid-session: este link setea la campaña en sesión según el modo global.
        // Nota: no trackeamos (ni seteamos campaña) para tráfico admin por defecto.
        try {
            $shouldTrack = $svc->shouldTrackMarketingTraffic();
            if ($shouldTrack && session_status() === PHP_SESSION_ACTIVE) {
                $incomingId = (int)($campaign['id'] ?? 0);
                $existingId = (int)($_SESSION['marketing_campaign_id'] ?? 0);

                $mode = $svc->attributionMode(); // first_touch | last_touch
                $shouldSet = true;
                if ($mode === 'first_touch' && $existingId > 0 && $incomingId > 0 && $existingId !== $incomingId) {
                    $shouldSet = false;
                }

                if ($shouldSet) {
                    // Campaña interna (para atribución de pedidos / conversión).
                    $_SESSION['marketing_campaign_id'] = $incomingId;

                    $src = trim((string)($campaign['utm_source'] ?? ''));
                    if ($src !== '') {
                        $_SESSION['analytics_utm'] = [
                            'utm_source' => $src,
                            'utm_medium' => trim((string)($campaign['utm_medium'] ?? '')),
                            'utm_campaign' => trim((string)($campaign['utm_campaign'] ?? '')),
                            'utm_content' => trim((string)($campaign['utm_content'] ?? '')),
                            'utm_term' => trim((string)($campaign['utm_term'] ?? '')),
                            'captured_at' => time(),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            // no bloqueante
        }

        try {
            $svc->registerHit((int)$campaign['id']);
        } catch (Throwable $e) {
            // No bloqueante
        }

        $dest = $svc->buildDestinationUrl($campaign);

        // Si el destino es ruta app, usamos url() para respetar subcarpeta.
        $isAbsolute = (bool)preg_match('#^https?://#i', $dest);
        $location = $isAbsolute ? $dest : (function_exists('url') ? url($dest) : $dest);

        header('Location: '.$location, true, 302);
        exit;
    }
}
