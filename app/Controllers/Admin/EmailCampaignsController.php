<?php
namespace App\Controllers\Admin;

use App\Repositories\Marketing\EmailCampaignRepository;
use App\Repositories\Marketing\EmailQueueRepository;
use App\Repositories\Marketing\EmailTemplateRepository;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignFormViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignShowViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignsIndexViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination;
use App\Services\Marketing\EmailBroadcastSchedulerService;
use App\Mail\Core\Mailer;

final class EmailCampaignsController extends BaseController
{
    public function index(): void
    {
        $db = db();
        $repo = new EmailCampaignRepository($db);
        $per = (int)($_GET['per'] ?? 25);
        if (!in_array($per,[25,50],true)) $per = 25;
        $total = $repo->countForAdmin();
        $pages = max(1,(int)ceil($total/$per));
        $page = max(1,min($pages,(int)($_GET['page'] ?? 1)));
        $campaigns = $repo->listWithStats($per,($page-1)*$per);

        $vm = (new MarketingEmailCampaignsIndexViewModel())->build([
            'campaigns' => $campaigns,
        ]);
        $vm = array_merge($vm,MarketingEmailPagination::build('/admin/marketing/email/campanas',$total,$per,$page));

        render_page('admin/marketing_email/campaigns', array_merge($vm, [
            'pageId' => 'admin-marketing-email',
            'title' => 'Campañas de Email',
        ]));
    }

    public function form(): void
    {
        $db = db();
        $repo = new EmailCampaignRepository($db);
        $tplRepoLite = new EmailTemplateRepository($db);
        $templatesLite = $tplRepoLite->listLite(250);
        $id = (int)($_GET['id'] ?? 0);
        $campaign = $id > 0 ? $repo->find($id) : null;
        if ($id > 0 && !$campaign) {
            flash('error', 'Campaña no encontrada.');
            redirect(url('/admin/marketing/email/campanas'));
            return;
        }
        if ($id > 0 && !$repo->canEdit($id)) {
            inline_notice_set('email_campaign_show', 'Esta campaña ya no admite cambios: está enviándose, finalizada, cancelada o tiene destinatarios en cola.', 'error');
            redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
            return;
        }

        // Etapa 4 (Templates): si es nueva campaña y viene template_id, precargamos defaults.
        if (!$campaign) {
            $tplId = (int)($_GET['template_id'] ?? 0);
            if ($tplId > 0) {
                $tplRepo = new EmailTemplateRepository($db);
                $tpl = $tplRepo->find($tplId);
                if ($tpl) {
                    $campaign = [
                        'id' => 0,
                        'name' => 'Nueva campaña',
                        'subject' => (string)($tpl['subject_default'] ?? ''),
                        'preheader' => (string)($tpl['preheader_default'] ?? ''),
                        'body_html' => (string)($tpl['body_html'] ?? ''),
                        'cta_label' => null,
                        'cta_url' => null,
                        'audience' => 'all',
                        'audience_params_json' => null,
                        'status' => 'draft',
                        'scheduled_at' => null,
                    ];
                    inline_notice_set('email_campaign_form', 'Plantilla cargada. Revisá el contenido y ajustá el Asunto/Nombre antes de guardar.', 'info');
                }
            }
        }

        $notice = inline_notice_get('email_campaign_form');

        $vm = (new MarketingEmailCampaignFormViewModel())->build([
            'campaign' => $campaign,
            'templatesLite' => $templatesLite,
            'notice' => $notice,
        ]);

        render_page('admin/marketing_email/campaign_form', array_merge($vm, [
            'pageId' => 'admin-email-campaign-form',
            'title' => $id > 0 ? 'Editar campaña' : 'Nueva campaña',
        ]));
    }

