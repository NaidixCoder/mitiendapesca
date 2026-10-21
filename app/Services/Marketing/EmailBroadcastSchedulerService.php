<?php
namespace App\Services\Marketing;

use App\Repositories\Marketing\EmailCampaignRepository;
use App\Repositories\Marketing\EmailClickTokenRepository;
use App\Repositories\Marketing\EmailQueueRepository;
use PDO;

/**
 * Etapa 3: Campañas broadcast.
 * - Encola campañas programadas.
 * - Genera links trackeados (click -> asigna campaign en sesión).
 * - Finaliza campañas cuando ya no quedan jobs en cola/sending.
 */
final class EmailBroadcastSchedulerService
{
    public function __construct(private PDO $db) {}

    /**
     * @return array{campaigns_seen:int,enqueued:int,finalized:int}
     */
    public function run(int $limitEnqueue = 500): array
    {
        $limitEnqueue = max(10, min(5000, $limitEnqueue));

        $campRepo = new EmailCampaignRepository($this->db);
        $queueRepo = new EmailQueueRepository($this->db);
        $clickRepo = new EmailClickTokenRepository($this->db);

        $stats = ['campaigns_seen' => 0, 'enqueued' => 0, 'finalized' => 0];

        $campaigns = $campRepo->dueToSchedule(25);
        foreach ($campaigns as $c) {
            $stats['campaigns_seen']++;
            $campaignId = (int)($c['id'] ?? 0);
            if ($campaignId <= 0) continue;

            // Enviar solo si está scheduled/sending
            $status = (string)($c['status'] ?? 'draft');
            if (!in_array($status, ['scheduled','sending'], true)) continue;

            // Encolar (idempotente por dedupe_key)
            $enq = $this->enqueueCampaign($c, $limitEnqueue, $queueRepo, $clickRepo);
            $stats['enqueued'] += $enq;

            // Si encolamos algo y estaba scheduled, pasa a sending
            if ($enq > 0 && $status === 'scheduled') {
                $campRepo->setStatus($campaignId, 'sending', null);
            }

            // Finalizar si ya no queda nada por enviar
            if ($this->shouldFinalize($campaignId, $c)) {
                $campRepo->markSent($campaignId);
                $stats['finalized']++;
            }
        }

        return $stats;
    }

