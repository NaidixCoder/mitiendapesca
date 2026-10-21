<?php
namespace App\Services\Marketing;

use App\Repositories\Marketing\MarketingCampaignRepository;
use App\Repositories\Marketing\MarketingCampaignHitRepository;
use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class MarketingCampaignService
{
    private MarketingCampaignRepository $campaigns;
    private MarketingCampaignHitRepository $hits;
    private AppSettingsRepository $settings;

    public function __construct(private PDO $db)
    {
        $this->campaigns = new MarketingCampaignRepository($db);
        $this->hits = new MarketingCampaignHitRepository($db);
        $this->settings = new AppSettingsRepository($db);
    }

    /**
     * Atribución global: first_touch (default) o last_touch.
     * Fuente: app_settings[marketing.attribution_mode] o ENV MARKETING_ATTRIBUTION.
     */
    public function attributionMode(): string
    {
        $mode = strtolower(trim((string)$this->settings->get('marketing.attribution_mode', '')));
        if ($mode === '') {
            $mode = strtolower(trim((string)env('MARKETING_ATTRIBUTION', 'first_touch')));
        }
        if (!in_array($mode, ['first_touch','last_touch'], true)) {
            $mode = 'first_touch';
        }
        return $mode;
    }

    public function findActiveBySlug(string $slug): ?array
    {
        $row = $this->campaigns->findBySlug($slug);
        if (!$row) return null;
        if (empty($row['is_active'])) return null;
        return $row;
    }

    /**
     * Evita contaminar métricas con tráfico de admin/dev.
     *
     * Regla:
     * - Admin logueado: por defecto NO trackea.
     * - Click desde /admin (referrer): por defecto NO trackea.
     *
     * Overrides (ENV):
     * - MARKETING_INCLUDE_ADMIN_TRAFFIC=1  => trackea también admin.
     */
    public function shouldTrackMarketingTraffic(): bool
    {
        try {
            $allowAdmin = (string)env('MARKETING_INCLUDE_ADMIN_TRAFFIC', '0') === '1';
            if (!$allowAdmin && function_exists('is_admin') && is_admin()) {
                return false;
            }

            $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
            if (!$allowAdmin && $ref !== '' && preg_match('#/admin(/|\?|$)#i', $ref)) {
                return false;
            }
        } catch (\Throwable $e) {
            // si falla algo, preferimos trackear antes que romper
        }

        return true;
    }

    public function registerHit(int $campaignId): void
    {
        if (!$this->shouldTrackMarketingTraffic()) {
            return;
        }
        $this->hits->insert(
            $campaignId,
            $this->sessionId(),
            (string)($_SERVER['HTTP_REFERER'] ?? ''),
            $this->ipHash(),
            $this->uaHash()
        );
    }

    /**
     * destination_path es una ruta de app (ej: /productos?categoria=...) o una URL absoluta.
     * Acá le agregamos UTMs definidos en la campaña (si no vienen ya en la URL).
     */
    public function buildDestinationUrl(array $campaign): string
    {
        $dest = trim((string)($campaign['destination_path'] ?? ''));
        if ($dest === '') return '/';

        $utms = [
            'utm_source' => trim((string)($campaign['utm_source'] ?? '')),
            'utm_medium' => trim((string)($campaign['utm_medium'] ?? '')),
            'utm_campaign' => trim((string)($campaign['utm_campaign'] ?? '')),
            'utm_content' => trim((string)($campaign['utm_content'] ?? '')),
            'utm_term' => trim((string)($campaign['utm_term'] ?? '')),
        ];
        foreach ($utms as $k => $v) {
            if ($v === '') unset($utms[$k]);
        }

        // Si es URL absoluta, la tratamos igual (parse_url soporta esquema).
        $parts = parse_url($dest);
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';

        $q = [];
        if (is_string($query) && $query !== '') {
            parse_str($query, $q);
        }
        foreach ($utms as $k => $v) {
            if (!isset($q[$k]) || trim((string)$q[$k]) === '') {
                $q[$k] = $v;
            }
        }

        $newQuery = http_build_query($q);

        // Reconstrucción
        $rebuilt = '';
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            $rebuilt .= $parts['scheme'] . '://' . $parts['host'];
            if (!empty($parts['port'])) $rebuilt .= ':' . $parts['port'];
        }

        if ($path !== '') {
            $rebuilt .= $path;
        } else {
            $rebuilt .= $dest; // fallback raro
        }

        if ($newQuery !== '') {
            $rebuilt .= '?' . $newQuery;
        }

        if (!empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
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

    private function ipHash(): ?string
    {
        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return $ip !== '' ? hash('sha256', $ip) : null;
    }

    private function uaHash(): ?string
    {
        $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        return $ua !== '' ? hash('sha256', $ua) : null;
    }
}
