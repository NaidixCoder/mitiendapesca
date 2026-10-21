<?php
namespace App\Repositories\Account;

use PDO;

class ProfileRepository
{
    public function __construct(private PDO $db) {}

    public function findByUserId(int $userId): ?array
    {
        $st = $this->db->prepare(
            'SELECT user_id, phone, birth_date, marketing_email_opt_in, marketing_whatsapp_opt_in, created_at, updated_at
             FROM user_profiles
             WHERE user_id = ?
             LIMIT 1'
        );
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Insert or update profile (sin dependencias extra).
     * Requiere PK/UNIQUE en user_id.
     */
    public function upsert(int $userId, array $data): void
    {
        $sql = "INSERT INTO user_profiles (user_id, phone, birth_date, marketing_email_opt_in, marketing_whatsapp_opt_in, created_at, updated_at)
                VALUES (:user_id, :phone, :birth_date, :email_opt_in, :wa_opt_in, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  phone = VALUES(phone),
                  birth_date = VALUES(birth_date),
                  marketing_email_opt_in = VALUES(marketing_email_opt_in),
                  marketing_whatsapp_opt_in = VALUES(marketing_whatsapp_opt_in),
                  updated_at = NOW()";

        $st = $this->db->prepare($sql);
        $st->execute([
            ':user_id' => $userId,
            ':phone' => $data['phone'] !== '' ? $data['phone'] : null,
            ':birth_date' => $data['birth_date'] !== '' ? $data['birth_date'] : null,
            ':email_opt_in' => (int)($data['marketing_email_opt_in'] ?? 0),
            ':wa_opt_in' => (int)($data['marketing_whatsapp_opt_in'] ?? 0),
        ]);
    }
}
