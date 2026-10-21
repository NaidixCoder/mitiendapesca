<?php
namespace App\Controllers\Admin;

use App\Services\Admin\Dashboard\DashboardNavigationAnalyticsService;

class AnalyticsController extends BaseController
{
    public function index(): void
    {
        $db = db();
        $analytics = (new DashboardNavigationAnalyticsService())->collect($db, 30);

        render_page('admin/analytics/index', [
            'navigationAnalytics' => $analytics['navigationAnalytics'],
            'pageId' => 'admin-analytics',
        ]);
    }
}
