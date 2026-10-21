<?php
namespace App\Controllers\Admin;

use App\Repositories\Marketing\EmailSubscriberRepository;
use App\Repositories\Marketing\EmailAutomationRepository;
use App\Repositories\Marketing\EmailQueueRepository;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailAutomationsViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailQueueViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailSubscribersViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination;
use App\Services\Marketing\EmailAutomationSchedulerService;
use App\Services\Marketing\EmailQueueWorkerService;
use App\Repositories\Settings\AppSettingsRepository;
use Throwable;

final class EmailMarketingController extends BaseController
{
    public function index(): void
    {
        $db = db();

        $stats = ['total'=>0,'pending'=>0,'subscribed'=>0,'unsubscribed'=>0];
        try {
            $stats = (new EmailSubscriberRepository($db))->stats();
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\EmailMarketingController@index: ' . $e->getMessage());
        }

        $queueCounts = ['queued'=>0,'sending'=>0,'sent'=>0,'failed'=>0,'cancelled'=>0,'total'=>0];
        try {
            $queueCounts = (new EmailQueueRepository($db))->countsByStatus();
        } catch (Throwable $e) {
            // no-op
        }

        render_page('admin/marketing_email/index', [
            'pageId' => 'admin-marketing-email',
            'stats' => $stats,
            'queueCounts' => $queueCounts,
        ]);
    }

    public function subscribers(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        if (!in_array($status, ['', 'pending', 'subscribed', 'unsubscribed'], true)) $status = '';
        $per = (int)($_GET['per'] ?? 25);
        if (!in_array($per, [25,50], true)) $per = 25;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $rows = [];
        $total = 0;
        $pages = 1;
        try {
            $repo = new EmailSubscriberRepository(db());
            $total = $repo->countForAdmin($q, $status);
            $pages = max(1, (int)ceil($total / $per));
            $page = min($page, $pages);
            $rows = $repo->listForAdmin($q, $status, $per, ($page - 1)*$per);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\EmailMarketingController@subscribers: ' . $e->getMessage());
        }

        $vm = (new MarketingEmailSubscribersViewModel())->build([
            'q' => $q,
            'status' => $status,
            'rows' => $rows,
            'total' => $total,
            'per' => $per,
            'page' => $page,
            'pages' => $pages,
        ]);

        render_page('admin/marketing_email/subscribers', array_merge($vm, [
            'pageId' => 'admin-marketing-email',
        ]));
    }

    public function setSubscriberStatus(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':email_sub_status', 60, 60);

        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        if ($id <= 0) { redirect('/admin/marketing/email/suscriptores'); }