    /** Vista previa HTML del email (sin layout web) */
    public function preview(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $subject   = trim((string)($_POST['subject'] ?? ''));
        $preheader = trim((string)($_POST['preheader'] ?? ''));
        $bodyHtml  = (string)($_POST['body_html'] ?? '');
        $ctaLabel  = trim((string)($_POST['cta_label'] ?? ''));
        $ctaUrl    = trim((string)($_POST['cta_url'] ?? ''));

        // Para preview: si el CTA es relativo (/productos), lo convertimos a absoluto.
        if ($ctaUrl !== '' && str_starts_with($ctaUrl, '/')) {
            $ctaUrl = url($ctaUrl);
        }

        $inner = Mailer::render('marketing/broadcast_campaign', [
            'email' => 'cliente@ejemplo.com',
            'preheader' => $preheader,
            'body_html' => $bodyHtml,
            'cta_label' => ($ctaLabel !== '' ? $ctaLabel : 'Ver'),
            'cta_url' => $ctaUrl,
            'unsubscribe_url' => '#',
        ]);

        $html = Mailer::wrapLayout($subject !== '' ? $subject : 'Vista previa', $inner);

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /** Enviar un test del email a una dirección (o varias) sin encolar campaña */
    public function sendTest(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'CSRF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $raw = (string)file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $toRaw = trim((string)($payload['to'] ?? ''));
        $subject = trim((string)($payload['subject'] ?? ''));
        $preheader = trim((string)($payload['preheader'] ?? ''));
        $bodyHtml = (string)($payload['body_html'] ?? $payload['html'] ?? '');
        $ctaLabel = trim((string)($payload['cta_label'] ?? ''));
        $ctaUrl = trim((string)($payload['cta_url'] ?? ''));

        if ($toRaw === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Ingresá un email de destino'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // Parseo simple: admite coma / punto y coma / espacios
        $parts = preg_split('/[\s,;]+/', $toRaw, -1, PREG_SPLIT_NO_EMPTY);
        $emails = [];
        foreach (($parts ?: []) as $p) {
            $p = trim((string)$p);
            if ($p === '') continue;
            if (!filter_var($p, FILTER_VALIDATE_EMAIL)) continue;
            $emails[] = $p;
        }
        $emails = array_values(array_unique($emails));

        if (empty($emails)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Email de destino inválido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (count($emails) > 5) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Máximo 5 destinatarios para el test'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        if (trim($bodyHtml) === '') {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'El contenido del email está vacío'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // CTA relativo -> absoluto (mejora preview en clientes email)
        if ($ctaUrl !== '' && str_starts_with($ctaUrl, '/')) {
            $ctaUrl = url($ctaUrl);
        }

        $inner = Mailer::render('marketing/broadcast_campaign', [
            'email' => $emails[0],
            'preheader' => $preheader,
            'body_html' => $bodyHtml,
            'cta_label' => ($ctaLabel !== '' ? $ctaLabel : 'Ver'),
            'cta_url' => $ctaUrl,
            'unsubscribe_url' => '#',
        ]);
        $finalSubject = ($subject !== '' ? $subject : 'Email de prueba');
        $html = Mailer::wrapLayout('[TEST] ' . $finalSubject, $inner);

        $sent = 0;
        $errors = [];
        foreach ($emails as $to) {
            try {
                $ok = Mailer::sendHtml($to, '[TEST] ' . $finalSubject, $html);
                if ($ok) {
                    $sent++;
                } else {
                    $errors[] = 'No se pudo enviar a ' . $to;
                }
            } catch (\Throwable $e) {
                $errors[] = $to . ': ' . $e->getMessage();
                if (function_exists('log_error')) {
                    log_error('[send_test] ' . $to . ' :: ' . $e->getMessage());
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        if ($sent <= 0) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'No se pudo enviar el test', 'details' => $errors], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        echo json_encode([
            'ok' => true,
            'sent' => $sent,
            'to' => $emails,
            'warning' => (!empty($errors) ? implode(' | ', $errors) : null),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Upload seguro de imágenes para el editor (retorna URL pública) */
    public function uploadImage(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'CSRF'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Falta archivo (image)'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $file = $_FILES['image'];
        $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_OK) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Upload inválido', 'code' => $err], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $tmp = (string)($file['tmp_name'] ?? '');
        $size = (int)($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Archivo temporal inválido'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // Límite duro (3MB)
        if ($size <= 0 || $size > 3 * 1024 * 1024) {
            http_response_code(413);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'La imagen supera el máximo de 3MB'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($tmp);

        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];
        if (!isset($map[$mime])) {
            http_response_code(415);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Formato no permitido (solo JPG/PNG/WebP/GIF)'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $ext = $map[$mime];
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        $dir = base_path('public_html/uploads/email');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        // URL pública (sirve en local / subcarpeta)
        $public = url('/uploads/email/' . $name);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'url' => $public], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function save(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $repo = new EmailCampaignRepository(db());
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && !$repo->canEdit($id)) {
            inline_notice_set('email_campaign_show', 'La campaña no puede editarse después de iniciar el envío o la cola.', 'error');
            redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $preheader = trim((string)($_POST['preheader'] ?? ''));
        $bodyHtml = (string)($_POST['body_html'] ?? '');
        $ctaLabel = trim((string)($_POST['cta_label'] ?? ''));
        $ctaUrl = trim((string)($_POST['cta_url'] ?? ''));
        $audience = trim((string)($_POST['audience'] ?? 'all'));
        $scheduledAt = trim((string)($_POST['scheduled_at'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'draft'));

        // Validación mínima para guardar (borrador). No depende del flash: mostramos inline.
        $basicErrors = [];
        if ($name === '') $basicErrors[] = 'Nombre vacío';
        if ($subject === '') $basicErrors[] = 'Asunto vacío';
        if (trim(strip_tags($bodyHtml)) === '') $basicErrors[] = 'Contenido vacío';

        if (!empty($basicErrors)) {
            remember_input($_POST);
            inline_notice_set('email_campaign_form', 'Revisá los campos obligatorios: ' . implode(' · ', $basicErrors), 'error');
            redirect(url('/admin/marketing/email/campanas' . ($id > 0 ? ('/edit?id=' . $id) : '/new')));
            return;
        }

        $audienceAllowed = ['all','buyers','non_buyers','inactive_buyers','vip'];
        if (!in_array($audience, $audienceAllowed, true)) $audience = 'all';

        $statusAllowed = ['draft','scheduled'];
        if (!in_array($status, $statusAllowed, true)) {
            remember_input($_POST);
            inline_notice_set('email_campaign_form', 'Estado no permitido para guardar. Para cancelar un envío usá la acción del detalle.', 'error');
            redirect(url('/admin/marketing/email/campanas' . ($id > 0 ? ('/edit?id=' . $id) : '/new')));
            return;
        }

        $audParams = null;
        if ($audience === 'inactive_buyers') {
            $days = (int)($_POST['aud_days'] ?? 60);
            $days = max(7, min(3650, $days));
            $audParams = json_encode(['days' => $days], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif ($audience === 'vip') {
            $minTotal = (int)($_POST['aud_min_total'] ?? 200000);
            $minTotal = max(0, $minTotal);
            $audParams = json_encode(['min_total' => $minTotal], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // scheduled_at: acepta "YYYY-MM-DDTHH:MM" (datetime-local) o "YYYY-MM-DD HH:MM".
        $scheduledAtDb = null;
        $scheduledAtErr = null;
        if ($scheduledAt !== '') {
            $normalized = str_replace('T', ' ', $scheduledAt);
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized)) {
                $scheduledAtDb = $normalized . ':00';
            } else {
                $scheduledAtErr = 'Formato de fecha/hora inválido';
            }
        }

        // Si está "Programada", la fecha/hora es obligatoria y debe ser futura.
        if ($status === 'scheduled') {
            if ($scheduledAtDb === null) {
                remember_input($_POST);
                inline_notice_set('email_campaign_form', 'Para dejarla en "Programada" necesitás fecha y hora. Si querés enviarla ya, guardá como "Borrador" y usá "Programar ahora" desde el detalle.', 'error');
                redirect(url('/admin/marketing/email/campanas' . ($id > 0 ? ('/edit?id=' . $id) : '/new')));
                return;
            }
            $ts = strtotime($scheduledAtDb);
            if (!$ts || $ts < time()) {
                remember_input($_POST);
                inline_notice_set('email_campaign_form', 'La fecha/hora de envío debe ser futura.', 'error');
                redirect(url('/admin/marketing/email/campanas' . ($id > 0 ? ('/edit?id=' . $id) : '/new')));
                return;
            }
        } elseif ($scheduledAtErr) {
            // En borrador permitimos vacío, pero si cargó algo inválido lo marcamos.
            remember_input($_POST);
            inline_notice_set('email_campaign_form', 'Fecha y hora: ' . $scheduledAtErr, 'error');
            redirect(url('/admin/marketing/email/campanas' . ($id > 0 ? ('/edit?id=' . $id) : '/new')));
            return;
        }

        $data = [
            'name' => $name,
            'subject' => $subject,
            'preheader' => ($preheader !== '' ? $preheader : null),
            'body_html' => $bodyHtml,
            'cta_label' => ($ctaLabel !== '' ? $ctaLabel : null),
            'cta_url' => ($ctaUrl !== '' ? $ctaUrl : null),
            'audience' => $audience,
            'audience_params_json' => $audParams,
            'status' => $status,
            'scheduled_at' => $scheduledAtDb,
        ];

        if ($status === 'scheduled') {
            $readyErrors = $this->validateCampaignReady($data);
            if ($readyErrors) {
                remember_input($_POST);
                inline_notice_set('email_campaign_form', 'Revisá la campaña antes de programarla: ' . implode(' · ', $readyErrors), 'error');
                redirect(url('/admin/marketing/email/campanas' . ($id > 0 ? ('/edit?id=' . $id) : '/new')));
                return;
            }
        }

        if ($id > 0) {
            if (!$repo->update($id, $data)) {
                inline_notice_set('email_campaign_show', 'La campaña cambió de estado y ya no se puede actualizar.', 'error');
                redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
                return;
            }
            flash('ok', 'Campaña actualizada.');
        } else {
            $id = $repo->create($data);
            flash('ok', 'Campaña creada.');
        }

        clear_old();

        redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
    }

    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $repo = new EmailCampaignRepository(db());
        $campaign = $id > 0 ? $repo->find($id) : null;
        if (!$campaign) {
            flash('error', 'Campaña no encontrada.');
            redirect(url('/admin/marketing/email/campanas'));
            return;
        }

        $orders = $repo->listAttributedOrders($id, 50);
        $stats = $repo->getStats($id);
        $notice = inline_notice_get('email_campaign_show');

        $vm = (new MarketingEmailCampaignShowViewModel())->build([
            'campaign' => $campaign,
            'orders' => $orders,
            'stats' => $stats,
            'notice' => $notice,
            'can_edit' => $repo->canEdit($id),
        ]);

        render_page('admin/marketing_email/campaign_show', array_merge($vm, [
            'pageId' => 'admin-email-campaign-show',
            'title' => 'Detalle de campaña',
        ]));
    }

    public function scheduleNow(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect(url('/admin/marketing/email/campanas'));
            return;
        }


        $repo = new EmailCampaignRepository(db());
        $c = $repo->find($id);
        if (!$c) {
            flash('error', 'No se encontró la campaña.');
            redirect(url('/admin/marketing/email/campanas'));
            return;
        }

        $errors = $this->validateCampaignReady($c);
        if (!empty($errors)) {
            inline_notice_set('email_campaign_show', "No se puede programar todavía: " . implode(" · ", $errors), 'error');
            redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
            return;
        }

        // Transición atómica: solo de borrador y sin destinatarios encolados.
        if (!$repo->scheduleNowIfDraft($id)) {
            inline_notice_set('email_campaign_show', 'Solo se puede programar ahora una campaña en borrador que no haya comenzado a enviarse.', 'error');
            redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
            return;
        }

        // Opcional: encolar inmediatamente (misma request)
        try {
            (new EmailBroadcastSchedulerService(db()))->run(800);
        } catch (\Throwable $e) {
            // best-effort
        }

        inline_notice_set('email_campaign_show', 'Campaña programada para enviar.', 'success');
        redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
    }


    /**
     * Validaciones "hard" para permitir programar/enviar una campaña.
     * Devuelve lista de errores (strings) si hay bloqueos.
     */
    private function validateCampaignReady(array $c): array
    {
        $errors = [];

        $subject = trim((string)($c['subject'] ?? ''));
        // preheader es opcional (la UI lo indica).
        $bodyHtml = (string)($c['body_html'] ?? '');
        $ctaLabel = trim((string)($c['cta_label'] ?? ''));
        $ctaUrl = trim((string)($c['cta_url'] ?? ''));

        if ($subject === '') {
            $errors[] = 'Asunto vacío';
        }

        if (trim(strip_tags($bodyHtml)) === '') {
            $errors[] = 'Contenido vacío';
        }

        // CTA: si hay label, debe haber URL
        if ($ctaLabel !== '' && $ctaUrl === '') {
            $errors[] = 'CTA sin link';
        }

        // URL CTA válida (si existe)
        if ($ctaUrl !== '' && !$this->isValidCtaUrl($ctaUrl)) {
            $errors[] = 'CTA con link inválido';
        }

        // Imágenes: src debe ser absoluto (http/https) o path absoluto (/...)
        $imgSrcs = [];
        if ($bodyHtml !== '') {
            if (preg_match_all('/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/iu', $bodyHtml, $m)) {
                $imgSrcs = $m[2] ?? [];
            }
        }

        foreach ($imgSrcs as $src) {
            $src = trim((string)$src);
            if ($src === '') {
                $errors[] = 'Imagen con src vacío';
                continue;
            }
            $ok = (
                str_starts_with($src, 'http://') ||
                str_starts_with($src, 'https://') ||
                str_starts_with($src, '/')
            );
            if (!$ok) {
                $errors[] = 'Imagen con URL inválida';
                break; // con una alcanza
            }
        }

        return $errors;
    }

    private function isValidCtaUrl(string $url): bool
    {
        $u = trim($url);
        if ($u === '') return false;

        // Permitimos:
        // - http(s)://...
        // - /ruta (compatible subcarpeta con url())
        // - #anchor
        // - mailto:
        if (preg_match('#^https?://#i', $u)) return true;
        if (str_starts_with($u, '/')) return true;
        if (str_starts_with($u, '#')) return true;
        if (preg_match('#^mailto:#i', $u)) return true;

        return false;
    }

    public function cancel(): void
    {
        if (!verify_csrf()) { http_response_code(403); exit('CSRF'); }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db = db();
            if ((new EmailCampaignRepository($db))->cancelIfActive($id)) {
                (new EmailQueueRepository($db))->cancelCampaignQueued($id);
                flash('ok', 'Campaña cancelada. Los correos pendientes quedaron cancelados.');
            } else {
                inline_notice_set('email_campaign_show', 'Solo se puede cancelar una campaña programada o en proceso.', 'error');
            }
        }
        redirect(url('/admin/marketing/email/campanas/show?id=' . $id));
    }
}





