<?php
namespace App\Services\Audit;

use App\Repositories\Admin\AdminAuditRepository;

/**
 * Auditoría (wrapper estático para mantener compatibilidad con llamadas existentes).
 *
 * Regla: jamás debe romper flujo/UX si falla.
 */
final class Audit
{
    /** @var array<string,bool> */
    private static array $dedupe = [];

    private static ?string $requestId = null;

    /**
     * Request ID estable por request para correlacionar eventos.
     * - Si viene X-Request-Id, lo respetamos.
     * - Si no, generamos uno (32 hex).
     */
    private static function getRequestId(): string
    {
        if (self::$requestId) return self::$requestId;

        $hdr = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        $hdr = is_string($hdr) ? trim($hdr) : '';
        if ($hdr !== '') {
            // Normalizamos: dejamos sólo [a-zA-Z0-9_-] y recortamos.
            $norm = preg_replace('/[^a-zA-Z0-9_\-]/', '', $hdr) ?? '';
            $norm = substr($norm, 0, 64);
            if ($norm !== '') {
                self::$requestId = $norm;
                return self::$requestId;
            }
        }

        try {
            self::$requestId = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            self::$requestId = sha1((string)microtime(true) . '|' . (string)mt_rand());
            self::$requestId = substr(self::$requestId, 0, 32);
        }
        return self::$requestId;
    }

    /**
     * Normaliza arrays para generar fingerprints estables (ordena claves recursivamente).
     *
     * @param array $arr
     * @return array
     */
    private static function normalizeArray(array $arr): array
    {
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = self::normalizeArray($v);
            }
        }
        if (!empty($arr)) {
            // Solo ordenar arrays asociativos (para no romper listas)
            $isAssoc = array_keys($arr) !== range(0, count($arr) - 1);
            if ($isAssoc) {
                ksort($arr);
            }
        }
        return $arr;
    }

    /**
     * Registra un evento de auditoría (silencioso si falla).
     *
     * @param string $action      Acción (ej: product_updated, order_status_changed)
     * @param ?string $entityType Tipo de entidad (product, order, user, ...)
     * @param ?int $entityId      ID de entidad
     * @param array $meta         Metadata JSON (before/after, motivo, etc.)
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, array $meta = []): void
    {
        try {
            if (!function_exists('db')) return;
            $db = db();
            if (!$db) return;

            // Actor (admin/user) actual
            $uid = (int)($_SESSION['uid'] ?? 0);

            // Dedupe por request (evita duplicados cuando el mismo handler llama 2 veces)
            $normMeta = self::normalizeArray($meta);
            $fingerprint = sha1(
                (string)$uid.'|'.$action.'|'.(string)($entityType ?? '').'|'.(string)($entityId ?? 0)
                .'|'.json_encode($normMeta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
            );
            if (isset(self::$dedupe[$fingerprint])) {
                return;
            }
            self::$dedupe[$fingerprint] = true;

            (new AdminAuditRepository($db))->log(
                $uid,
                $action,
                $entityType,
                $entityId,
                $meta,
                self::getRequestId()
            );
        } catch (\Throwable $e) {
            // Nunca romper UX por auditoría
        }
    }
}


