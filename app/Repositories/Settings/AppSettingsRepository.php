<?php
namespace App\Repositories\Settings;

use PDO;
use PDOException;

/**
 * Settings globales (key-value) para el admin.
 *
 * IMPORTANTE: debe ser resistente a que la tabla no exista, pero NO debe fallar "silencioso"
 * cuando el admin intenta GUARDAR (caso típico: faltó correr migraciones).
 */
final class AppSettingsRepository
{
    private ?string $lastError = null;
    private ?bool $availableCache = null;

    public function __construct(private PDO $db) {}

    public function isAvailable(): bool
    {
        if ($this->availableCache !== null) return $this->availableCache;

        try {
            // Chequeo rápido y compatible (sin information_schema)
            $st = $this->db->query("SHOW TABLES LIKE 'app_settings'");
            $row = $st ? $st->fetchColumn() : false;
            $this->availableCache = ($row !== false && $row !== null);
        } catch (PDOException $e) {
            $this->availableCache = false;
        }
        return $this->availableCache;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $key = trim($key);
        if ($key === '') return $default;

        try {
            $st = $this->db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
            $st->execute([$key]);
            $val = $st->fetchColumn();
            if ($val === false || $val === null) return $default;
            $val = (string)$val;
            return $val !== '' ? $val : $default;
        } catch (PDOException $e) {
            // Tabla no existe o no está migrada todavía.
            $this->lastError = $e->getMessage();
            return $default;
        }
    }

    public function set(string $key, string $value): bool
    {
        $this->lastError = null;

        $key = trim($key);
        if ($key === '') return false;

        $value = (string)$value;

        try {
            $st = $this->db->prepare(
                'INSERT INTO app_settings (setting_key, setting_value, updated_at) VALUES (?,?, NOW())
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
            );
            $ok = $st->execute([$key, $value]);
            return (bool)$ok;
        } catch (PDOException $e) {
            // Tabla no existe / no migrada / etc.
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
