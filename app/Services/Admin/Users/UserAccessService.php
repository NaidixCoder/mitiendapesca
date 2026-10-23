<?php
namespace App\Services\Admin\Users;

use PDO;
use RuntimeException;

final class UserAccessService
{
    public function __construct(private PDO $db) {}

    public function changeRole(int $actorId, int $targetId, string $role): array
    {
        if ($actorId <= 0 || $targetId <= 0 || $actorId === $targetId) {
            throw new RuntimeException('No se permite modificar la propia cuenta.');
        }
        $this->db->beginTransaction();
        try {
            // Bloquear el conjunto de administradores antes de contar evita degradaciones simultáneas.
            $locked = $this->db->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($actorId, array_map('intval', $locked), true)) {
                throw new RuntimeException('La cuenta actual no tiene permisos de administrador.');
            }
            $target = $this->db->prepare('SELECT role FROM users WHERE id = ? FOR UPDATE');
            $target->execute([$targetId]);
            $old = $target->fetchColumn();
            if ($old === false) {
                throw new RuntimeException('El usuario no existe.');
            }
            if ($old === 'admin' && $role !== 'admin' && count($locked) <= 1) {
                throw new RuntimeException('Debe permanecer al menos un administrador.');
            }
            if ($old === $role) {
                $this->db->commit();
                return ['changed' => false, 'old_role' => $old];
            }
            $update = $this->db->prepare('UPDATE users SET role = ?, session_version = COALESCE(session_version, 0) + 1 WHERE id = ?');
            $update->execute([$role, $targetId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('No se pudo modificar el usuario.');
            }
            $this->db->commit();
            return ['changed' => true, 'old_role' => $old];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function invalidateSessions(int $actorId, int $targetId): void
    {
        if ($actorId <= 0 || $targetId <= 0 || $actorId === $targetId) {
            throw new RuntimeException('No se permite cerrar la propia sesión desde Usuarios.');
        }
        $actor = $this->db->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
        $actor->execute([$actorId]);
        if (!$actor->fetchColumn()) throw new RuntimeException('No autorizado.');
        $st = $this->db->prepare('UPDATE users SET session_version = COALESCE(session_version, 0) + 1 WHERE id = ?');
        $st->execute([$targetId]);
        if ($st->rowCount() !== 1) throw new RuntimeException('Usuario inexistente o sesión no actualizada.');
    }
}
