<?php
namespace App\Services\Analytics;

use App\Repositories\Analytics\AnalyticsReportRepository;
use PDO;

/**
 * Reportes "livianos" para el Dashboard (P2).
 *
 * Regla: SQL en Repositories, agregación/parseo en Service, Controller solo orquesta.
 */
final class AnalyticsNavInsightsService
{
    private AnalyticsReportRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new AnalyticsReportRepository($db);
    }

    /**
     * @return array{landing_pages:array<int,array<string,mixed>>,sources:array<int,array<string,mixed>>}
     */
    public function buildLandingAndSources(int $days, int $limit = 8, int $maxSessions = 5000): array
    {
        $days = max(1, min(365, (int)$days));
        $limit = max(1, min(50, (int)$limit));
        $maxSessions = max(100, min(50000, (int)$maxSessions));

        // Landing pages: primer page_view por sesión
        $landingEvents = $this->repo->landingEvents($days, $maxSessions);

        // Fuentes: primer page_view con utm_* (si existe) o primer page_view de la sesión.
        $sourceEvents = $this->repo->sourceAttributionEvents($days, $maxSessions);

        // 1) Landing pages (limpiamos parámetros de tracking para no “explotar” la tabla)
        $byPage = [];
        foreach ($landingEvents as $e) {
            $rawPath = (string)($e['path'] ?? '');
            if ($rawPath === '') continue;

            $clean = $this->stripTrackingQueryParams($rawPath);
            if ($clean === '') continue;

            if (!isset($byPage[$clean])) {
                $byPage[$clean] = ['path' => $clean, 'sessions' => 0];
            }
            $byPage[$clean]['sessions']++;
        }
        $landingPages = array_values($byPage);
        usort($landingPages, fn($a,$b) => ((int)($b['sessions'] ?? 0)) <=> ((int)($a['sessions'] ?? 0)));
        $landingPages = array_slice($landingPages, 0, $limit);

        // 2) Fuentes (Referrer/UTM) a nivel sesión (landing)
        $bySource = [];
        foreach ($sourceEvents as $e) {
            $rawPath = (string)($e['path'] ?? '');
            $ref = (string)($e['referrer'] ?? '');

            // Preferimos UTMs guardadas en meta_json (canonical 301 puede haber removido la query)
            $metaUtm = ['utm_source'=>'','utm_medium'=>'','utm_campaign'=>''];
            try {
                $mj = (string)($e['meta_json'] ?? '');
                if ($mj !== '') {
                    $decoded = json_decode($mj, true);
                    if (is_array($decoded)) {
                        $metaUtm['utm_source'] = trim((string)($decoded['utm_source'] ?? ''));
                        $metaUtm['utm_medium'] = trim((string)($decoded['utm_medium'] ?? ''));
                        $metaUtm['utm_campaign'] = trim((string)($decoded['utm_campaign'] ?? ''));
                    }
                }
            } catch (\Throwable $ex) {
                // No bloqueante
            }

            $utm = $this->extractUtm($rawPath);
            // Si vino meta, pisa path
            foreach (['utm_source','utm_medium','utm_campaign'] as $k) {
                if ($metaUtm[$k] !== '') $utm[$k] = $metaUtm[$k];
            }
            $source = $utm['utm_source'] !== '' ? $utm['utm_source'] : $this->referrerHost($ref);
            $medium = $utm['utm_medium'] !== '' ? $utm['utm_medium'] : '';
            $campaign = $utm['utm_campaign'] !== '' ? $utm['utm_campaign'] : '';

            if ($source === '') $source = 'Directo';

            $key = strtolower($source.'|'.$medium.'|'.$campaign);
            if (!isset($bySource[$key])) {
                $bySource[$key] = [
                    'source' => $source,
                    'medium' => $medium,
                    'campaign' => $campaign,
                    'sessions' => 0,
                ];
            }
            $bySource[$key]['sessions']++;
        }
        $sources = array_values($bySource);
        usort($sources, fn($a,$b) => ((int)($b['sessions'] ?? 0)) <=> ((int)($a['sessions'] ?? 0)));
        $sources = array_slice($sources, 0, $limit);

        return [
            'landing_pages' => $landingPages,
            'sources' => $sources,
        ];
    }

    private function stripTrackingQueryParams(string $pathWithQuery): string
    {
        $pathWithQuery = trim($pathWithQuery);
        if ($pathWithQuery === '') return '';

        // parse_url necesita scheme para URLs absolutas; acá es path. Igual funciona.
        $parts = parse_url($pathWithQuery);
        $path = (string)($parts['path'] ?? '');
        $query = (string)($parts['query'] ?? '');
        if ($query === '') return $pathWithQuery; // sin query

        parse_str($query, $qs);
        if (!is_array($qs) || empty($qs)) return $pathWithQuery;

        $trackingKeys = [
            'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
            'gclid','fbclid','yclid','msclkid','igshid','mc_cid','mc_eid'
        ];
        foreach ($trackingKeys as $k) {
            if (array_key_exists($k, $qs)) unset($qs[$k]);
        }

        if (empty($qs)) return $path !== '' ? $path : $pathWithQuery;
        $newQuery = http_build_query($qs);
        return $path . ($newQuery !== '' ? ('?'.$newQuery) : '');
    }

    /** @return array{utm_source:string,utm_medium:string,utm_campaign:string} */
    private function extractUtm(string $pathWithQuery): array
    {
        $parts = parse_url((string)$pathWithQuery);
        $query = (string)($parts['query'] ?? '');
        if ($query === '') {
            return ['utm_source'=>'','utm_medium'=>'','utm_campaign'=>''];
        }
        parse_str($query, $qs);
        if (!is_array($qs)) {
            return ['utm_source'=>'','utm_medium'=>'','utm_campaign'=>''];
        }
        $src = trim((string)($qs['utm_source'] ?? ''));
        $med = trim((string)($qs['utm_medium'] ?? ''));
        $cam = trim((string)($qs['utm_campaign'] ?? ''));
        return ['utm_source'=>$src,'utm_medium'=>$med,'utm_campaign'=>$cam];
    }

    private function referrerHost(string $ref): string
    {
        $ref = trim($ref);
        if ($ref === '') return '';

        // parse_url con URLs sin scheme falla; forzamos https:// si es necesario.
        $try = $ref;
        if (!str_contains($try, '://')) {
            $try = 'https://' . ltrim($try, '/');
        }
        $parts = parse_url($try);
        $host = trim((string)($parts['host'] ?? ''));
        if ($host === '') return '';

        // Normalización básica
        $host = strtolower($host);
        if (str_starts_with($host, 'www.')) $host = substr($host, 4);
        return $host;
    }
}
