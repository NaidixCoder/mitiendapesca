<?php
namespace App\Repositories\Marketing;

use PDO;

final class EmailCampaignRepository
{
    public function __construct(private PDO $db) {}

    public function countForAdmin(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM email_campaigns")->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function listWithStats(int $limit = 120, int $offset = 0): array
    {
        $limit = max(1, min(500, $limit));
        $offset = max(0, $offset);

        $sql = "
            SELECT
              c.*,
              COALESCE(s.sent_count, 0) AS sent_count,
              COALESCE(o.open_count, 0) AS open_count,
              COALESCE(k.click_count, 0) AS click_count,
              COALESCE(r.revenue_total, 0) AS revenue_total,
              COALESCE(r.orders_count, 0) AS orders_count
            FROM email_campaigns c
            LEFT JOIN (
              SELECT entity_id AS campaign_id, COUNT(*) AS sent_count
                FROM email_send_logs
               WHERE status = 'sent' AND entity_type = 'email_campaign'
               GROUP BY entity_id
            ) s ON s.campaign_id = c.id
            LEFT JOIN (
              SELECT entity_id AS campaign_id, COUNT(*) AS open_count
                FROM email_send_logs
               WHERE status = 'sent' AND entity_type = 'email_campaign' AND opened_at IS NOT NULL
               GROUP BY entity_id
            ) o ON o.campaign_id = c.id
            LEFT JOIN (
              SELECT campaign_id, COUNT(*) AS click_count
                FROM email_click_tokens
               WHERE clicked_at IS NOT NULL
               GROUP BY campaign_id
            ) k ON k.campaign_id = c.id
            LEFT JOIN (
              SELECT email_campaign_id AS campaign_id,
                     COUNT(*) AS orders_count,
                     COALESCE(SUM(total), 0) AS revenue_total
                FROM orders
               WHERE email_campaign_id IS NOT NULL
                 AND status IN ('paid','processing','packed','shipped','delivered')
               GROUP BY email_campaign_id
            ) r ON r.campaign_id = c.id
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Stats agregados de una campaña.
     * @return array{sent_count:int,open_count:int,click_count:int,orders_count:int,revenue_total:int}
     */
    public function getStats(int $campaignId): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) {
            return ['sent_count' => 0, 'open_count' => 0, 'click_count' => 0, 'orders_count' => 0, 'revenue_total' => 0];
        }

        $sql = "
            SELECT
              COALESCE(s.sent_count, 0) AS sent_count,
              COALESCE(o.open_count, 0) AS open_count,
              COALESCE(k.click_count, 0) AS click_count,
              COALESCE(r.orders_count, 0) AS orders_count,
              COALESCE(r.revenue_total, 0) AS revenue_total
            FROM email_campaigns c
            LEFT JOIN (
              SELECT entity_id AS campaign_id, COUNT(*) AS sent_count
                FROM email_send_logs
               WHERE status='sent' AND entity_type='email_campaign'
               GROUP BY entity_id
            ) s ON s.campaign_id = c.id
            LEFT JOIN (
              SELECT entity_id AS campaign_id, COUNT(*) AS open_count
                FROM email_send_logs
               WHERE status='sent' AND entity_type='email_campaign' AND opened_at IS NOT NULL
               GROUP BY entity_id
            ) o ON o.campaign_id = c.id
            LEFT JOIN (
              SELECT campaign_id, COUNT(*) AS click_count
                FROM email_click_tokens
               WHERE clicked_at IS NOT NULL
               GROUP BY campaign_id
            ) k ON k.campaign_id = c.id
            LEFT JOIN (
              SELECT email_campaign_id AS campaign_id,
                     COUNT(*) AS orders_count,
                     COALESCE(SUM(total), 0) AS revenue_total
                FROM orders
               WHERE email_campaign_id IS NOT NULL
                 AND status IN ('paid','processing','packed','shipped','delivered')
               GROUP BY email_campaign_id
            ) r ON r.campaign_id = c.id
            WHERE c.id = :id
            LIMIT 1";

        $st = $this->db->prepare($sql);
        $st->execute([':id' => $campaignId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'sent_count' => (int)($row['sent_count'] ?? 0),
            'open_count' => (int)($row['open_count'] ?? 0),
            'click_count' => (int)($row['click_count'] ?? 0),
            'orders_count' => (int)($row['orders_count'] ?? 0),
            'revenue_total' => (int)($row['revenue_total'] ?? 0),
        ];
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM email_campaigns WHERE id = :id LIMIT 1');
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $st = $this->db->prepare(
            'INSERT INTO email_campaigns
              (name, subject, preheader, body_html, cta_label, cta_url, audience, audience_params_json, status, scheduled_at, created_at, updated_at)
             VALUES
              (:name, :subject, :preheader, :body_html, :cta_label, :cta_url, :audience, :audience_params_json, :status, :scheduled_at, NOW(), NOW())'
        );

        $st->execute([
            ':name' => (string)($data['name'] ?? ''),
            ':subject' => (string)($data['subject'] ?? ''),
            ':preheader' => $data['preheader'] ?? null,
            ':body_html' => (string)($data['body_html'] ?? ''),
            ':cta_label' => $data['cta_label'] ?? null,
            ':cta_url' => $data['cta_url'] ?? null,
            ':audience' => (string)($data['audience'] ?? 'all'),
            ':audience_params_json' => $data['audience_params_json'] ?? null,
            ':status' => (string)($data['status'] ?? 'draft'),
            ':scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Solo se pueden editar campañas que todavía no iniciaron el encolado.
     * La condición se repite dentro del UPDATE para evitar carreras con el scheduler.
     */
    public function canEdit(int $id): bool
    {
        if ($id <= 0) return false;
        $st = $this->db->prepare(
            "SELECT 1 FROM email_campaigns c
              WHERE c.id=:id AND c.status IN ('draft','scheduled')
                AND NOT EXISTS (
                    SELECT 1 FROM email_queue q
                     WHERE q.entity_type='email_campaign' AND q.entity_id=c.id
                ) LIMIT 1"
        );
        $st->execute([':id' => $id]);
        return (bool)$st->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        $st = $this->db->prepare(
            "UPDATE email_campaigns
                SET name=:name,
                    subject=:subject,
                    preheader=:preheader,
                    body_html=:body_html,
                    cta_label=:cta_label,
                    cta_url=:cta_url,
                    audience=:audience,
                    audience_params_json=:audience_params_json,
                    status=:status,
                    scheduled_at=:scheduled_at,
                    updated_at=NOW()
              WHERE id=:id
                AND status IN ('draft','scheduled')
                AND NOT EXISTS (
                  SELECT 1 FROM email_queue q
                   WHERE q.entity_type='email_campaign' AND q.entity_id=:guard_id
                )"
        );

        $st->execute([
            ':id' => $id,
            ':guard_id' => $id,
            ':name' => (string)($data['name'] ?? ''),
            ':subject' => (string)($data['subject'] ?? ''),
            ':preheader' => $data['preheader'] ?? null,
            ':body_html' => (string)($data['body_html'] ?? ''),
            ':cta_label' => $data['cta_label'] ?? null,
            ':cta_url' => $data['cta_url'] ?? null,
            ':audience' => (string)($data['audience'] ?? 'all'),
            ':audience_params_json' => $data['audience_params_json'] ?? null,
            ':status' => (string)($data['status'] ?? 'draft'),
            ':scheduled_at' => $data['scheduled_at'] ?? null,
        ]);
        // rowCount() es 0 también en un UPDATE sin cambios; no señalar
        // un bloqueo falso cuando sigue siendo editable.
        return $st->rowCount() > 0 || $this->canEdit($id);
    }

    /** Programar de inmediato una campaña en borrador, sin destinatarios previos. */
    public function scheduleNowIfDraft(int $id): bool
    {
        if ($id <= 0) return false;
        $st = $this->db->prepare(
            "UPDATE email_campaigns
                SET status='scheduled', scheduled_at=NOW(), updated_at=NOW()
              WHERE id=:id AND status='draft'
                AND NOT EXISTS (
                    SELECT 1 FROM email_queue q
                     WHERE q.entity_type='email_campaign' AND q.entity_id=:guard_id
                )"
        );
        $st->execute([':id' => $id, ':guard_id' => $id]);
        return $st->rowCount() === 1;
    }

    /** Cancelar únicamente campañas programadas o que ya están en curso. */
    public function cancelIfActive(int $id): bool
    {
        if ($id <= 0) return false;
        $st = $this->db->prepare(
            "UPDATE email_campaigns
                SET status='cancelled', updated_at=NOW()
              WHERE id=:id AND status IN ('scheduled','sending')"
        );
        $st->execute([':id' => $id]);
        return $st->rowCount() === 1;
    }

    public function setStatus(int $id, string $status, ?string $scheduledAt = null): void
    {
        $allowed = ['draft','scheduled','sending','sent','cancelled'];
        if (!in_array($status, $allowed, true)) return;

        $st = $this->db->prepare('UPDATE email_campaigns SET status=:s, scheduled_at=COALESCE(:sa, scheduled_at), updated_at=NOW() WHERE id=:id');
        $st->execute([':id' => $id, ':s' => $status, ':sa' => $scheduledAt]);
    }

    /** Una campaña cancelada o finalizada no puede seguir despachando correos adquiridos. */
    public function maySend(int $id): bool
    {
        if ($id <= 0) return false;
        $st = $this->db->prepare('SELECT status FROM email_campaigns WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        return in_array($st->fetchColumn(), ['scheduled', 'sending'], true);
    }

    public function markSent(int $id): void
    {
        $st = $this->db->prepare("UPDATE email_campaigns SET status='sent', sent_at=NOW(), updated_at=NOW() WHERE id=:id");
        $st->execute([':id' => $id]);
    }

    /**
     * Devuelve campañas que deben encolarse ahora (scheduled_at <= NOW()).
     * @return array<int,array<string,mixed>>
     */
    public function dueToSchedule(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $sql = "SELECT * FROM email_campaigns WHERE status IN ('scheduled','sending') AND scheduled_at IS NOT NULL AND scheduled_at <= NOW() ORDER BY scheduled_at ASC, id ASC LIMIT " . (int)$limit;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,array<string,mixed>> */
    public function listAttributedOrders(int $campaignId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $sql = "SELECT id, user_id, status, total, created_at
                  FROM orders
                 WHERE email_campaign_id = :cid
                   AND status IN ('paid','processing','packed','shipped','delivered')
                 ORDER BY created_at DESC, id DESC
                 LIMIT " . (int)$limit;
        $st = $this->db->prepare($sql);
        $st->execute([':cid' => $campaignId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
