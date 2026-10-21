<?php

namespace App\Controllers\Admin;

use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Services\Billing\FacturaPdfService;
use App\Services\Billing\FacturacionArcaService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionComprobantesIndexViewModel;
use App\Services\Admin\Facturacion\ViewModels\FacturacionComprobantesShowViewModel;
use App\Support\Admin\ArcaBoxRenderer;

final class ComprobantesFiscalesController
{
    private ?ComprobanteFiscalRepository $repo = null;
    private ?FacturacionArcaService $facturacion = null;
    private ?FacturaPdfService $pdf = null;

    private function repo(): ComprobanteFiscalRepository
    {
        if ($this->repo === null) {
            $this->repo = new ComprobanteFiscalRepository(db());
        }
        return $this->repo;
    }

    private function facturacion(): FacturacionArcaService
    {
        if ($this->facturacion === null) {
            $this->facturacion = new FacturacionArcaService(db());
        }
        return $this->facturacion;
    }

    private function pdfService(): FacturaPdfService
    {
        if ($this->pdf === null) {
            $this->pdf = new FacturaPdfService(db());
        }
        return $this->pdf;
    }

    public function index(): void
    {
        admin_required();

        $filters = [
            'status' => trim((string)($_GET['status'] ?? '')),
            'arca_env' => trim((string)($_GET['arca_env'] ?? '')),
            'q' => trim((string)($_GET['q'] ?? '')),
            'kind' => trim((string)($_GET['kind'] ?? '')),

            // Filtros exactos (drill-down duplicados)
            'pto_vta' => (int)($_GET['pto_vta'] ?? 0),
            'cbte_tipo' => (int)($_GET['cbte_tipo'] ?? 0),
            'cbte_nro' => (int)($_GET['cbte_nro'] ?? 0),

            // Rangos de fechas (unificados)
            // date_field: issued|created, date_from/date_to en formato YYYY-MM-DD
            'date_field' => trim((string)($_GET['date_field'] ?? '')),
            'date_from'  => trim((string)($_GET['date_from'] ?? '')),
            'date_to'    => trim((string)($_GET['date_to'] ?? '')),

            // Legacy (drill-down/reportes)
            'issued_from' => trim((string)($_GET['issued_from'] ?? '')),
            'issued_to'   => trim((string)($_GET['issued_to'] ?? '')),
            'created_from' => trim((string)($_GET['created_from'] ?? '')),
            'created_to'   => trim((string)($_GET['created_to'] ?? '')),

            // Retrocompatible (si quedó algún link viejo)
            'order_id' => (int)($_GET['order_id'] ?? 0),
        ];
        // Normalizamos filtros unificados de fecha a los legacy del repo (para evitar tocar SQL).
        $df = (string)($filters['date_field'] ?? '');
        $fromD = (string)($filters['date_from'] ?? '');
        $toD = (string)($filters['date_to'] ?? '');
        if ($df !== 'created') $df = 'issued';

        if ($fromD !== '' || $toD !== '') {
            if ($df === 'created') {
                if (!$filters['created_from']) $filters['created_from'] = $fromD;
                if (!$filters['created_to'])   $filters['created_to']   = $toD;
            } else {
                if (!$filters['issued_from']) $filters['issued_from'] = $fromD;
                if (!$filters['issued_to'])   $filters['issued_to']   = $toD;
            }
        }


        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $total = $this->repo()->countList($filters);
        $rows = $this->repo()->list($filters, $perPage, $offset);
        $pagination = paginate_meta($total, $perPage, $page);
        $vm = new FacturacionComprobantesIndexViewModel();

        render_page('admin/facturacion/comprobantes/index', $vm->build([
            'filters' => $filters,
            'rows' => $rows,
            'pagination' => $pagination,
        ]), [
            'pageId' => 'admin_facturacion_comprobantes_index',
        ]);
    }

    public function show(): void
    {
        admin_required();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Falta id.';
            return;
        }

        $row = $this->repo()->findById($id);
        if (!$row) {
            http_response_code(404);
            echo 'Comprobante no encontrado.';
            return;
        }
        $vm = new FacturacionComprobantesShowViewModel();

