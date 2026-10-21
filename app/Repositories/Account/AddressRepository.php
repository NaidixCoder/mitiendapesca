<?php
namespace App\Repositories\Account;

use PDO;

class AddressRepository
{
    public function __construct(private PDO $db) {}

    public function countByUser(int $userId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM user_addresses WHERE user_id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    public function listByUser(int $userId): array
    {
        $st = $this->db->prepare(
            'SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, updated_at DESC, id DESC'
        );
        $st->execute([$userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDefaultByUser(int $userId): ?array
    {
        $st = $this->db->prepare(
            'SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1 ORDER BY id DESC LIMIT 1'
        );
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $st = $this->db->prepare('SELECT * FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1');
        $st->execute([$id, $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(int $userId, array $data): int
    {
        $sql = 'INSERT INTO user_addresses
          (user_id, label, full_name, phone, street, number, floor, apartment, city, state, zip, notes, is_default, created_at, updated_at)
          VALUES
          (:user_id, :label, :full_name, :phone, :street, :number, :floor, :apartment, :city, :state, :zip, :notes, :is_default, NOW(), NOW())';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':user_id'   => $userId,
            ':label'     => $data['label'] ?? null,
            ':full_name' => $data['full_name'] ?? null,
            ':phone'     => $data['phone'] ?? null,
            ':street'    => $data['street'] ?? null,
            ':number'    => $data['number'] ?? null,
            ':floor'     => $data['floor'] ?? null,
            ':apartment' => $data['apartment'] ?? null,
            ':city'      => $data['city'] ?? null,
            ':state'     => $data['state'] ?? null,
            ':zip'       => $data['zip'] ?? null,
            ':notes'     => $data['notes'] ?? null,
            ':is_default'=> (int)($data['is_default'] ?? 0),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $sql = 'UPDATE user_addresses SET
            label = :label,
            full_name = :full_name,
            phone = :phone,
            street = :street,
            number = :number,
            floor = :floor,
            apartment = :apartment,
            city = :city,
            state = :state,
            zip = :zip,
            notes = :notes,
            updated_at = NOW()
          WHERE id = :id AND user_id = :user_id
          LIMIT 1';

        $st = $this->db->prepare($sql);
        return $st->execute([
            ':id'        => $id,
            ':user_id'   => $userId,
            ':label'     => $data['label'] ?? null,
            ':full_name' => $data['full_name'] ?? null,
            ':phone'     => $data['phone'] ?? null,
            ':street'    => $data['street'] ?? null,
            ':number'    => $data['number'] ?? null,
            ':floor'     => $data['floor'] ?? null,
            ':apartment' => $data['apartment'] ?? null,
            ':city'      => $data['city'] ?? null,
            ':state'     => $data['state'] ?? null,
            ':zip'       => $data['zip'] ?? null,
            ':notes'     => $data['notes'] ?? null,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $st = $this->db->prepare('DELETE FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1');
        $st->execute([$id, $userId]);
        return $st->rowCount() > 0;
    }

    public function clearDefault(int $userId): void
    {
        $st = $this->db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = ?');
        $st->execute([$userId]);
    }

    public function setDefault(int $id, int $userId): bool
    {
        $st = $this->db->prepare('UPDATE user_addresses SET is_default = 1, updated_at = NOW() WHERE id = ? AND user_id = ? LIMIT 1');
        $st->execute([$id, $userId]);
        return $st->rowCount() > 0;
    }
}
