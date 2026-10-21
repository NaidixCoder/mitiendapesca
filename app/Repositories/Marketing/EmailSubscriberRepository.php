<?php
namespace App\Repositories\Marketing;

use PDO;

final class EmailSubscriberRepository
{
    public function __construct(private PDO $db) {}

    public function findByEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM email_subscribers WHERE email = :email LIMIT 1';
        $st = $this->db->prepare($sql);
        $st->execute([':email' => $email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByConfirmToken(string $token): ?array
    {
        $sql = 'SELECT * FROM email_subscribers WHERE confirm_token = :t LIMIT 1';
        $st = $this->db->prepare($sql);
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByUnsubscribeToken(string $token): ?array
    {
        $sql = 'SELECT * FROM email_subscribers WHERE unsubscribe_token = :t LIMIT 1';
        $st = $this->db->prepare($sql);
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Crea o actualiza un suscriptor.
     * Devuelve el registro final.
     */
    public function upsertSubscribe(string $email, string $status, ?string $confirmToken, string $unsubscribeToken, ?array $preferences = null, ?string $source = null): array
    {
        $now = date('Y-m-d H:i:s');
        // Evitar comparaciones string en SQL que pueden romper por mezcla de collations.
        // Usamos flags numéricos para CASE y mantenemos toda la lógica de estados consistente.
        $isSubscribed = ($status === 'subscribed') ? 1 : 0;
        $isUnsubscribed = ($status === 'unsubscribed') ? 1 : 0;

        $exists = $this->findByEmail($email);
        if ($exists) {
            $sql = 'UPDATE email_subscribers
                      SET status = :status,
                          confirm_token = :confirm_token,
                          unsubscribe_token = :unsubscribe_token,
                          confirmed_at = CASE WHEN :is_subscribed = 1 THEN COALESCE(confirmed_at, :now_confirm) ELSE confirmed_at END,
                          unsubscribed_at = CASE WHEN :is_unsubscribed = 1 THEN COALESCE(unsubscribed_at, :now_unsub) ELSE NULL END,
                          preferences_json = :prefs,
                          source = COALESCE(:source, source),
                          updated_at = :now_updated
                    WHERE id = :id';

            $st = $this->db->prepare($sql);
            $st->execute([
                ':status' => $status,
                ':is_subscribed' => $isSubscribed,
                ':is_unsubscribed' => $isUnsubscribed,
                ':confirm_token' => $confirmToken,
                ':unsubscribe_token' => $unsubscribeToken,
                ':prefs' => $preferences ? json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                ':source' => $source,
                ':now_confirm' => $now,
                ':now_unsub' => $now,
                ':now_updated' => $now,
                ':id' => (int)$exists['id'],
            ]);
            return $this->findByEmail($email) ?? $exists;
        }

        $sql = 'INSERT INTO email_subscribers (email, status, confirm_token, unsubscribe_token, confirmed_at, preferences_json, source, created_at, updated_at)
                VALUES (:email, :status, :confirm_token, :unsubscribe_token,
                        CASE WHEN :is_subscribed = 1 THEN :now_confirm ELSE NULL END,
                        :prefs, :source, :now_created, :now_updated)';
        $st = $this->db->prepare($sql);
        $st->execute([
            ':email' => $email,
            ':status' => $status,
            ':is_subscribed' => $isSubscribed,
            ':confirm_token' => $confirmToken,
            ':unsubscribe_token' => $unsubscribeToken,
            ':prefs' => $preferences ? json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ':source' => $source,
            ':now_confirm' => $now,
            ':now_created' => $now,
            ':now_updated' => $now,
        ]);

        return $this->findByEmail($email) ?? [
            'id' => (int)$this->db->lastInsertId(),
            'email' => $email,
            'status' => $status,
        ];
    }

    public function confirmByToken(string $token): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE email_subscribers
                   SET status = \'subscribed\',
                       confirmed_at = COALESCE(confirmed_at, :now_confirm),
                       confirm_token = NULL,
                       unsubscribed_at = NULL,
                       updated_at = :now_updated
                 WHERE confirm_token = :t';
        $st = $this->db->prepare($sql);
        $st->execute([':now_confirm' => $now, ':now_updated' => $now, ':t' => $token]);
        return $st->rowCount() > 0;
    }

    public function unsubscribeByToken(string $token): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE email_subscribers
                   SET status = \'unsubscribed\',
                       unsubscribed_at = COALESCE(unsubscribed_at, :now_unsub),
                       confirm_token = NULL,
                       updated_at = :now_updated
                 WHERE unsubscribe_token = :t';
        $st = $this->db->prepare($sql);
        $st->execute([':now_unsub' => $now, ':now_updated' => $now, ':t' => $token]);
        return $st->rowCount() > 0;
    }

    public function setStatusById(int $id, string $status): bool
    {
        if (!in_array($status, ['pending','subscribed','unsubscribed'], true)) return false;
        $now = date('Y-m-d H:i:s');
        $isSubscribed = ($status === 'subscribed') ? 1 : 0;
        $isUnsubscribed = ($status === 'unsubscribed') ? 1 : 0;

        $sql = 'UPDATE email_subscribers
                   SET status = :s,
                       confirmed_at = CASE WHEN :is_subscribed = 1 THEN COALESCE(confirmed_at, :now_confirm) ELSE confirmed_at END,
                       unsubscribed_at = CASE WHEN :is_unsubscribed = 1 THEN COALESCE(unsubscribed_at, :now_unsub) ELSE NULL END,
                       updated_at = :now_updated
                 WHERE id = :id';
        $st = $this->db->prepare($sql);
        $st->execute([
            ':s' => $status,
            ':is_subscribed' => $isSubscribed,
            ':is_unsubscribed' => $isUnsubscribed,
            ':now_confirm' => $now,
            ':now_unsub' => $now,
            ':now_updated' => $now,
            ':id' => $id
        ]);
        return $st->rowCount() > 0;
    }

    /** Verificación actual justo antes de enviar un correo de marketing ya encolado. */
    public function isSubscribedById(int $id): bool
    {
        if ($id <= 0) return false;
        $st = $this->db->prepare('SELECT status FROM email_subscribers WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        return $st->fetchColumn() === 'subscribed';
    }

    public function stats(): array
    {
        $sql = "SELECT status, COUNT(*) AS c FROM email_subscribers GROUP BY status";
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $out = ['pending' => 0, 'subscribed' => 0, 'unsubscribed' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $s = (string)($r['status'] ?? '');
            $c = (int)($r['c'] ?? 0);
            if (isset($out[$s])) $out[$s] = $c;
            $out['total'] += $c;
        }
        return $out;
    }

    /** Cantidad filtrada sin LIMIT ni lectura de tokens privados. */
    public function countForAdmin(string $q = '', string $status = ''): int
    {
        $conditions = [];
        $params = [];
        if ($q !== '') {
            $conditions[] = 'email LIKE :q';
            $params[':q'] = '%'.trim($q).'%';
        }
        if (in_array($status, ['pending','subscribed','unsubscribed'], true)) {
            $conditions[] = 'status = :s';
            $params[':s'] = $status;
        }
        $sql = 'SELECT COUNT(*) FROM email_subscribers';
        if ($conditions) $sql .= ' WHERE '.implode(' AND ', $conditions);
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /**
     * Listado paginado para Admin.
     * - $status: pending|subscribed|unsubscribed|"" (todos)
     */
    public function listForAdmin(string $q, string $status, int $limit = 100, int $offset = 0): array
    {
        $q = trim($q);
        $status = trim($status);
        $limit = max(1, min(200, $limit));

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = 'email LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }
        if (in_array($status, ['pending','subscribed','unsubscribed'], true)) {
            $where[] = 'status = :s';
            $params[':s'] = $status;
        }

        $sql = 'SELECT id, email, status, confirmed_at, unsubscribed_at, created_at
                  FROM email_subscribers';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $offset = max(0, $offset);
        $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
