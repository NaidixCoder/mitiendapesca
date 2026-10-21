<?php
namespace App\Repositories\Marketing;

use PDO;

final class EmailClickTokenRepository
{
    public function __construct(private PDO $db) {}

    public function createToken(array $row): string
    {
        $token = (string)($row['token'] ?? '');
        if ($token === '') {
            // 32 bytes -> 64 hex
            $token = bin2hex(random_bytes(32));
        }

        $st = $this->db->prepare(
            'INSERT INTO email_click_tokens
              (token, campaign_id, queue_id, subscriber_id, user_id, destination_url, created_at)
             VALUES
              (:t, :cid, :qid, :sid, :uid, :url, NOW())'
        );
        $st->execute([
            ':t' => $token,
            ':cid' => $row['campaign_id'] ?? null,
            ':qid' => $row['queue_id'] ?? null,
            ':sid' => $row['subscriber_id'] ?? null,
            ':uid' => $row['user_id'] ?? null,
            ':url' => (string)($row['destination_url'] ?? '/'),
        ]);

        return $token;
    }

    /** @return array<string,mixed>|null */
    public function find(string $token): ?array
    {
        $st = $this->db->prepare('SELECT * FROM email_click_tokens WHERE token=:t LIMIT 1');
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markClicked(string $token): void
    {
        $st = $this->db->prepare('UPDATE email_click_tokens SET clicked_at=NOW() WHERE token=:t AND clicked_at IS NULL');
        $st->execute([':t' => $token]);
    }

    public function countClicksForCampaign(int $campaignId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM email_click_tokens WHERE campaign_id=:id AND clicked_at IS NOT NULL');
        $st->execute([':id' => $campaignId]);
        return (int)$st->fetchColumn();
    }
}