        try {
            $ok = (new EmailSubscriberRepository(db()))->setStatusById($id, $status);
            flash($ok ? 'ok' : 'error', $ok ? 'Actualizado.' : 'No se pudo actualizar.');
        } catch (Throwable $e) {
            flash('error', 'No se pudo actualizar.');
        }
        redirect('/admin/marketing/email/suscriptores');
    }

    /* ==================== Automatizaciones ==================== */

    public function automations(): void
    {
        $db = db();
        try {
            (new EmailAutomationRepository($db))->ensureDefaults();
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\EmailMarketingController@automations ensureDefaults: ' . $e->getMessage());
        }

        $rows = [];
        try {
            $rows = (new EmailAutomationRepository($db))->listAutomationsWithSteps();
        } catch (Throwable $e) {
            // no-op
        }

        $vm = (new MarketingEmailAutomationsViewModel())->build([
            'rows' => $rows,
        ]);

        render_page('admin/marketing_email/automations', array_merge($vm, [
            'pageId' => 'admin-marketing-email',
        ]));
    }

    public function toggleAutomation(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':email_auto_toggle', 60, 60);

        $id = (int)($_POST['id'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0) === 1;
        if ($id <= 0) { redirect('/admin/marketing/email/automatizaciones'); }

        try {
            (new EmailAutomationRepository(db()))->setAutomationEnabled($id, $enabled);
            flash('ok', 'Actualizado.');
        } catch (Throwable $e) {
            flash('error', 'No se pudo actualizar.');
        }

        redirect('/admin/marketing/email/automatizaciones');
    }

    public function toggleStep(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':email_step_toggle', 90, 60);

        $id = (int)($_POST['id'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0) === 1;
        if ($id <= 0) { redirect('/admin/marketing/email/automatizaciones'); }

        try {
            (new EmailAutomationRepository(db()))->setStepEnabled($id, $enabled);
            flash('ok', 'Actualizado.');
        } catch (Throwable $e) {
            flash('error', 'No se pudo actualizar.');
        }

        redirect('/admin/marketing/email/automatizaciones');
    }

    /* ==================== Cola ==================== */

    public function queue(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? 'queued'));
        if (!in_array($status,['queued','sending','sent','failed','cancelled','all'],true)) $status='queued';
        $per = (int)($_GET['per'] ?? 25);
        if (!in_array($per,[25,50],true)) $per=25;
        $page = max(1,(int)($_GET['page'] ?? 1));

        $repo = new EmailQueueRepository(db());
        $counts = ['queued'=>0,'sending'=>0,'sent'=>0,'failed'=>0,'cancelled'=>0,'total'=>0];
        $rows = [];
        $logs = [];
        $total = 0;
        try {
            $counts = $repo->countsByStatus();
            $total = $repo->countForAdmin($q, $status);
            $page = min($page,max(1,(int)ceil($total/$per)));
            $rows = $repo->listQueue($q, $status, $per,($page-1)*$per);
            $logs = $repo->listLogs(50);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\\EmailMarketingController: ' . $e->getMessage());
            flash('error', 'No se pudo leer la cola. ¿Aplicaste la migración 20260119_08_email_marketing_automations_queue?');
        }

        $vm = (new MarketingEmailQueueViewModel())->build([
            'q' => $q,
            'status' => $status,
            'counts' => $counts,
            'rows' => $rows,
            'logs' => $logs,
        ]);
        $filters=[];
        if ($q !== '') $filters['q']=$q;
        if ($status !== 'queued') $filters['status']=$status;
        $vm = array_merge($vm,MarketingEmailPagination::build('/admin/marketing/email/cola',$total,$per,$page,$filters));

        render_page('admin/marketing_email/queue', array_merge($vm, [
            'pageId' => 'admin-marketing-email',
        ]));
    }

    /**
     * Ejecuta scheduler + worker (best-effort) desde admin.
     * Para producción: usar CLI cron (tools/email-marketing-runner.php).
     */
    public function runNow(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }
        rate_limit_or_fail('admin:'.client_ip().':'.($_SESSION['uid']??0).':email_run', 6, 60);

        $db = db();
        $settings = new AppSettingsRepository($db);
        $schedLimit = (int)($settings->get('email_marketing_scheduler_limit', '') ?: env('EMAIL_MARKETING_SCHEDULER_LIMIT', 200));
        $workLimit  = (int)($settings->get('email_marketing_worker_limit', '') ?: env('EMAIL_MARKETING_WORKER_LIMIT', 25));
        $schedLimit = max(10, min(2000, $schedLimit));
        $workLimit  = max(1, min(500, $workLimit));

        $schedStats = ['welcome_enqueued'=>0,'cart_enqueued'=>0,'post_purchase_enqueued'=>0,'skipped_unsubscribed'=>0];
        $workStats = ['processed'=>0,'sent'=>0,'failed'=>0];

        try {
            $schedStats = (new EmailAutomationSchedulerService($db))->run($schedLimit);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\EmailMarketingController@runNow scheduler: ' . $e->getMessage());
        }

        try {
            $workStats = (new EmailQueueWorkerService($db))->processDue($workLimit);
        } catch (Throwable $e) {
            if (function_exists('log_error')) log_error('Admin\EmailMarketingController@runNow worker: ' . $e->getMessage());
        }

        flash('ok', "Encolado: welcome={$schedStats['welcome_enqueued']} | carrito={$schedStats['cart_enqueued']} | post-compra={$schedStats['post_purchase_enqueued']} (bajas omitidas={$schedStats['skipped_unsubscribed']}). Procesado: {$workStats['processed']} (enviados={$workStats['sent']}, fallados={$workStats['failed']}).");
        redirect('/admin/marketing/email/cola');
    }
}

