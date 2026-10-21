<?php
namespace App\Repositories\Marketing;

use PDO;

final class EmailQueueRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Encola un email. Usa dedupe_key (SHA-256) para idempotencia.
     * Retorna true si insertó, false si ya existía.
     */
    public function enqueue(array $row): bool
    {
        $sql = 'INSERT IGNORE INTO email_queue
                (dedupe_key, to_email, subject, template, payload_json, unsubscribe_token, subscriber_id, user_id, automation_step_id, entity_type, entity_id, status, scheduled_at, attempts, created_at, updated_at)
                VALUES
                (:dedupe_key, :to_email, :subject, :template, :payload_json, :unsubscribe_token, :subscriber_id, :user_id, :automation_step_id, :entity_type, :entity_id, \'queued\', :scheduled_at, 0, NOW(), NOW())';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':dedupe_key' => (string)($row['dedupe_key'] ?? ''),
            ':to_email' => (string)($row['to_email'] ?? ''),
            ':subject' => (string)($row['subject'] ?? ''),
            ':template' => (string)($row['template'] ?? ''),
            ':payload_json' => $row['payload_json'] ?? null,
            ':unsubscribe_token' => $row['unsubscribe_token'] ?? null,
            ':subscriber_id' => $row['subscriber_id'] ?? null,
            ':user_id' => $row['user_id'] ?? null,
            ':automation_step_id' => $row['automation_step_id'] ?? null,
            ':entity_type' => $row['entity_type'] ?? null,
            ':entity_id' => $row['entity_id'] ?? null,
            ':scheduled_at' => (string)($row['scheduled_at'] ?? date('Y-m-d H:i:s')),
        ]);
        return $st->rowCount() > 0;
    }

    public function countsByStatus(): array
    {
        $rows = $this->db->query('SELECT status, COUNT(*) c FROM email_queue GROUP BY status')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = ['queued'=>0,'sending'=>0,'sent'=>0,'failed'=>0,'cancelled'=>0,'total'=>0];
        foreach ($rows as $r) {
            $s = (string)($r['status'] ?? '');
            $c = (int)($r['c'] ?? 0);
            if (isset($out[$s])) $out[$s] = $c;
            $out['total'] += $c;
        }
        return $out;
    }

    public function countForAdmin(string $q, string $status): int
    {
        $filters = [];
        $params = [];
        if (trim($q) !== '') {
            $filters[] = '(to_email LIKE :qe OR subject LIKE :qs OR template LIKE :qt)';
            $like = '%'.trim($q).'%';
            $params=[':qe'=>$like,':qs'=>$like,':qt'=>$like];
        }
        if (in_array($status, ['queued','sending','sent','failed','cancelled'], true)) {
            $filters[] = 'status=:s';
            $params[':s'] = $status;
        }
        $sql = 'SELECT COUNT(*) FROM email_queue';
        if ($filters) $sql .= ' WHERE '.implode(' AND ', $filters);
        $st=$this->db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public function listQueue(string $q, string $status, int $limit = 200, int $offset = 0): array
    {
        $q = trim($q);
        $status = trim($status);
        $limit = max(1, min(500, $limit));

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(to_email LIKE :q_email OR subject LIKE :q_subject OR template LIKE :q_template)';
            $like = '%' . $q . '%';
            $params[':q_email'] = $like;
            $params[':q_subject'] = $like;
            $params[':q_template'] = $like;
        }
        if (in_array($status, ['queued','sending','sent','failed','cancelled'], true)) {
            $where[] = 'status = :s';
            $params[':s'] = $status;
        }

        $sql = 'SELECT id, to_email, subject, template, status, scheduled_at, attempts, last_error, sent_at, created_at
                  FROM email_queue';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY scheduled_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . max(0, (int)$offset);

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Devuelve hasta $limit emails due (queued y scheduled_at <= now) y los marca como "sending".
     */
    public function claimDue(int $limit = 25): array
    {
        $limit = max(1, min(200, $limit));

        $sql = 'SELECT * FROM email_queue
                 WHERE status = \'queued\' AND scheduled_at <= NOW()
                 ORDER BY scheduled_at ASC, id ASC
                 LIMIT ' . (int)$limit;
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (!$rows) return [];

        $mark = $this->db->prepare('UPDATE email_queue SET status = \'sending\', attempts = attempts + 1, updated_at = NOW() WHERE id = :id AND status = \'queued\'');

        $claimed = [];
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id <= 0) continue;
            $mark->execute([':id' => $id]);
            if ($mark->rowCount() > 0) $claimed[] = $r;
        }
        return $claimed;
    }

    /** Descartar un mensaje adquirido que ya no debe enviarse (por ejemplo, baja de suscripción). */
    public function cancelClaimed(int $id, string $reason): void
    {
        $st = $this->db->prepare(
            "UPDATE email_queue SET status='cancelled', last_error=:reason, updated_at=NOW()
             WHERE id=:id AND status='sending'"
        );
        $st->execute([':id' => $id, ':reason' => mb_substr($reason, 0, 250)]);
    }

    /** Al cancelar una campaña masiva, descartar también sus destinatarios aún en cola. */
    public function cancelCampaignQueued(int $campaignId): int
    {
        if ($campaignId <= 0) return 0;
        $st = $this->db->prepare(
            "UPDATE email_queue SET status='cancelled', last_error='Campaña cancelada',
             updated_at=NOW() WHERE entity_type='email_campaign' AND entity_id=:id AND status='queued'"
        );
        $st->execute([':id' => $campaignId]);
        return $st->rowCount();
    }

    public function markSent(int $id): void
    {
        $st = $this->db->prepare('UPDATE email_queue SET status = \'sent\', sent_at = NOW(), updated_at = NOW() WHERE id = :id');
        $st->execute([':id' => $id]);
    }

    public function markFailed(int $id, string $error): void
    {
        $st = $this->db->prepare('UPDATE email_queue SET status = \'failed\', last_error = :e, updated_at = NOW() WHERE id = :id');
        $st->execute([':id' => $id, ':e' => mb_substr($error, 0, 250)]);
    }

    public function cancelQueued(string $dedupeKey): void
    {
        $st = $this->db->prepare('UPDATE email_queue SET status = \'cancelled\', updated_at = NOW() WHERE dedupe_key = :k AND status = \'queued\'');
        $st->execute([':k' => $dedupeKey]);
    }

    public function insertLog(array $row): void
    {
        $sql = 'INSERT INTO email_send_logs
                (queue_id, to_email, status, subject, template, subscriber_id, user_id, automation_step_id, entity_type, entity_id, error_text, open_token, created_at)
                VALUES
                (:queue_id, :to_email, :status, :subject, :template, :subscriber_id, :user_id, :automation_step_id, :entity_type, :entity_id, :error_text, :open_token, NOW())';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':queue_id' => $row['queue_id'] ?? null,
            ':to_email' => (string)($row['to_email'] ?? ''),
            ':status' => (string)($row['status'] ?? 'failed'),
            ':subject' => (string)($row['subject'] ?? ''),
            ':template' => (string)($row['template'] ?? ''),
            ':subscriber_id' => $row['subscriber_id'] ?? null,
            ':user_id' => $row['user_id'] ?? null,
            ':automation_step_id' => $row['automation_step_id'] ?? null,
            ':entity_type' => $row['entity_type'] ?? null,
            ':entity_id' => $row['entity_id'] ?? null,
            ':error_text' => $row['error_text'] ?? null,
            ':open_token' => $row['open_token'] ?? null,
        ]);
    }

    /** Cuenta envíos OK recientes por destinatario (para frequency caps). */
    public function countSentToEmailSince(string $email, int $minutes): int
    {
        $email = trim($email);
        if ($email === '') return 0;
        $minutes = max(1, min(60 * 24 * 365, $minutes));

        $st = $this->db->prepare(
            "SELECT COUNT(*)
               FROM email_send_logs
              WHERE status = 'sent'
                AND to_email = :e
                AND created_at >= (NOW() - INTERVAL {$minutes} MINUTE)"
        );
        $st->execute([':e' => $email]);
        return (int)($st->fetchColumn() ?: 0);
    }

    /** Reprograma un job ya claim-eado (status=sending) volviéndolo a queued. */
    public function defer(int $id, int $minutes, string $reason): void
    {
        $id = (int)$id;
        if ($id <= 0) return;
        $minutes = max(1, min(60 * 24 * 30, $minutes));
        $reason = mb_substr((string)$reason, 0, 250);

        $st = $this->db->prepare(
            "UPDATE email_queue
                SET status='queued',
                    scheduled_at = (NOW() + INTERVAL {$minutes} MINUTE),
                    last_error = :r,
                    updated_at = NOW()
              WHERE id = :id"
        );
        $st->execute([':id' => $id, ':r' => $reason]);
    }

    public function listLogs(int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $sql = 'SELECT id, to_email, status, subject, template, error_text, created_at
                  FROM email_send_logs
                 ORDER BY created_at DESC, id DESC
                 LIMIT ' . (int)$limit;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
