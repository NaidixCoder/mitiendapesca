<?php
namespace App\Controllers\Admin;

use App\Queries\Admin\SeoAuditQuery;
use App\Services\Admin\Seo\ViewModels\SeoAuditIndexViewModel;
use Throwable;

final class SeoAuditController extends BaseController
{
    public function index(): void
    {
        $db = db();

        $filters = [
            'q'         => (string)($_GET['q'] ?? ''),
            'active'    => ($_GET['active'] ?? ''),
            'issue'     => (string)($_GET['issue'] ?? ''),
            'score_min' => ($_GET['score_min'] ?? ''),
            'score_max' => ($_GET['score_max'] ?? ''),
            'sort'      => (string)($_GET['sort'] ?? 'score'),
            'order'     => (string)($_GET['order'] ?? 'asc'),
            'page'      => (int)($_GET['page'] ?? 1),
            'per'       => (int)($_GET['per'] ?? 25),
        ];

        try {
            $res = (new SeoAuditQuery($db))->search($filters);
            $vm = (new SeoAuditIndexViewModel())->build([
                'filters' => $filters,
                'rows' => is_array($res['rows'] ?? null) ? $res['rows'] : [],
                'total' => (int)($res['total'] ?? 0),
                'page' => (int)($res['page'] ?? 1),
                'pages' => (int)($res['pages'] ?? 1),
                'per' => (int)($res['per'] ?? 25),
                'summary' => is_array($res['summary'] ?? null) ? $res['summary'] : [],
            ]);

            render_page('admin/seo/auditoria', array_merge([
                'pageId' => 'admin-seo-audit',
                'pageTitle' => 'Auditoría SEO',
            ], $vm));
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\SeoAuditController@index: '.$e->getMessage());
            http_response_code(500);
            render_page('errors/500', ['message' => 'No se pudo cargar la auditoría SEO.']);
        }
    }
}

