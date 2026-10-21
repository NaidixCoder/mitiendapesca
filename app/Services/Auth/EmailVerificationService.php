<?php
namespace App\Services\Auth;

use PDO;
use App\Mail\Auth\EmailVerificationMail;

class EmailVerificationService
{
    public function __construct(private PDO $db) {}

    public function sendForUser(int $userId): void
    {
        $this->db->prepare(
            'DELETE FROM email_verifications WHERE user_id = ?'
        )->execute([$userId]);

        $token = bin2hex(random_bytes(32));

        $this->db->prepare(
            'INSERT INTO email_verifications (user_id, token, created_at, expires_at)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))'
        )->execute([$userId, $token]);

        $stmt = $this->db->prepare(
            'SELECT email FROM users WHERE id = ?'
        );
        $stmt->execute([$userId]);
        $email = $stmt->fetchColumn();

        EmailVerificationMail::send($email, $token);
    }

    public function verifyToken(string $token): bool
    {
        $stmt = $this->db->prepare(
            'SELECT user_id FROM email_verifications
             WHERE token = ? AND used_at IS NULL AND expires_at > NOW()'
        );
        $stmt->execute([$token]);
        $userId = $stmt->fetchColumn();

        if (!$userId) return false;

        $this->db->beginTransaction();

        $this->db->prepare(
            'UPDATE users
             SET email_verified = 1, email_verified_at = NOW()
             WHERE id = ?'
        )->execute([$userId]);

        $this->db->prepare(
            'UPDATE email_verifications
             SET used_at = NOW()
             WHERE token = ?'
        )->execute([$token]);

        $this->db->commit();
        return true;
    }
}

