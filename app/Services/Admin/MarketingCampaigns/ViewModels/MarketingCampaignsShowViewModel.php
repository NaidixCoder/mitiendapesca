<?php
namespace App\Services\Admin\MarketingCampaigns\ViewModels;

final class MarketingCampaignsShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $campaign = is_array($input['campaign'] ?? null) ? $input['campaign'] : [];
        $report = is_array($input['report'] ?? null) ? $input['report'] : [];
        $series = is_array($input['series'] ?? null) ? $input['series'] : [];
        $recentHits = is_array($input['recentHits'] ?? null) ? $input['recentHits'] : [];
        $recentOrders = is_array($input['recentOrders'] ?? null) ? $input['recentOrders'] : [];
        $topPages = is_array($input['topPages'] ?? null) ? $input['topPages'] : [];
        $topProductsViewed = is_array($input['topProductsViewed'] ?? null) ? $input['topProductsViewed'] : [];
        $topProductsAdded = is_array($input['topProductsAdded'] ?? null) ? $input['topProductsAdded'] : [];
        $days = (int)($input['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 30;
        }

        $campaignId = (int)($campaign['id'] ?? 0);
        $campaignName = (string)($campaign['name'] ?? 'Campaña');
        $campaignSlug = (string)($campaign['slug'] ?? '');
        $destinationPath = (string)($campaign['destination_path'] ?? '');
        $utmCampaign = (string)($campaign['utm_campaign'] ?? '');
        $notes = (string)($campaign['notes'] ?? '');
        $isActive = ((int)($campaign['is_active'] ?? 0) === 1);

        $promoPath = '/promo/' . $campaignSlug;
        $promoUrl = url($promoPath);

        $hits = (int)($report['hits'] ?? 0);
        $newUsers = (int)($report['new_users'] ?? 0);
        $addToCart = (int)($report['add_to_cart'] ?? 0);
        $purchases = (int)($report['purchases'] ?? 0);
        $revenue = (int)($report['revenue'] ?? 0);
        $ratioNew = (float)($report['ratio_new_users'] ?? 0);
        $ratioATC = (float)($report['ratio_add_to_cart'] ?? 0);
        $ratioBuy = (float)($report['ratio_purchases'] ?? 0);

        $seriesPrepared = [];
        foreach ($series as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rowHits = (int)($row['hits'] ?? 0);
            $rowNew = (int)($row['new_users'] ?? 0);
            $rowAtc = (int)($row['add_to_cart'] ?? 0);
            $rowPurchases = (int)($row['purchases'] ?? 0);
            $rowRevenue = (int)($row['revenue'] ?? 0);
            $seriesPrepared[] = [
                'date' => (string)($row['date'] ?? ''),
                'hits_fmt' => number_format($rowHits, 0, ',', '.'),
                'new_users_fmt' => number_format($rowNew, 0, ',', '.'),
                'add_to_cart_fmt' => number_format($rowAtc, 0, ',', '.'),
                'purchases_fmt' => number_format($rowPurchases, 0, ',', '.'),
                'revenue_fmt' => number_format($rowRevenue, 0, ',', '.'),
            ];
        }

        $hitsPrepared = [];
        foreach ($recentHits as $h) {
            if (!is_array($h)) {
                continue;
            }
            $hitsPrepared[] = [
                'created_at' => (string)($h['created_at'] ?? ''),
                'referrer' => $this->referrerUi((string)($h['referrer'] ?? '')),
            ];
        }

        $recentOrdersPrepared = [];
        foreach ($recentOrders as $order) {
            if (!is_array($order)) {
                continue;
            }
            $orderId = (int)($order['id'] ?? 0);
            $total = (int)($order['total'] ?? 0);
            $recentOrdersPrepared[] = [
                'id' => $orderId,
                'created_at' => (string)($order['created_at'] ?? ''),
                'customer_name' => (string)($order['shipping_full_name'] ?? '—'),
                'total_fmt' => number_format($total, 0, ',', '.'),
                'show_url' => url('/admin/orders/show?id=' . $orderId),
            ];
        }

        $topPagesPrepared = [];
        foreach ($topPages as $row) {
            if (!is_array($row)) {
                continue;
            }
            $path = (string)($row['path'] ?? '');
            $views = (int)($row['views'] ?? 0);
            $isLink = ($path !== '' && str_starts_with($path, '/'));
            $topPagesPrepared[] = [
                'path' => $path,
                'views_fmt' => number_format($views, 0, ',', '.'),
                'is_link' => $isLink,
                'href' => $isLink ? url($path) : '',
            ];
        }

        $topProductsViewedPrepared = [];
        foreach ($topProductsViewed as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = (string)($row['product_name'] ?? '');
            $slug = (string)($row['product_slug'] ?? '');
            $variant = (string)($row['variant_title'] ?? '');
            $views = (int)($row['views'] ?? 0);
            $topProductsViewedPrepared[] = [
                'label' => $name . ($variant !== '' ? (' - ' . $variant) : ''),
                'views_fmt' => number_format($views, 0, ',', '.'),
                'has_link' => ($slug !== ''),
                'href' => $slug !== '' ? url('/producto?slug=' . urlencode($slug)) : '',
            ];
        }

        $topProductsAddedPrepared = [];
        foreach ($topProductsAdded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = (string)($row['product_name'] ?? '');
            $slug = (string)($row['product_slug'] ?? '');
            $variant = (string)($row['variant_title'] ?? '');
            $qty = (int)($row['qty'] ?? 0);
            $topProductsAddedPrepared[] = [
                'label' => $name . ($variant !== '' ? (' - ' . $variant) : ''),
                'qty_fmt' => number_format($qty, 0, ',', '.'),
                'has_link' => ($slug !== ''),
                'href' => $slug !== '' ? url('/producto?slug=' . urlencode($slug)) : '',
            ];
        }

        $showPayload = [
            'days' => $days,
            'campaign' => [
                    'id' => $campaignId,
                    'name' => $campaignName,
                    'slug' => $campaignSlug,
                    'destination_path' => $destinationPath,
                    'destination_label' => $destinationPath !== '' ? $destinationPath : '/',
                    'utm_campaign' => $utmCampaign,
                    'utm_campaign_label' => $utmCampaign !== '' ? $utmCampaign : $campaignSlug,
                    'notes' => $notes,
                    'notes_label' => $notes !== '' ? $notes : '—',
                    'is_active' => $isActive,
                    'active_label' => $isActive ? 'Activa' : 'Pausada',
                    'promo_url' => $promoUrl,
                    'promo_path' => $promoPath,
                ],
            'report' => [
                    'hits_fmt' => number_format($hits, 0, ',', '.'),
                    'new_users_fmt' => number_format($newUsers, 0, ',', '.'),
                    'add_to_cart_fmt' => number_format($addToCart, 0, ',', '.'),
                    'purchases_fmt' => number_format($purchases, 0, ',', '.'),
                    'revenue_fmt' => number_format($revenue, 0, ',', '.'),
                    'ratio_new_users_fmt' => number_format($ratioNew, 1, ',', '.') . '%',
                    'ratio_add_to_cart_fmt' => number_format($ratioATC, 1, ',', '.') . '%',
                    'ratio_purchases_fmt' => number_format($ratioBuy, 1, ',', '.') . '%',
                ],
            'series' => $seriesPrepared,
            'recent_hits' => $hitsPrepared,
            'recent_orders' => $recentOrdersPrepared,
            'top_pages' => $topPagesPrepared,
            'top_products_viewed' => $topProductsViewedPrepared,
            'top_products_added' => $topProductsAddedPrepared,
            'links' => [
                    'dashboard' => url('/admin'),
                    'marketing_index' => url('/admin/marketing/campanas'),
                    'edit' => url('/admin/marketing/campanas/edit?id=' . $campaignId),
                    'export_csv' => url('/admin/marketing/campanas/export_one?id=' . $campaignId . '&days=' . $days),
                    'show' => url('/admin/marketing/campanas/show'),
                    'qr_open' => url('/admin/marketing/campanas/qr?id=' . $campaignId),
                    'qr_download' => url('/admin/marketing/campanas/qr?id=' . $campaignId . '&download=1'),
                'qr_data_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($promoUrl),
            ],
        ];

        return [
            'show_payload' => $showPayload,
            'show_view' => [
                'campaign' => is_array($showPayload['campaign'] ?? null) ? $showPayload['campaign'] : [],
                'report' => is_array($showPayload['report'] ?? null) ? $showPayload['report'] : [],
                'links' => is_array($showPayload['links'] ?? null) ? $showPayload['links'] : [],
                'days' => (int)($showPayload['days'] ?? 30),
                'series' => is_array($showPayload['series'] ?? null) ? $showPayload['series'] : [],
                'recentHits' => is_array($showPayload['recent_hits'] ?? null) ? $showPayload['recent_hits'] : [],
                'recentOrders' => is_array($showPayload['recent_orders'] ?? null) ? $showPayload['recent_orders'] : [],
                'topPages' => is_array($showPayload['top_pages'] ?? null) ? $showPayload['top_pages'] : [],
                'topProductsViewed' => is_array($showPayload['top_products_viewed'] ?? null) ? $showPayload['top_products_viewed'] : [],
                'topProductsAdded' => is_array($showPayload['top_products_added'] ?? null) ? $showPayload['top_products_added'] : [],
            ],
        ];
    }

    /**
     * @return array{badge:string,tone:string,text:string,href:?string}
     */
    private function referrerUi(string $ref): array
    {
        $ref = trim($ref);

        if ($ref === '') {
            return [
                'badge' => 'Directo',
                'tone' => 'neutral',
                'text' => 'Directo / sin referrer',
                'href' => null,
            ];
        }

        $host = (string)(parse_url($ref, PHP_URL_HOST) ?? '');
        $path = (string)(parse_url($ref, PHP_URL_PATH) ?? '');
        $curHost = (string)($_SERVER['HTTP_HOST'] ?? '');

        $isInternal = false;
        if ($host === '' && str_starts_with($ref, '/')) {
            $isInternal = true;
            $path = $ref;
        } elseif ($host !== '' && $curHost !== '' && strcasecmp($host, $curHost) === 0) {
            $isInternal = true;
        }

        $isAdmin = $isInternal && ($path !== '') && (strpos($path, '/admin') !== false);
        $badge = $isInternal ? ($isAdmin ? 'Interno (admin)' : 'Interno') : 'Externo';
        $tone = $isInternal ? ($isAdmin ? 'warning' : 'neutral') : 'external';

        if ($host !== '') {
            $short = $host . ($path !== '' ? $path : '');
        } elseif ($path !== '') {
            $short = $path;
        } else {
            $short = $ref;
        }

        if (function_exists('mb_strlen') && mb_strlen($short) > 80) {
            $short = (string)mb_substr($short, 0, 77) . '…';
        } elseif (strlen($short) > 80) {
            $short = substr($short, 0, 77) . '…';
        }

        return [
            'badge' => $badge,
            'tone' => $tone,
            'text' => $short,
            'href' => $ref,
        ];
    }
}
