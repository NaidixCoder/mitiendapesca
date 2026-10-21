<?php
namespace App\Services\Admin\Dashboard;

use PDO;
use App\Repositories\Analytics\AnalyticsReportRepository;
use App\Services\Analytics\AnalyticsNavInsightsService;

final class DashboardNavigationAnalyticsService
{
    /**
     * @return array<string,mixed>
     */
    public function collect(PDO $db, int $days = 30): array
    {
        $navDays = in_array($days, [7, 30, 90], true) ? $days : 30;
        $navTopPages = [];
        $navTopProducts = [];
        $navTopAddToCart = [];
        $navTopViewToCart = [];
        $navFunnel = ["checkout_views" => 0, "purchases" => 0, "conversion" => 0.0];
        $navLandingPages = [];
        $navTopSources = [];

        $arep = null;
        try {
            $arep = new AnalyticsReportRepository($db);
        } catch (\Throwable $e) {
            $arep = null;
        }

        if ($arep) {
            try { $navTopPages = $arep->topPages($navDays, 8); } catch (\Throwable $e) { $navTopPages = []; }
            try { $navTopProducts = $arep->topProducts($navDays, 8); } catch (\Throwable $e) { $navTopProducts = []; }
            try { $navTopAddToCart = $arep->topAddToCart($navDays, 8); } catch (\Throwable $e) { $navTopAddToCart = []; }
            try { $navTopViewToCart = $arep->topProductsViewToCart($navDays, 8); } catch (\Throwable $e) { $navTopViewToCart = []; }
            try { $navFunnel = $arep->funnelMini($navDays); } catch (\Throwable $e) { $navFunnel = ["checkout_views" => 0, "purchases" => 0, "conversion" => 0.0]; }

            $slugs = [];
            foreach ($navTopPages as $r) {
                $path = (string)($r['path'] ?? '');
                if ($path === '') {
                    continue;
                }
                if (str_starts_with($path, '/producto') && str_contains($path, 'slug=')) {
                    if (preg_match('~slug=([^&]+)~', $path, $m)) {
                        $slug = urldecode((string)$m[1]);
                        if ($slug !== '') {
                            $slugs[] = $slug;
                        }
                    }
                }
            }

            $slugs = array_values(array_unique($slugs));
            $bySlug = [];
            if (!empty($slugs)) {
                try {
                    $in = implode(',', array_fill(0, count($slugs), '?'));
                    $st = $db->prepare("SELECT slug, name FROM products WHERE slug IN ($in)");
                    $st->execute($slugs);
                    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $rr) {
                        $s = (string)($rr['slug'] ?? '');
                        $n = (string)($rr['name'] ?? '');
                        if ($s !== '' && $n !== '') {
                            $bySlug[$s] = $n;
                        }
                    }
                } catch (\Throwable $e) {
                    $bySlug = [];
                }
            }

            if (!empty($bySlug)) {
                foreach ($navTopPages as &$r) {
                    $path = (string)($r['path'] ?? '');
                    if ($path === '') {
                        continue;
                    }
                    if (str_starts_with($path, '/producto') && preg_match('~slug=([^&]+)~', $path, $m)) {
                        $slug = urldecode((string)$m[1]);
                        if ($slug !== '' && isset($bySlug[$slug])) {
                            $r['label'] = 'Producto: ' . $bySlug[$slug];
                        }
                    }
                }
                unset($r);
            }
        }

        try {
            $ins = new AnalyticsNavInsightsService($db);
            $landingPack = $ins->buildLandingAndSources($navDays, 8, 5000);
            $navLandingPages = is_array($landingPack['landing_pages'] ?? null) ? $landingPack['landing_pages'] : [];
            $navTopSources = is_array($landingPack['sources'] ?? null) ? $landingPack['sources'] : [];
        } catch (\Throwable $e) {
            $navLandingPages = [];
            $navTopSources = [];
        }

        $partialsViewModel = new DashboardPartialsViewModelService();
        $navigationAnalytics = $partialsViewModel->prepareNavigation([
            'navDays' => $navDays,
            'navTopPages' => $navTopPages,
            'navLandingPages' => $navLandingPages,
            'navTopSources' => $navTopSources,
            'navTopProducts' => $navTopProducts,
            'navTopAddToCart' => $navTopAddToCart,
            'navTopViewToCart' => $navTopViewToCart,
            'navFunnel' => $navFunnel,
        ]);

        return [
            'navDays' => $navDays,
            'navTopPages' => $navTopPages,
            'navLandingPages' => $navLandingPages,
            'navTopSources' => $navTopSources,
            'navTopProducts' => $navTopProducts,
            'navTopAddToCart' => $navTopAddToCart,
            'navTopViewToCart' => $navTopViewToCart,
            'navFunnel' => $navFunnel,
            'navigationAnalytics' => $navigationAnalytics,
        ];
    }
}
