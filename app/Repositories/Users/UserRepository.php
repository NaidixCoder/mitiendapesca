<?php
namespace App\Repositories\Users;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $db) {}

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT id, name, email, email_verified, created_at, last_login_at, avatar_url, role FROM users WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateName(int $userId, string $name): void
    {
        $st = $this->db->prepare('UPDATE users SET name = :name WHERE id = :id LIMIT 1');
        $st->execute([
            ':id' => $userId,
            ':name' => $name,
        ]);
    }
}

