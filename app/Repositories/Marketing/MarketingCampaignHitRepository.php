<?php
namespace App\Repositories\Marketing;

use PDO;

final class MarketingCampaignHitRepository
{
    public function __construct(private PDO $db) {}

    public function insert(int $campaignId, ?string $sessionId, ?string $referrer, ?string $ipHash, ?string $uaHash): void
    {
        // Dedupe simple: evita inflar hits por refresh/bots.
        // Por defecto, no contamos hits repetidos dentro de una ventana corta.
        $dedupeSeconds = $this->dedupeWindowSeconds();
        if ($dedupeSeconds > 0 && $this->existsRecent($campaignId, $sessionId, $ipHash, $uaHash, $dedupeSeconds)) {
            return;
        }

        $st = $this->db->prepare(
            'INSERT INTO marketing_campaign_hits (campaign_id, session_id, referrer, ip_hash, ua_hash)
             VALUES (?,?,?,?,?)'
        );
        $st->execute([
            (int)$campaignId,
            $sessionId !== '' ? $sessionId : null,
            $referrer !== '' ? $this->trim255($referrer) : null,
            $ipHash !== '' ? $ipHash : null,
            $uaHash !== '' ? $uaHash : null,
        ]);
    }

    private function trim255(string $s): string
    {
        $s = trim($s);
        if (strlen($s) > 255) $s = substr($s, 0, 255);
        return $s;
    }

    private function dedupeWindowSeconds(): int
    {
        // Override ENV: MARKETING_HIT_DEDUPE_SECONDS=60
        $value = $_ENV['MARKETING_HIT_DEDUPE_SECONDS'] ?? getenv('MARKETING_HIT_DEDUPE_SECONDS');
        // getenv() devuelve false cuando falta la variable: ?? no reemplaza false.
        // También consultamos el cargador .env de la aplicación.
        if ($value === false || $value === null || trim((string)$value) === '') {
            $value = function_exists('env') ? env('MARKETING_HIT_DEDUPE_SECONDS', '60') : '60';
        }
        $n = (int)trim((string)$value);
        if ($n < 0) $n = 0;
        if ($n > 600) $n = 600; // hard cap
        return $n;
    }

    private function existsRecent(int $campaignId, ?string $sessionId, ?string $ipHash, ?string $uaHash, int $seconds): bool
    {
        $seconds = (int)$seconds;
        if ($seconds <= 0) return false;

        // Preferimos session_id; si no hay, caemos en ip_hash+ua_hash.
        if ($sessionId !== null && trim($sessionId) !== '') {
            $sql = 'SELECT 1
                    FROM marketing_campaign_hits
                    WHERE campaign_id = ?
                      AND session_id = ?
                      AND created_at >= DATE_SUB(NOW(), INTERVAL '.$seconds.' SECOND)
                    LIMIT 1';
            $st = $this->db->prepare($sql);
            $st->execute([(int)$campaignId, (string)$sessionId]);
            return (bool)$st->fetchColumn();
        }

        $ipHash = $ipHash !== null ? trim($ipHash) : '';
        $uaHash = $uaHash !== null ? trim($uaHash) : '';
        if ($ipHash !== '' && $uaHash !== '') {
            $sql = 'SELECT 1
                    FROM marketing_campaign_hits
                    WHERE campaign_id = ?
                      AND ip_hash = ?
                      AND ua_hash = ?
                      AND created_at >= DATE_SUB(NOW(), INTERVAL '.$seconds.' SECOND)
                    LIMIT 1';
            $st = $this->db->prepare($sql);
            $st->execute([(int)$campaignId, $ipHash, $uaHash]);
            return (bool)$st->fetchColumn();
        }

        return false;
    }
}
