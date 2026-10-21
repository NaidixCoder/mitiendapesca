<?php
namespace App\Repositories\Marketing;

use PDO;

final class EmailTemplateRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<int,array<string,mixed>> */
    public function list(int $limit = 200): array
    {
        $limit = max(1, min(500, (int)$limit));
        $sql = 'SELECT * FROM email_templates ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit;
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countForAdmin(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function listLite(int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(500, (int)$limit));
        $sql = 'SELECT id, name, subject_default, preheader_default, editor_mode, created_at FROM email_templates ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit . ' OFFSET ' . max(0, (int)$offset);
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        $st = $this->db->prepare('SELECT * FROM email_templates WHERE id = :id LIMIT 1');
        $st->execute([':id' => (int)$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $st = $this->db->prepare(
            'INSERT INTO email_templates (name, subject_default, preheader_default, editor_mode, body_html, created_at, updated_at)
             VALUES (:name, :subject_default, :preheader_default, :editor_mode, :body_html, NOW(), NOW())'
        );

        $st->execute([
            ':name' => (string)($data['name'] ?? ''),
            ':subject_default' => $data['subject_default'] ?? null,
            ':preheader_default' => $data['preheader_default'] ?? null,
            ':editor_mode' => (string)($data['editor_mode'] ?? 'visual'),
            ':body_html' => (string)($data['body_html'] ?? ''),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM email_templates WHERE id = :id');
        $st->execute([':id' => (int)$id]);
    }
}
