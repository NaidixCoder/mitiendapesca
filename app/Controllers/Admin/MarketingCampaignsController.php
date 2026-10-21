<?php
namespace App\Controllers\Admin;

use App\Repositories\Marketing\MarketingCampaignRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Audit\Audit;
use App\Services\Admin\MarketingCampaigns\ViewModels\MarketingCampaignsShowViewModel;
use Throwable;

final class MarketingCampaignsController extends BaseController
{
    public function index(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $days = (int)($_GET['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        $status = (string)($_GET['status'] ?? '');
        if (!in_array($status, ['active', 'inactive'], true)) $status = '';
        $per = (int)($_GET['per'] ?? 25);
        if (!in_array($per, [25, 50], true)) $per = 25;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $rows = [];
        $total = 0;
        $pages = 1;
        $attributionMode = 'first_touch';
        try {
            $repo = new MarketingCampaignRepository(db());
            $total = $repo->countForAdmin($q, $status);
            $pages = max(1, (int)ceil($total / $per));
            $page = min($page, $pages);
            $rows = $repo->listForAdmin($q, $days, $status, $per, ($page-1)*$per);

            $settings = new AppSettingsRepository(db());
            $mode = strtolower(trim((string)$settings->get('marketing.attribution_mode', '')));
            if ($mode === '') $mode = strtolower(trim((string)env('MARKETING_ATTRIBUTION', 'first_touch')));
            if (in_array($mode, ['first_touch','last_touch'], true)) {
                $attributionMode = $mode;
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\MarketingCampaignsController@index: ' . $e->getMessage());
            flash('error', 'No se pudo cargar el listado de campañas. Intentá nuevamente.');
        }
        $view = (new \App\Services\Admin\MarketingCampaigns\ViewModels\MarketingCampaignsIndexViewModel())->build($rows, $q, $days, $status, $page, $per, $total, $pages);
        render_page('admin/marketing_campaigns/index', array_merge($view, [
            'pageId' => 'admin-marketing-campaigns',
            'rows' => $rows,
            'q' => $q,
            'days' => $days,
            'attributionMode' => $attributionMode,
        ]));
    }

    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $days = (int)($_GET['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        if ($id <= 0) { redirect('/admin/marketing/campanas'); }

        $repo = new MarketingCampaignRepository(db());
        $campaign = $repo->findById($id);
        if (!$campaign) {
            flash('error', 'Campaña no encontrada.');
            redirect('/admin/marketing/campanas');
        }

        $report = [];
        $series = [];
        $recentHits = [];
        $recentOrders = [];
        $topPages = [];
        $topProductsViewed = [];
        $topProductsAdded = [];
        try {
            $report = $repo->getReport($id, $days);
            $series = $repo->getDailySeries($id, $days);
            $recentHits = $repo->getRecentHits($id, 20, $days);
            $recentOrders = $repo->getRecentOrders($id, 10, $days);
            $topPages = $repo->topPages($id, $days, 12);
            $topProductsViewed = $repo->topProductsViewed($id, $days, 12);
            $topProductsAdded = $repo->topProductsAddedToCart($id, $days, 12);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\MarketingCampaignsController@show: ' . $e->getMessage());
        }

        $vm = (new MarketingCampaignsShowViewModel())->build([
            'campaign' => $campaign,
            'days' => $days,
            'report' => $report,
            'series' => $series,
            'recentHits' => $recentHits,
            'recentOrders' => $recentOrders,
            'topPages' => $topPages,
            'topProductsViewed' => $topProductsViewed,
            'topProductsAdded' => $topProductsAdded,
        ]);

        render_page('admin/marketing_campaigns/show', array_merge([
            'pageId' => 'admin-marketing-campaigns',
            'campaign' => $campaign,
            'days' => $days,
            'report' => $report,
            'series' => $series,
            'recentHits' => $recentHits,
            'recentOrders' => $recentOrders,
            'topPages' => $topPages,
            'topProductsViewed' => $topProductsViewed,
            'topProductsAdded' => $topProductsAdded,
        ], $vm));
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $row = [
            'id' => 0,
            'name' => '',
            'slug' => '',
            'destination_path' => '/productos',
            'utm_source' => '',
            'utm_medium' => '',
            'utm_campaign' => '',
            'utm_content' => '',
            'utm_term' => '',
            'is_active' => 1,
            'notes' => '',
        ];

        try {
            if ($id > 0) {
                $ex = (new MarketingCampaignRepository(db()))->findById($id);
                if ($ex) $row = array_merge($row, $ex);
            }
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\MarketingCampaignsController@form: ' . $e->getMessage());
        }

        render_page('admin/marketing_campaigns/form', [
            'pageId' => 'admin-marketing-campaign-form',
            'campaign' => $row,
        ]);
    }

    public function save(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':mkt_campaign_save', 30, 60);
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
        require_once base_path('app/Support/str.php');

        $repo = new MarketingCampaignRepository(db());

        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $slug = trim((string)($_POST['slug'] ?? ''));
        $dest = trim((string)($_POST['destination_path'] ?? ''));

        if ($slug === '' && $name !== '') $slug = str_slug($name);

        // normalizaciones
        $dest = ($dest === '') ? '/productos' : $dest;
        $isActive = ((string)($_POST['is_active'] ?? '') === '1') ? 1 : 0;

        $fields = [
            'utm_source' => trim((string)($_POST['utm_source'] ?? '')),
            'utm_medium' => trim((string)($_POST['utm_medium'] ?? '')),
            'utm_campaign' => trim((string)($_POST['utm_campaign'] ?? '')),
            'utm_content' => trim((string)($_POST['utm_content'] ?? '')),
            'utm_term' => trim((string)($_POST['utm_term'] ?? '')),
        ];
        foreach ($fields as $k => $v) {
            if ($v === '') $fields[$k] = null;
        }

        $notes = trim((string)($_POST['notes'] ?? ''));
        if ($notes === '') $notes = null;

        $errs = [];
        if ($name === '') $errs[] = 'Nombre requerido';
        if ($slug === '') $errs[] = 'Slug requerido';
        if (strlen($slug) > 80) $errs[] = 'Slug demasiado largo';
        if (strlen($dest) > 255) $errs[] = 'Destino demasiado largo';

        // destino: permitir rutas (/productos?...) o URL absoluta (https://...)
        if ($dest !== '' && !preg_match('#^(https?://|/)#i', $dest)) {
            $errs[] = 'Destino inválido: debe empezar con / o con https://';
        }

        if (!$errs && $repo->slugExists($slug, $id)) {
            $errs[] = 'Slug ya existe';
        }

        if ($errs) {
            flash('error', implode(' · ', $errs));
            redirect($id ? ('/admin/marketing/campanas/edit?id='.$id) : '/admin/marketing/campanas/new');
        }

        $newId = $repo->upsert([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'destination_path' => $dest,
            'utm_source' => $fields['utm_source'],
            'utm_medium' => $fields['utm_medium'],
            'utm_campaign' => $fields['utm_campaign'],
            'utm_content' => $fields['utm_content'],
            'utm_term' => $fields['utm_term'],
            'is_active' => $isActive,
            'notes' => $notes,
        ]);

        try {
            Audit::log('marketing.campaign.save', 'marketing_campaign', (int)$newId, ['op' => ($id>0?'update':'insert')]);
        } catch (Throwable $e) {
            // no bloqueante
        }

        flash('ok', $id > 0 ? 'Campaña actualizada.' : 'Campaña creada.');
        redirect('/admin/marketing/campanas');
    }

    public function toggle(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':mkt_campaign_toggle', 60, 60);
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $id = (int)($_POST['id'] ?? 0);
        $isActive = (int)($_POST['is_active'] ?? 0);
        if ($id <= 0) { redirect('/admin/marketing/campanas'); }

        try {
            (new MarketingCampaignRepository(db()))->setActive($id, $isActive ? 1 : 0);
            Audit::log('marketing.campaign.toggle', 'marketing_campaign', (int)$id, ['is_active' => (int)($isActive?1:0)]);
        } catch (Throwable $e) {
            flash('error', 'No se pudo actualizar.');
            redirect('/admin/marketing/campanas');
        }

        flash('ok', 'Actualizado.');
        redirect('/admin/marketing/campanas');
    }

    public function saveSettings(): void
    {
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':mkt_settings_save', 30, 60);
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $mode = strtolower(trim((string)($_POST['attribution_mode'] ?? '')));
        if (!in_array($mode, ['first_touch','last_touch'], true)) {
            flash('error', 'Modo de atribución inválido.');
            redirect('/admin/marketing/campanas');
        }

        try {
            (new AppSettingsRepository(db()))->set('marketing.attribution_mode', $mode);
            Audit::log('marketing.settings.save', 'app_settings', 0, ['marketing.attribution_mode' => $mode]);
        } catch (Throwable $e) {
            flash('error', 'No se pudo guardar el ajuste.');
            redirect('/admin/marketing/campanas');
        }

        flash('ok', 'Ajustes actualizados.');
        redirect('/admin/marketing/campanas');
    }

    public function export(): void
    {
        $days = (int)($_GET['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) $days = 30;

        $q = trim((string)($_GET['q'] ?? ''));

        $status = (string)($_GET['status'] ?? '');
        if (!in_array($status, ['active','inactive'], true)) $status = '';
        $rows = (new MarketingCampaignRepository(db()))->listForAdmin($q, $days, $status);

        $filename = 'campanas_' . date('Ymd_His') . '_'. $days . 'd.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'id','nombre','slug','promo_url','destino','utm_source','utm_medium','utm_campaign','activa',
            'hits','nuevos_usuarios','add_to_cart','compras','ingresos','ratio_nuevos','ratio_atc','ratio_compras','rango_dias'
        ]);

        foreach ($rows as $r) {
            $hits = (int)($r['hits_30d'] ?? 0);
            $newUsers = (int)($r['new_users_30d'] ?? 0);
            $atc = (int)($r['add_to_cart_30d'] ?? 0);
            $purchases = (int)($r['purchases_30d'] ?? 0);
            $revenue = (int)($r['revenue_30d'] ?? 0);
            $ratioNew = $hits > 0 ? round(($newUsers / $hits) * 100, 1) : 0.0;
            $ratioAtc = $hits > 0 ? round(($atc / $hits) * 100, 1) : 0.0;
            $ratioBuy = $hits > 0 ? round(($purchases / $hits) * 100, 1) : 0.0;

            $promo = '/promo?slug='.(string)($r['slug'] ?? '');
            $promoUrl = function_exists('url') ? url($promo) : $promo;

            fputcsv($out, [
                (int)($r['id'] ?? 0),
                (string)($r['name'] ?? ''),
                (string)($r['slug'] ?? ''),
                $promoUrl,
                (string)($r['destination_path'] ?? ''),
                (string)($r['utm_source'] ?? ''),
                (string)($r['utm_medium'] ?? ''),
                (string)($r['utm_campaign'] ?? ''),
                (int)($r['is_active'] ?? 0),
                $hits,
                $newUsers,
                $atc,
                $purchases,
                $revenue,
                $ratioNew,
                $ratioAtc,
                $ratioBuy,
                $days,
            ]);
        }

        fclose($out);
        exit;
    }

    public function exportOne(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $days = (int)($_GET['days'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        if ($id <= 0) { redirect('/admin/marketing/campanas'); }

        $repo = new MarketingCampaignRepository(db());
        $campaign = $repo->findById($id);
        if (!$campaign) { redirect('/admin/marketing/campanas'); }

        $series = $repo->getDailySeries($id, $days);

        $filename = 'campana_' . (string)($campaign['slug'] ?? 'campana') . '_' . date('Ymd_His') . '_'. $days . 'd.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['fecha','hits','nuevos_usuarios','add_to_cart','compras','ingresos','rango_dias']);
        foreach ($series as $row) {
            fputcsv($out, [
                (string)($row['date'] ?? ''),
                (int)($row['hits'] ?? 0),
                (int)($row['new_users'] ?? 0),
                (int)($row['add_to_cart'] ?? 0),
                (int)($row['purchases'] ?? 0),
                (int)($row['revenue'] ?? 0),
                $days,
            ]);
        }
        fclose($out);
        exit;
    }

    /**
     * Devuelve el PNG del QR del /promo/{slug}.
     * - download=1 => fuerza descarga
     * - download=0 => inline (abrir)
     */
    public function qr(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $download = ((string)($_GET['download'] ?? '') === '1');
        if ($id <= 0) {
            http_response_code(404);
            exit('Not found');
        }

        $repo = new MarketingCampaignRepository(db());
        $campaign = $repo->findById($id);
        if (!$campaign) {
            http_response_code(404);
            exit('Not found');
        }

        $promoPath = '/promo/' . (string)($campaign['slug'] ?? '');
        $promoUrl = function_exists('url') ? url($promoPath) : $promoPath;

        // QR externo (sin deps). Renderizamos algo más grande para imprimir.
        $qrRemote = 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&data=' . rawurlencode((string)$promoUrl);

        $png = '';
        $status = 0;

        // Preferimos cURL (más control). Fallback a file_get_contents si está habilitado.
        if (function_exists('curl_init')) {
            $ch = curl_init($qrRemote);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            $png = (string)curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'follow_location' => 1,
                ],
            ]);
            $png = (string)@file_get_contents($qrRemote, false, $ctx);
            $status = ($png !== '') ? 200 : 0;
        }

        if ($status !== 200 || $png === '') {
            http_response_code(502);
            exit('No se pudo generar el QR.');
        }

        $slug = preg_replace('/[^a-z0-9\-_.]+/i', '-', (string)($campaign['slug'] ?? 'campana'));
        $filename = 'qr_' . trim($slug, '-') . '.png';

        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=300');
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . $filename . '"');
        echo $png;
        exit;
    }
}

