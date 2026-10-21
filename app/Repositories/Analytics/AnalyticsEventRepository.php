<?php
namespace App\Repositories\Analytics;

use PDO;

/**
 * Persistencia de eventos de analíticas.
 *
 * Nota de seguridad: evitamos PII en claro (ip/ua/cart_token -> hashes best-effort).
 */
final class AnalyticsEventRepository
{
    public function __construct(private PDO $db) {}

    private static ?bool $hasCampaignColumn = null;

    private function hasCampaignColumn(): bool
    {
        if (self::$hasCampaignColumn !== null) return self::$hasCampaignColumn;
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='analytics_events' AND COLUMN_NAME='marketing_campaign_id'");
            $st->execute();
            self::$hasCampaignColumn = ((int)$st->fetchColumn() > 0);
            return self::$hasCampaignColumn;
        } catch (\Throwable $e) {
            self::$hasCampaignColumn = false;
            return false;
        }
    }

    /**
     * Inserta un evento.
     *
     * @param array<string,mixed> $data
     */
    public function insert(array $data, bool $ignoreDuplicates = false): int
    {
        $withCampaign = $this->hasCampaignColumn();

        $sql = ($ignoreDuplicates ? 'INSERT IGNORE' : 'INSERT') . " INTO analytics_events (
            event_key,
            path,
            referrer," . ($withCampaign ? "
            marketing_campaign_id," : "") . "
            user_id,
            session_id,
            cart_token_hash,
            ip_hash,
            ua_hash,
            product_id,
            variant_id,
            order_id,
            meta_json,
            created_at
        ) VALUES (
            :event_key,
            :path,
            :referrer," . ($withCampaign ? "
            :marketing_campaign_id," : "") . "
            :user_id,
            :session_id,
            :cart_token_hash,
            :ip_hash,
            :ua_hash,
            :product_id,
            :variant_id,
            :order_id,
            :meta_json,
            NOW()
        )";

        $params = [
            ':event_key' => (string)($data['event_key'] ?? ''),
            ':path' => $this->nullIfEmpty($data['path'] ?? null),
            ':referrer' => $this->nullIfEmpty($data['referrer'] ?? null),
            ':user_id' => $this->nullIfZeroInt($data['user_id'] ?? null),
            ':session_id' => $this->nullIfEmpty($data['session_id'] ?? null),
            ':cart_token_hash' => $this->nullIfEmpty($data['cart_token_hash'] ?? null),
            ':ip_hash' => $this->nullIfEmpty($data['ip_hash'] ?? null),
            ':ua_hash' => $this->nullIfEmpty($data['ua_hash'] ?? null),
            ':product_id' => $this->nullIfZeroInt($data['product_id'] ?? null),
            ':variant_id' => $this->nullIfZeroInt($data['variant_id'] ?? null),
            ':order_id' => $this->nullIfZeroInt($data['order_id'] ?? null),
            ':meta_json' => $this->nullIfEmpty($data['meta_json'] ?? null),
        ];

        if ($withCampaign) {
            $params[':marketing_campaign_id'] = $this->nullIfZeroInt($data['marketing_campaign_id'] ?? null);
        }

        $st = $this->db->prepare($sql);
        $st->execute($params);

        return (int)$this->db->lastInsertId();
    }

    private function nullIfEmpty(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    private function nullIfZeroInt(mixed $v): ?int
    {
        if ($v === null) return null;
        $i = (int)$v;
        return $i <= 0 ? null : $i;
    }
}