        render_page('admin/facturacion/comprobantes/show', $vm->build([
            'row' => $row,
        ]), [
            'pageId' => 'admin_facturacion_comprobantes_show',
        ]);
    }

    public function consultar(): void
    {
        admin_required();
        verify_csrf_or_abort();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            inline_notice_set('comprobantes_fiscales', 'Falta id de comprobante.', 'error');
            redirect('/admin/facturacion/comprobantes');
            return;
        }

        $res = $this->facturacion()->consultarComprobante($id);
        if (($res['ok'] ?? false) === true) {
            inline_notice_set('comprobantes_fiscales', 'Consulta realizada. Se guardó un snapshot.', 'success');
        } else {
            $msg = $res['error'] ?? 'No se pudo consultar el comprobante.';
            inline_notice_set('comprobantes_fiscales', $msg, 'error');
        }

        redirect('/admin/facturacion/comprobantes/show?id=' . $id);
    }

    public function retryEmit(): void
    {
        admin_required();
        verify_csrf_or_abort();

        $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
              || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
              || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            if ($isJson) {
                json(['ok'=>false,'message'=>'Falta id de comprobante.'], 422);
                return;
            }
            inline_notice_set('comprobantes_fiscales', 'Falta id de comprobante.', 'error');
            redirect('/admin/facturacion/comprobantes');
            return;
        }

        $actor = (int)($_SESSION['uid'] ?? 0);
        $res = $this->facturacion()->reintentarEmisionComprobante($actor, $id, 'manual_admin');

        $ok = (($res['ok'] ?? false) === true);
        $skipped = !empty($res['skipped']);
        $msg = (string)($res['msg'] ?? ($ok ? 'Reintento realizado.' : 'No se pudo reintentar.'));

        if ($isJson) {
            $orderId = 0;
            try {
                $row = $this->repo()->findById($id);
                $orderId = (int)($row['order_id'] ?? 0);
            } catch (\Throwable $e) {
                $orderId = 0;
            }

            $arcaHtml = '';
            if ($orderId > 0) {
                try { $arcaHtml = (new ArcaBoxRenderer(db()))->render($orderId); } catch (\Throwable $e) { $arcaHtml = ''; }
            }

            json([
                'ok' => $ok,
                'skipped' => $skipped,
                'message' => $msg,
                'order_id' => $orderId,
                'arca_html' => $arcaHtml,
            ] + ($ok ? [] : ['error'=>'retry_failed']), $ok ? 200 : 422);
            return;
        }

        if ($ok) {
            $type = $skipped ? 'warning' : 'success';
            inline_notice_set('comprobantes_fiscales', $msg, $type);
        } else {
            inline_notice_set('comprobantes_fiscales', $msg, 'error');
        }

        redirect('/admin/facturacion/comprobantes/show?id=' . $id);
    }

    public function resendEmail(): void
    {
        admin_required();
        verify_csrf_or_abort();

        $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
              || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
              || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            if ($isJson) {
                json(['ok'=>false,'message'=>'Falta id de comprobante.'], 422);
                return;
            }
            inline_notice_set('comprobantes_fiscales', 'Falta id de comprobante.', 'error');
            redirect('/admin/facturacion/comprobantes');
            return;
        }

        $actor = (int)($_SESSION['uid'] ?? 0);
        $res = $this->facturacion()->reenviarEmailComprobante($actor, $id, 'manual_admin');

        $ok = (($res['ok'] ?? false) === true);
        $skipped = !empty($res['skipped']);
        $msg = (string)($res['msg'] ?? ($ok ? 'Email enviado.' : 'No se pudo enviar el email.'));

        if ($isJson) {
            $orderId = 0;
            try {
                $row = $this->repo()->findById($id);
                $orderId = (int)($row['order_id'] ?? 0);
            } catch (\Throwable $e) {
                $orderId = 0;
            }

            $arcaHtml = '';
            if ($orderId > 0) {
                try { $arcaHtml = (new ArcaBoxRenderer(db()))->render($orderId); } catch (\Throwable $e) { $arcaHtml = ''; }
            }

            json([
                'ok' => $ok,
                'skipped' => $skipped,
                'message' => $msg,
                'order_id' => $orderId,
                'arca_html' => $arcaHtml,
            ] + ($ok ? [] : ['error'=>'resend_failed']), $ok ? 200 : 422);
            return;
        }

        if ($ok) {
            $type = $skipped ? 'warning' : 'success';
            inline_notice_set('comprobantes_fiscales', $msg, $type);
        } else {
            inline_notice_set('comprobantes_fiscales', $msg, 'error');
        }

        redirect('/admin/facturacion/comprobantes/show?id=' . $id);
    }

    // GET /admin/facturacion/comprobantes/pdf?id=...
    public function pdf(): void
    {
        admin_required();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo 'Falta id.';
            return;
        }

        $res = $this->pdfService()->buildPdfForComprobanteId($id);
        if (($res['ok'] ?? false) !== true) {
            http_response_code(400);
            echo (string)($res['error'] ?? 'No se pudo generar el PDF.');
            return;
        }

        $filename = (string)($res['filename'] ?? 'factura.pdf');
        $bytes = (string)($res['bytes'] ?? '');
        $engine = (string)($res['engine'] ?? 'unknown');

        header('X-Content-Type-Options: nosniff');
        header('X-PDF-Engine: ' . $engine);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . (string)strlen($bytes));
        echo $bytes;
        exit;
    }
}

