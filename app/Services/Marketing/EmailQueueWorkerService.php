<?php
namespace App\Services\Marketing;

use App\Mail\Core\Mailer;
use App\Repositories\Marketing\EmailQueueRepository;
use App\Repositories\Marketing\EmailSubscriberRepository;
use App\Repositories\Marketing\EmailCampaignRepository;
use App\Repositories\Settings\AppSettingsRepository;

use PDO;

final class EmailQueueWorkerService
{
    public function __construct(private PDO $db) {}

    public function processDue(int $limit = 25): array
    {
        $limit = max(1, min(200, $limit));

        $repo = new EmailQueueRepository($this->db);
        $subscribers = new EmailSubscriberRepository($this->db);
        $campaigns = new EmailCampaignRepository($this->db);
        $settings = new AppSettingsRepository($this->db);

        // Etapa 4: caps de frecuencia + open tracking (habilitables desde Ajustes)
        $openTracking = (string)($settings->get('email_marketing_open_tracking', '0') ?? '0') === '1';
        $capDay = (int)($settings->get('email_marketing_cap_per_day', '') ?: 0);
        $capWeek = (int)($settings->get('email_marketing_cap_per_week', '') ?: 0);
        $capDay = max(0, min(20, $capDay));
        $capWeek = max(0, min(100, $capWeek));
        $jobs = $repo->claimDue($limit);

        $stats = ['processed' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($jobs as $j) {
            $id = (int)($j['id'] ?? 0);
            if ($id <= 0) continue;

            $to = (string)($j['to_email'] ?? '');
            $subject = (string)($j['subject'] ?? '');
            $template = (string)($j['template'] ?? '');
            $payload = $this->decodeJson($j['payload_json'] ?? null);
            $unsubTok = (string)($j['unsubscribe_token'] ?? ($payload['unsubscribe_token'] ?? ''));

            $stats['processed']++;

            if (($j['entity_type'] ?? '') === 'email_campaign'
                && !$campaigns->maySend((int)($j['entity_id'] ?? 0))) {
                $repo->cancelClaimed($id, 'Campaña cancelada o no vigente');
                continue;
            }

            // El consentimiento puede cambiar después del encolado.
            // Nunca enviar comunicaciones de marketing a alguien dado de baja.
            $subscriberId = (int)($j['subscriber_id'] ?? 0);
            if ($subscriberId > 0 && !$subscribers->isSubscribedById($subscriberId)) {
                $repo->cancelClaimed($id, 'Suscripción no activa al momento del envío');
                continue;
            }

            // Caps: si el usuario ya recibió demasiados emails, reprogramamos el job
            if ($to !== '' && ($capDay > 0 || $capWeek > 0)) {
                $sent24h = $capDay > 0 ? $repo->countSentToEmailSince($to, 60 * 24) : 0;
                $sent7d = $capWeek > 0 ? $repo->countSentToEmailSince($to, 60 * 24 * 7) : 0;
                if (($capDay > 0 && $sent24h >= $capDay) || ($capWeek > 0 && $sent7d >= $capWeek)) {
                    $repo->defer($id, 60, 'Límite de frecuencia alcanzado (reprogramado 60m)');
                    continue;
                }
            }

            try {
                $unsubUrl = $unsubTok ? url('/newsletter/unsubscribe?token=' . urlencode($unsubTok)) : '';
                $payload['unsubscribe_url'] = $unsubUrl;

                // Open tracking (pixel)
                $openToken = null;
                if ($openTracking) {
                    $openToken = hash('sha256', random_bytes(32));
                    $payload['open_pixel_url'] = url('/email/open?t=' . urlencode($openToken));
                }

                $content = Mailer::render($template, $payload);
                $html = Mailer::wrapLayout($subject, $content);

                if ($openTracking && !empty($payload['open_pixel_url'])) {
                    $html = $this->injectPixel($html, (string)$payload['open_pixel_url']);
                }

                $ok = Mailer::sendHtml($to, $subject, $html);

                if ($ok) {
                    $repo->markSent($id);
                    $repo->insertLog([
                        'queue_id' => $id,
                        'to_email' => $to,
                        'status' => 'sent',
                        'subject' => $subject,
                        'template' => $template,
                        'subscriber_id' => $j['subscriber_id'] ?? null,
                        'user_id' => $j['user_id'] ?? null,
                        'automation_step_id' => $j['automation_step_id'] ?? null,
                        'entity_type' => $j['entity_type'] ?? null,
                        'entity_id' => $j['entity_id'] ?? null,
                        'error_text' => null,
                        'open_token' => $openToken,
                    ]);
                    $stats['sent']++;
                } else {
                    $repo->markFailed($id, 'mail() devolvió false');
                    $repo->insertLog([
                        'queue_id' => $id,
                        'to_email' => $to,
                        'status' => 'failed',
                        'subject' => $subject,
                        'template' => $template,
                        'subscriber_id' => $j['subscriber_id'] ?? null,
                        'user_id' => $j['user_id'] ?? null,
                        'automation_step_id' => $j['automation_step_id'] ?? null,
                        'entity_type' => $j['entity_type'] ?? null,
                        'entity_id' => $j['entity_id'] ?? null,
                        'error_text' => 'mail() devolvió false',
                        'open_token' => $openToken,
                    ]);
                    $stats['failed']++;
                }
            } catch (\Throwable $e) {
                $repo->markFailed($id, $e->getMessage());
                $repo->insertLog([
                    'queue_id' => $id,
                    'to_email' => $to,
                    'status' => 'failed',
                    'subject' => $subject,
                    'template' => $template,
                    'subscriber_id' => $j['subscriber_id'] ?? null,
                    'user_id' => $j['user_id'] ?? null,
                    'automation_step_id' => $j['automation_step_id'] ?? null,
                    'entity_type' => $j['entity_type'] ?? null,
                    'entity_id' => $j['entity_id'] ?? null,
                    'error_text' => mb_substr($e->getMessage(), 0, 250),
                    'open_token' => null,
                ]);
                $stats['failed']++;
            }
        }

        return $stats;
    }

    private function decodeJson($json): array
    {
        if ($json === null || $json === '') return [];
        if (is_array($json)) return $json;
        $d = json_decode((string)$json, true);
        return is_array($d) ? $d : [];
    }

    private function injectPixel(string $html, string $pixelUrl): string
    {
        $pixelUrl = trim($pixelUrl);
        if ($pixelUrl === '') return $html;
        $img = '<img src="' . htmlspecialchars($pixelUrl, ENT_QUOTES, 'UTF-8') . '" width="1" height="1" style="display:none" alt="" />';

        // Insertar antes de </body> si existe, sino al final.
        if (stripos($html, '</body>') !== false) {
            return preg_replace('~</body>~i', $img . '</body>', $html, 1) ?: ($html . $img);
        }
        return $html . $img;
    }
}