    private function enqueueCampaign(array $campaign, int $limit, EmailQueueRepository $queueRepo, EmailClickTokenRepository $clickRepo): int
    {
        $campaignId = (int)($campaign['id'] ?? 0);
        if ($campaignId <= 0) return 0;

        $subject = (string)($campaign['subject'] ?? '');
        $preheader = (string)($campaign['preheader'] ?? '');
        $bodyHtml = (string)($campaign['body_html'] ?? '');
        $ctaLabel = (string)($campaign['cta_label'] ?? 'Ver');
        $ctaUrl = (string)($campaign['cta_url'] ?? '/');

// Guard rails: si no está lista, no encolar (evita campañas rotas).
$errors = $this->validateCampaignReady($subject, $preheader, $bodyHtml, $ctaLabel, $ctaUrl);
if (!empty($errors)) {
    try {
        (new EmailCampaignRepository($this->db))->setStatus($campaignId, 'draft', null);
    } catch (\Throwable $e) {
        // best-effort
    }
    error_log('[EmailBroadcastScheduler] campaign #' . $campaignId . ' no encolada: ' . implode(' | ', $errors));
    return 0;
}


        $audience = (string)($campaign['audience'] ?? 'all');
        $audParams = $this->decodeJson($campaign['audience_params_json'] ?? null);

        $rows = $this->selectAudience($audience, $audParams, $limit, $campaignId);
        if (!$rows) return 0;

        $scheduledAt = (string)($campaign['scheduled_at'] ?? date('Y-m-d H:i:s'));

        $count = 0;
        foreach ($rows as $r) {
            $sid = (int)($r['subscriber_id'] ?? 0);
            $to = (string)($r['email'] ?? '');
            if ($sid <= 0 || $to === '') continue;

            $userId = isset($r['user_id']) ? (int)($r['user_id'] ?? 0) : 0;
            $unsubTok = (string)($r['unsubscribe_token'] ?? '');

            // Token único por campaña + suscriptor (un solo CTA)
            $token = $clickRepo->createToken([
                'campaign_id' => $campaignId,
                'queue_id' => null,
                'subscriber_id' => $sid,
                'user_id' => ($userId > 0 ? $userId : null),
                'destination_url' => $ctaUrl !== '' ? $ctaUrl : '/',
            ]);
            $trackedUrl = url('/email/click?t=' . urlencode($token));

            $payload = [
                'preheader' => $preheader,
                'body_html' => $bodyHtml,
                'cta_label' => $ctaLabel !== '' ? $ctaLabel : 'Ver',
                'cta_url' => $trackedUrl,
            ];

            $dedupe = hash('sha256', 'email_campaign|' . $campaignId . '|sub|' . $sid);

            $ok = $queueRepo->enqueue([
                'dedupe_key' => $dedupe,
                'to_email' => $to,
                'subject' => $subject,
                'template' => 'marketing/broadcast_campaign',
                'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'unsubscribe_token' => $unsubTok ?: null,
                'subscriber_id' => $sid,
                'user_id' => ($userId > 0 ? $userId : null),
                'automation_step_id' => null,
                'entity_type' => 'email_campaign',
                'entity_id' => $campaignId,
                'scheduled_at' => $scheduledAt,
            ]);

            if ($ok) $count++;
        }

        return $count;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function selectAudience(string $audience, array $params, int $limit, int $campaignId): array
    {
        $limit = max(1, min(5000, $limit));

        // Base: suscriptores activos
        // Excluir a los destinatarios que ya fueron incorporados a esta campaña,
        // incluso si sus correos ya se enviaron, fallaron o fueron cancelados.
        // Así, cada ejecución del cron toma el siguiente lote sin repetir el primero.
        $where = "s.status='subscribed' AND NOT EXISTS (
            SELECT 1 FROM email_queue queued
            WHERE queued.entity_type='email_campaign'
              AND queued.entity_id=:campaign_id
              AND queued.subscriber_id=s.id
        )";
        $join = "LEFT JOIN users u ON u.email = s.email";
        $having = '';

        if ($audience === 'buyers') {
            $where .= " AND u.id IS NOT NULL";
            $having = "HAVING confirmed_orders > 0";
        } elseif ($audience === 'non_buyers') {
            // Incluye suscriptores sin user o sin compras confirmadas
            $having = "HAVING confirmed_orders = 0";
        } elseif ($audience === 'inactive_buyers') {
            $days = (int)($params['days'] ?? 60);
            $days = max(7, min(3650, $days));
            $where .= " AND u.id IS NOT NULL";
            $having = "HAVING confirmed_orders > 0 AND (last_confirmed_order_at IS NULL OR last_confirmed_order_at < (NOW() - INTERVAL {$days} DAY))";
        } elseif ($audience === 'vip') {
            $minTotal = (int)($params['min_total'] ?? 200000);
            $minTotal = max(0, $minTotal);
            $where .= " AND u.id IS NOT NULL";
            $having = "HAVING confirmed_orders > 0 AND confirmed_total >= {$minTotal}";
        }

        $sql = "
            SELECT
              s.id AS subscriber_id,
              s.email,
              s.unsubscribe_token,
              u.id AS user_id,
              COALESCE(SUM(CASE WHEN o.status IN ('paid','processing','packed','shipped','delivered') THEN 1 ELSE 0 END), 0) AS confirmed_orders,
              COALESCE(SUM(CASE WHEN o.status IN ('paid','processing','packed','shipped','delivered') THEN o.total ELSE 0 END), 0) AS confirmed_total,
              MAX(CASE WHEN o.status IN ('paid','processing','packed','shipped','delivered') THEN o.created_at ELSE NULL END) AS last_confirmed_order_at
            FROM email_subscribers s
            {$join}
            LEFT JOIN orders o ON o.user_id = u.id
            WHERE {$where}
            GROUP BY s.id
            {$having}
            ORDER BY s.id ASC
            LIMIT {$limit}
        ";

        $st = $this->db->prepare($sql);
        $st->execute([':campaign_id' => $campaignId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function shouldFinalize(int $campaignId, array $campaign): bool
    {
        // Cola vacía no significa campaña completa si hay destinatarios todavía sin encolar.
        $remaining = $this->selectAudience(
            (string)($campaign['audience'] ?? 'all'),
            $this->decodeJson($campaign['audience_params_json'] ?? null),
            1,
            $campaignId
        );
        if ($remaining) return false;
        $st = $this->db->prepare(
            "SELECT COUNT(*)
               FROM email_queue
              WHERE entity_type='email_campaign'
                AND entity_id=:id
                AND status IN ('queued','sending')"
        );
        $st->execute([':id' => $campaignId]);
        return ((int)$st->fetchColumn() === 0);
    }

    private function decodeJson($json): array
    {
        if ($json === null || $json === '') return [];
        if (is_array($json)) return $json;
        $d = json_decode((string)$json, true);
        return is_array($d) ? $d : [];
    }
/**
 * @return string[] errores
 */
private function validateCampaignReady(string $subject, string $preheader, string $bodyHtml, string $ctaLabel, string $ctaUrl): array
{
    $errors = [];
    if (trim($subject) === '') $errors[] = 'subject vacío';
    // preheader es opcional (la UI lo indica).
    if (trim(strip_tags($bodyHtml)) === '') $errors[] = 'body vacío';
    if (trim($ctaLabel) !== '' && trim($ctaUrl) === '') $errors[] = 'cta sin url';
    if (trim($ctaUrl) !== '' && !$this->isValidUrlOrPath(trim($ctaUrl))) $errors[] = 'cta url inválida';
    if (!empty($this->findInvalidImgSrcs($bodyHtml))) $errors[] = 'img src inválido';
    return $errors;
}

private function isValidUrlOrPath(string $url): bool
{
    if ($url === '') return false;
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return true;
    if (str_starts_with($url, '/')) return true;
    if (str_starts_with($url, '#')) return true;
    if (str_starts_with($url, 'mailto:')) return true;
    return false;
}

private function findInvalidImgSrcs(string $html): array
{
    $bad = [];
    if (trim($html) === '') return $bad;
    if (preg_match_all('/<img\b[^>]*\bsrc\s*=\s*([\"\'])(.*?)\1/i', $html, $m)) {
        foreach ($m[2] as $src) {
            $s = trim(html_entity_decode($src));
            if ($s === '') { $bad[] = $s; continue; }
            if (str_starts_with($s, 'http://') || str_starts_with($s, 'https://')) continue;
            if (str_starts_with($s, 'data:')) continue;
            if (str_starts_with($s, '/')) continue;
            $bad[] = $s;
        }
    }
    return $bad;
}
}
