<?php
namespace App\Controllers\Admin;

use App\Repositories\Marketing\EmailTemplateRepository;
use App\Repositories\Marketing\EmailCampaignRepository;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailTemplatesIndexViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailTemplatesShowViewModel;
use App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination;
use App\Services\Marketing\EmailTemplateService;

final class EmailTemplatesController extends BaseController
{
    public function index(): void
    {
        $db = db();
        $repo = new EmailTemplateRepository($db);
        $per = (int)($_GET['per'] ?? 25);
        if (!in_array($per,[25,50],true)) $per=25;
        $total=$repo->countForAdmin();
        $page=max(1,min(max(1,(int)ceil($total/$per)),(int)($_GET['page'] ?? 1)));
        $templates=$repo->listLite($per,($page-1)*$per);
        $notice = inline_notice_get('email_templates');
        $vm = (new MarketingEmailTemplatesIndexViewModel())->build(is_array($templates) ? $templates : [], $notice);
        $vm = array_merge($vm,MarketingEmailPagination::build('/admin/marketing/email/templates',$total,$per,$page));
        $vm['templates_total'] = $total;

        render_page('admin/marketing_email/templates_index', [
            'pageId' => 'admin-email-templates',
            'title' => 'Plantillas de Email',
            'templates' => $templates,
            'templates_payload' => $vm,
        ]);
    }

    public function show(): void
    {
        $db = db();
        $repo = new EmailTemplateRepository($db);
        $id = (int)($_GET['id'] ?? 0);
        $tpl = $id > 0 ? $repo->find($id) : null;
        if (!$tpl) {
            http_response_code(404);
            echo 'Plantilla no encontrada';
            exit;
        }

        $vm = (new MarketingEmailTemplatesShowViewModel())->build(is_array($tpl) ? $tpl : []);
        render_page('admin/marketing_email/templates_show', [
            'pageId' => 'admin-email-templates',
            'title' => (string)($vm['page_title'] ?? 'Plantilla'),
            'template_show' => is_array($vm['template_show'] ?? null) ? $vm['template_show'] : [],
        ]);
    }

    public function create(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            exit('CSRF');
        }

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') { $name = trim((string)($_POST['template_name'] ?? '')); }
$bodyHtml = (string)($_POST['body_html'] ?? '');
        $subjectDefault = trim((string)($_POST['subject_default'] ?? ''));
        $preheaderDefault = trim((string)($_POST['preheader_default'] ?? ''));
        $editorMode = trim((string)($_POST['editor_mode'] ?? 'visual'));
        $returnTo = (string)($_POST['return_to'] ?? '/admin/marketing/email/templates');

        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/admin/marketing/email/templates';
        }

        $db = db();
        $repo = new EmailTemplateRepository($db);
        $svc = new EmailTemplateService($repo);

        $res = $svc->createTemplate([
            'name' => $name,
            'body_html' => $bodyHtml,
            'subject_default' => ($subjectDefault !== '' ? $subjectDefault : null),
            'preheader_default' => ($preheaderDefault !== '' ? $preheaderDefault : null),
            'editor_mode' => $editorMode,
        ]);

        if (!$res['ok']) {
            inline_notice_set('email_templates', (string)($res['error'] ?? 'No se pudo guardar la plantilla'), 'error');
            redirect($returnTo);
            return;
        }

        inline_notice_set('email_templates', 'Plantilla guardada correctamente.', 'success');
        redirect($returnTo);
    }

    public function delete(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            exit('CSRF');
        }

        $id = (int)($_POST['id'] ?? 0);
        $returnTo = (string)($_POST['return_to'] ?? '/admin/marketing/email/templates');
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/admin/marketing/email/templates';
        }

        if ($id <= 0) {
            inline_notice_set('email_templates', 'Plantilla inválida.', 'error');
            redirect($returnTo);
            return;
        }

        $db = db();
        $repo = new EmailTemplateRepository($db);
        $repo->delete($id);

        inline_notice_set('email_templates', 'Plantilla eliminada.', 'success');
        redirect($returnTo);
    }

    public function applyToCampaign(): void
    {
        if (!verify_csrf()) {
            http_response_code(403);
            exit('CSRF');
        }

        $campaignId = (int)($_POST['campaign_id'] ?? 0);
        $templateId = (int)($_POST['template_id'] ?? 0);

        if ($campaignId <= 0 || $templateId <= 0) {
            inline_notice_set('email_campaign_form', 'Faltan datos para aplicar la plantilla.', 'error');
            redirect('/admin/marketing/email/campanas/edit?id=' . (int)$campaignId);
            return;
        }

        $db = db();
        $tplRepo = new EmailTemplateRepository($db);
        $campRepo = new EmailCampaignRepository($db);

        $tpl = $tplRepo->find($templateId);
        if (!$tpl) {
            inline_notice_set('email_campaign_form', 'La plantilla no existe.', 'error');
            redirect('/admin/marketing/email/campanas/edit?id=' . (int)$campaignId);
            return;
        }

        $campaign = $campRepo->find($campaignId);
        if (!$campaign) {
            inline_notice_set('email_campaign_form', 'La campaña no existe.', 'error');
            redirect('/admin/marketing/email/campanas');
            return;
        }

        if (!$campRepo->canEdit($campaignId)) {
            inline_notice_set('email_campaign_show', 'No se puede aplicar una plantilla a una campaña que ya empezó a enviarse.', 'error');
            redirect(url('/admin/marketing/email/campanas/show?id=' . $campaignId));
            return;
        }

        // Reemplaza el contenido actual (MVP)
        $data = $campaign;
        $data['body_html'] = (string)($tpl['body_html'] ?? '');
        // Defaults opcionales: solo si el campo de la campaña está vacío.
        if (trim((string)($data['subject'] ?? '')) === '' && !empty($tpl['subject_default'])) {
            $data['subject'] = (string)$tpl['subject_default'];
        }
        if (trim((string)($data['preheader'] ?? '')) === '' && !empty($tpl['preheader_default'])) {
            $data['preheader'] = (string)$tpl['preheader_default'];
        }

        if (!$campRepo->update($campaignId, $data)) {
            inline_notice_set('email_campaign_show', 'La campaña ya no admite cambios; no se aplicó la plantilla.', 'error');
            redirect(url('/admin/marketing/email/campanas/show?id=' . $campaignId));
            return;
        }

        inline_notice_set('email_campaign_form', 'Plantilla aplicada. Recordá revisar el contenido antes de enviar.', 'success');
        redirect('/admin/marketing/email/campanas/edit?id=' . (int)$campaignId);
    }
}
