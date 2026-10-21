<?php

declare(strict_types=1);

namespace App\Repositories\Users;

use PDO;

final class UserFiscalProfileRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = \DB::pdo();
    }

    public function tableExists(): bool
    {
        try {
            $st = $this->db->query("SHOW TABLES LIKE 'user_fiscal_profiles'");
            return (bool)($st && $st->fetchColumn());
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** @return array<string,mixed>|null */
    public function findByUserId(int $userId): ?array
    {
        if ($userId <= 0 || !$this->tableExists()) return null;
        $st = $this->db->prepare("SELECT * FROM user_fiscal_profiles WHERE user_id = ? LIMIT 1");
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

    /** @param array<string,mixed> $data */
    public function upsert(int $userId, array $data): void
    {
        if ($userId <= 0 || !$this->tableExists()) return;

        $existing = $this->findByUserId($userId);
        $fields = [
            'tax_condition' => (string)($data['tax_condition'] ?? 'consumidor_final'),
            'cuit' => (string)($data['cuit'] ?? ''),
            'legal_name' => (string)($data['legal_name'] ?? ''),
            'validation_status' => (string)($data['validation_status'] ?? 'unverified'),
            'validation_error' => (string)($data['validation_error'] ?? ''),
            'validated_at' => $data['validated_at'] ?? null,
        ];

        if ($existing) {
            $st = $this->db->prepare("UPDATE user_fiscal_profiles
                SET tax_condition=:tax_condition, cuit=:cuit, legal_name=:legal_name,
                    validation_status=:validation_status, validation_error=:validation_error,
                    validated_at=:validated_at, updated_at=NOW()
                WHERE user_id=:user_id");
            $st->execute([
                ':user_id' => $userId,
                ':tax_condition' => $fields['tax_condition'],
                ':cuit' => $fields['cuit'],
                ':legal_name' => $fields['legal_name'],
                ':validation_status' => $fields['validation_status'],
                ':validation_error' => $fields['validation_error'],
                ':validated_at' => $fields['validated_at'],
            ]);
            return;
        }

        $st = $this->db->prepare("INSERT INTO user_fiscal_profiles
            (user_id, tax_condition, cuit, legal_name, validation_status, validation_error, validated_at, created_at, updated_at)
            VALUES
            (:user_id,:tax_condition,:cuit,:legal_name,:validation_status,:validation_error,:validated_at,NOW(),NOW())");
        $st->execute([
            ':user_id' => $userId,
            ':tax_condition' => $fields['tax_condition'],
            ':cuit' => $fields['cuit'],
            ':legal_name' => $fields['legal_name'],
            ':validation_status' => $fields['validation_status'],
            ':validation_error' => $fields['validation_error'],
            ':validated_at' => $fields['validated_at'],
        ]);
    }
}

