<?php
namespace App\Repositories\Marketing;

use PDO;

final class MarketingCampaignRepository
{
    public function __construct(private PDO $db) {}

    private static ?bool $hasOrdersCampaign = null;
    private static ?bool $hasUsersCampaign = null;
    private static ?bool $hasAnalyticsEventsCampaign = null;

    private function ordersHasCampaignId(): bool
    {
        if (self::$hasOrdersCampaign !== null) return self::$hasOrdersCampaign;
        $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='marketing_campaign_id'");
        $st->execute();
        self::$hasOrdersCampaign = ((int)$st->fetchColumn() > 0);
        return self::$hasOrdersCampaign;
    }

    private function usersHasCampaignId(): bool
    {
        if (self::$hasUsersCampaign !== null) return self::$hasUsersCampaign;
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='marketing_campaign_id'");
            $st->execute();
            self::$hasUsersCampaign = ((int)$st->fetchColumn() > 0);
            return self::$hasUsersCampaign;
        } catch (\Throwable $e) {
            self::$hasUsersCampaign = false;
            return false;
        }
    }

    private function analyticsEventsHasCampaignId(): bool
    {
        if (self::$hasAnalyticsEventsCampaign !== null) return self::$hasAnalyticsEventsCampaign;
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='analytics_events' AND COLUMN_NAME='marketing_campaign_id'");
            $st->execute();
            self::$hasAnalyticsEventsCampaign = ((int)$st->fetchColumn() > 0);
            return self::$hasAnalyticsEventsCampaign;
        } catch (\Throwable $e) {
            self::$hasAnalyticsEventsCampaign = false;
            return false;
        }
    }

    public function findBySlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') return null;
        $st = $this->db->prepare('SELECT * FROM marketing_campaigns WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $id = (int)$id;
        if ($id <= 0) return null;
        $st = $this->db->prepare('SELECT * FROM marketing_campaigns WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM marketing_campaigns WHERE slug = ? AND id <> ?');
        $st->execute([trim($slug), (int)$excludeId]);
        return (int)$st->fetchColumn() > 0;
    }

    public function listForAdmin(string $q = '', int $days = 30, string $status = '', ?int $limit = null, int $offset = 0): array
    {
        $q = trim($q);
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        [$where, $params] = $this->adminFilters($q, $status);

        $ordersCols = '';
        if ($this->ordersHasCampaignId()) {
            // "Compras" = pedidos con pago confirmado / en proceso de preparación.
            $ordersCols = ",
                (SELECT COUNT(*)
                   FROM orders o
                  WHERE o.marketing_campaign_id = c.id
                    AND o.status IN ('paid','processing','packed','shipped','delivered')
                    AND o.created_at >= (NOW() - INTERVAL {$days} DAY)) AS purchases_30d,
                (SELECT COALESCE(SUM(o.total),0)
                   FROM orders o
                  WHERE o.marketing_campaign_id = c.id
                    AND o.status IN ('paid','processing','packed','shipped','delivered')
                    AND o.created_at >= (NOW() - INTERVAL {$days} DAY)) AS revenue_30d";
        } else {
            $ordersCols = ", 0 AS purchases_30d, 0 AS revenue_30d";
        }

        $usersCols = '';
        if ($this->usersHasCampaignId()) {
            $usersCols = ",
                (SELECT COUNT(*)
                   FROM users u
                  WHERE u.marketing_campaign_id = c.id
                    AND u.created_at >= (NOW() - INTERVAL {$days} DAY)) AS new_users_30d";
        } else {
            $usersCols = ", 0 AS new_users_30d";
        }

        $addToCartCols = '';
        if ($this->analyticsEventsHasCampaignId()) {
            $addToCartCols = ",
                (SELECT COUNT(*)
                   FROM analytics_events ae
                  WHERE ae.marketing_campaign_id = c.id
                    AND ae.event_key = 'add_to_cart'
                    AND ae.created_at >= (NOW() - INTERVAL {$days} DAY)) AS add_to_cart_30d";
        } else {
            $addToCartCols = ", 0 AS add_to_cart_30d";
        }

        $sql = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM marketing_campaign_hits h WHERE h.campaign_id = c.id AND h.created_at >= (NOW() - INTERVAL {$days} DAY)) AS hits_30d
                   {$ordersCols}
                   {$usersCols}
                   {$addToCartCols}
            FROM marketing_campaigns c
            {$where}
            ORDER BY c.created_at DESC, c.id DESC
        ";
        if ($limit !== null) {
            $limit = max(1, min(100, $limit));
            $offset = max(0, $offset);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Count without recalculating metrics; supports real pagination. */
    public function countForAdmin(string $q = '', string $status = ''): int
    {
        [$where, $params] = $this->adminFilters(trim($q), $status);
        $st = $this->db->prepare("SELECT COUNT(*) FROM marketing_campaigns c {$where}");
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** Stable, explicitly named bindings for PDO native prepares. */
    private function adminFilters(string $q, string $status): array
    {
        $conditions = [];
        $params = [];
        if ($q !== '') {
            $conditions[] = '(c.name LIKE :q_name OR c.slug LIKE :q_slug OR c.destination_path LIKE :q_destination)';
            $like = "%{$q}%";
            $params[':q_name'] = $like;
            $params[':q_slug'] = $like;
            $params[':q_destination'] = $like;
        }
        if ($status === 'active' || $status === 'inactive') {
            $conditions[] = 'c.is_active = :is_active';
            $params[':is_active'] = $status === 'active' ? 1 : 0;
        }
        return [$conditions ? 'WHERE '.implode(' AND ', $conditions) : '', $params];
    }

    public function upsert(array $data): int
    {
        $id = (int)($data['id'] ?? 0);

        if ($id > 0) {
            $st = $this->db->prepare(
                'UPDATE marketing_campaigns
                 SET name=?, slug=?, destination_path=?, utm_source=?, utm_medium=?, utm_campaign=?, utm_content=?, utm_term=?, is_active=?, notes=?, updated_at=NOW()
                 WHERE id=?'
            );
            $st->execute([
                (string)($data['name'] ?? ''),
                (string)($data['slug'] ?? ''),
                (string)($data['destination_path'] ?? ''),
                $data['utm_source'] ?? null,
                $data['utm_medium'] ?? null,
                $data['utm_campaign'] ?? null,
                $data['utm_content'] ?? null,
                $data['utm_term'] ?? null,
                (int)($data['is_active'] ?? 1),
                $data['notes'] ?? null,
                $id,
            ]);
            return $id;
        }

        $st = $this->db->prepare(
            'INSERT INTO marketing_campaigns (name, slug, destination_path, utm_source, utm_medium, utm_campaign, utm_content, utm_term, is_active, notes)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        $st->execute([
            (string)($data['name'] ?? ''),
            (string)($data['slug'] ?? ''),
            (string)($data['destination_path'] ?? ''),
            $data['utm_source'] ?? null,
            $data['utm_medium'] ?? null,
            $data['utm_campaign'] ?? null,
            $data['utm_content'] ?? null,
            $data['utm_term'] ?? null,
            (int)($data['is_active'] ?? 1),
            $data['notes'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function setActive(int $id, int $isActive): void
    {
        $st = $this->db->prepare('UPDATE marketing_campaigns SET is_active=?, updated_at=NOW() WHERE id=?');
        $st->execute([(int)$isActive, (int)$id]);
    }

    /**
     * Reporte agregado de una campaña en el rango.
     * NOTA: days está whitelisteado (7/30/90) para evitar inyección en INTERVAL.
     */
    public function getReport(int $campaignId, int $days = 30): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [
            'hits' => 0,
            'new_users' => 0,
            'add_to_cart' => 0,
            'purchases' => 0,
            'revenue' => 0,
            'ratio_new_users' => 0.0,
            'ratio_add_to_cart' => 0.0,
            'ratio_purchases' => 0.0,
        ];
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;

        $hits = 0;
        $newUsers = 0;
        $addToCart = 0;
        $purchases = 0;
        $revenue = 0;

        $st = $this->db->prepare("SELECT COUNT(*) FROM marketing_campaign_hits WHERE campaign_id = ? AND created_at >= (NOW() - INTERVAL {$days} DAY)");
        $st->execute([$campaignId]);
        $hits = (int)$st->fetchColumn();

        if ($this->usersHasCampaignId()) {
            $st = $this->db->prepare("SELECT COUNT(*) FROM users WHERE marketing_campaign_id = ? AND created_at >= (NOW() - INTERVAL {$days} DAY)");
            $st->execute([$campaignId]);
            $newUsers = (int)$st->fetchColumn();
        }

        if ($this->analyticsEventsHasCampaignId()) {
            $st = $this->db->prepare(
                "SELECT COUNT(*)
                   FROM analytics_events
                  WHERE marketing_campaign_id = ?
                    AND event_key = 'add_to_cart'
                    AND created_at >= (NOW() - INTERVAL {$days} DAY)"
            );
            $st->execute([$campaignId]);
            $addToCart = (int)$st->fetchColumn();
        }

        if ($this->ordersHasCampaignId()) {
            $st = $this->db->prepare(
                "SELECT COUNT(*) AS c, COALESCE(SUM(total),0) AS s
                   FROM orders
                  WHERE marketing_campaign_id = ?
                    AND status IN ('paid','processing','packed','shipped','delivered')
                    AND created_at >= (NOW() - INTERVAL {$days} DAY)"
            );
            $st->execute([$campaignId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            $purchases = (int)($row['c'] ?? 0);
            $revenue = (int)($row['s'] ?? 0);
        }

        $ratioNew = ($hits > 0) ? round(($newUsers / $hits) * 100, 1) : 0.0;
        $ratioATC = ($hits > 0) ? round(($addToCart / $hits) * 100, 1) : 0.0;
        $ratioBuy = ($hits > 0) ? round(($purchases / $hits) * 100, 1) : 0.0;

        return [
            'hits' => $hits,
            'new_users' => $newUsers,
            'add_to_cart' => $addToCart,
            'purchases' => $purchases,
            'revenue' => $revenue,
            'ratio_new_users' => $ratioNew,
            'ratio_add_to_cart' => $ratioATC,
            'ratio_purchases' => $ratioBuy,
        ];
    }

    /**
     * Series diaria (hits / nuevos usuarios / compras / ingresos) para mostrar tendencia.
     */
    public function getDailySeries(int $campaignId, int $days = 30): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [];
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;

        $out = [];

        // Hits por día
        $st = $this->db->prepare(
            "SELECT DATE(created_at) AS d, COUNT(*) AS hits
               FROM marketing_campaign_hits
              WHERE campaign_id = ?
                AND created_at >= (NOW() - INTERVAL {$days} DAY)
              GROUP BY DATE(created_at)
              ORDER BY d ASC"
        );
        $st->execute([$campaignId]);
        foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
            $d = (string)($r['d'] ?? '');
            if ($d === '') continue;
            if (!isset($out[$d])) $out[$d] = ['date' => $d, 'hits' => 0, 'new_users' => 0, 'purchases' => 0, 'revenue' => 0];
            $out[$d]['hits'] = (int)($r['hits'] ?? 0);
        }

        // Add-to-cart por día (desde analytics_events)
        if ($this->analyticsEventsHasCampaignId()) {
            $st = $this->db->prepare(
                "SELECT DATE(created_at) AS d, COUNT(*) AS add_to_cart
                   FROM analytics_events
                  WHERE marketing_campaign_id = ?
                    AND event_key = 'add_to_cart'
                    AND created_at >= (NOW() - INTERVAL {$days} DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY d ASC"
            );
            $st->execute([$campaignId]);
            foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
                $d = (string)($r['d'] ?? '');
                if ($d === '') continue;
                if (!isset($out[$d])) $out[$d] = ['date' => $d, 'hits' => 0, 'new_users' => 0, 'purchases' => 0, 'revenue' => 0];
                $out[$d]['add_to_cart'] = (int)($r['add_to_cart'] ?? 0);
            }
        }

        // Nuevos usuarios por día
        if ($this->usersHasCampaignId()) {
            $st = $this->db->prepare(
                "SELECT DATE(created_at) AS d, COUNT(*) AS new_users
                   FROM users
                  WHERE marketing_campaign_id = ?
                    AND created_at >= (NOW() - INTERVAL {$days} DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY d ASC"
            );
            $st->execute([$campaignId]);
            foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
                $d = (string)($r['d'] ?? '');
                if ($d === '') continue;
            if (!isset($out[$d])) $out[$d] = ['date' => $d, 'hits' => 0, 'new_users' => 0, 'purchases' => 0, 'revenue' => 0];
                $out[$d]['new_users'] = (int)($r['new_users'] ?? 0);
            }
        }

        // Compras/ingresos por día
        if ($this->ordersHasCampaignId()) {
            $st = $this->db->prepare(
                "SELECT DATE(created_at) AS d, COUNT(*) AS purchases, COALESCE(SUM(total),0) AS revenue
                   FROM orders
                  WHERE marketing_campaign_id = ?
                    AND status IN ('paid','processing','packed','shipped','delivered')
                    AND created_at >= (NOW() - INTERVAL {$days} DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY d ASC"
            );
            $st->execute([$campaignId]);
            foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
                $d = (string)($r['d'] ?? '');
                if ($d === '') continue;
                if (!isset($out[$d])) $out[$d] = ['date' => $d, 'hits' => 0, 'new_users' => 0, 'purchases' => 0, 'revenue' => 0];
                $out[$d]['purchases'] = (int)($r['purchases'] ?? 0);
                $out[$d]['revenue'] = (int)($r['revenue'] ?? 0);
            }
        }

        // Normalizar a lista ordenada
        ksort($out);
        return array_values($out);
    }

    public function getRecentHits(int $campaignId, int $limit = 20, int $days = 30): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [];
        $limit = max(1, min(100, (int)$limit));
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;

        $st = $this->db->prepare(
            "SELECT created_at, referrer, session_id
               FROM marketing_campaign_hits
              WHERE campaign_id = ?
                AND created_at >= (NOW() - INTERVAL {$days} DAY)
              ORDER BY created_at DESC
              LIMIT {$limit}"
        );
        $st->execute([$campaignId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getRecentOrders(int $campaignId, int $limit = 10, int $days = 30): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [];
        $limit = max(1, min(50, (int)$limit));
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        if (!$this->ordersHasCampaignId()) return [];

        $st = $this->db->prepare(
            "SELECT id, created_at, status, total, shipping_full_name
               FROM orders
              WHERE marketing_campaign_id = ?
                AND status IN ('paid','processing','packed','shipped','delivered')
                AND created_at >= (NOW() - INTERVAL {$days} DAY)
              ORDER BY created_at DESC
              LIMIT {$limit}"
        );
        $st->execute([$campaignId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Top páginas vistas dentro de la campaña (page_view en analytics_events).
     * Agrupa por path “limpio” (sin utm/gclid/etc) para evitar explosión.
     */
    public function topPages(int $campaignId, int $days = 30, int $limit = 10): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [];
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        $limit = max(1, min(50, (int)$limit));
        if (!$this->analyticsEventsHasCampaignId()) return [];

        // 1) agrupamos por path raw en SQL (rápido), 2) limpiamos y re-agregamos en PHP.
        $st = $this->db->prepare(
            "SELECT path, COUNT(*) AS c
               FROM analytics_events
              WHERE marketing_campaign_id = ?
                AND event_key = 'page_view'
                AND created_at >= (NOW() - INTERVAL {$days} DAY)
                AND path IS NOT NULL AND path <> ''
              GROUP BY path
              ORDER BY c DESC
              LIMIT 300"
        );
        $st->execute([$campaignId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $by = [];
        foreach ($rows as $r) {
            $raw = trim((string)($r['path'] ?? ''));
            if ($raw === '') continue;
            $clean = $this->stripTrackingQueryParams($raw);
            if ($clean === '') continue;
            $c = (int)($r['c'] ?? 0);
            if (!isset($by[$clean])) $by[$clean] = ['path' => $clean, 'views' => 0];
            $by[$clean]['views'] += $c;
        }
        $out = array_values($by);
        usort($out, fn($a,$b) => ((int)($b['views'] ?? 0)) <=> ((int)($a['views'] ?? 0)));
        return array_slice($out, 0, $limit);
    }

    /** Top productos vistos dentro de la campaña (product_view en analytics_events). */
    public function topProductsViewed(int $campaignId, int $days = 30, int $limit = 10): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [];
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        $limit = max(1, min(50, (int)$limit));
        if (!$this->analyticsEventsHasCampaignId()) return [];

        $st = $this->db->prepare(
            "SELECT ae.product_id,
                    ae.variant_id,
                    COUNT(*) AS views,
                    p.name AS product_name,
                    p.slug AS product_slug,
                    pv.title AS variant_title
               FROM analytics_events ae
               JOIN products p ON p.id = ae.product_id
               LEFT JOIN product_variants pv ON pv.id = ae.variant_id
              WHERE ae.marketing_campaign_id = ?
                AND ae.event_key = 'product_view'
                AND ae.created_at >= (NOW() - INTERVAL {$days} DAY)
              GROUP BY ae.product_id, ae.variant_id, p.name, p.slug, pv.title
              ORDER BY views DESC
              LIMIT {$limit}"
        );
        $st->execute([$campaignId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** Top productos agregados al carrito dentro de la campaña (add_to_cart en analytics_events). */
    public function topProductsAddedToCart(int $campaignId, int $days = 30, int $limit = 10): array
    {
        $campaignId = (int)$campaignId;
        if ($campaignId <= 0) return [];
        $days = (int)$days;
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        $limit = max(1, min(50, (int)$limit));
        if (!$this->analyticsEventsHasCampaignId()) return [];

        $st = $this->db->prepare(
            "SELECT ae.product_id,
                    ae.variant_id,
                    COUNT(*) AS events,
                    SUM(
                      CASE
                        WHEN ae.meta_json IS NULL THEN 1
                        WHEN JSON_EXTRACT(ae.meta_json, '$.qty') IS NULL THEN 1
                        ELSE CAST(JSON_UNQUOTE(JSON_EXTRACT(ae.meta_json, '$.qty')) AS UNSIGNED)
                      END
                    ) AS qty,
                    p.name AS product_name,
                    p.slug AS product_slug,
                    pv.title AS variant_title
               FROM analytics_events ae
               JOIN products p ON p.id = ae.product_id
               LEFT JOIN product_variants pv ON pv.id = ae.variant_id
              WHERE ae.marketing_campaign_id = ?
                AND ae.event_key = 'add_to_cart'
                AND ae.created_at >= (NOW() - INTERVAL {$days} DAY)
              GROUP BY ae.product_id, ae.variant_id, p.name, p.slug, pv.title
              ORDER BY qty DESC, events DESC
              LIMIT {$limit}"
        );
        $st->execute([$campaignId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function stripTrackingQueryParams(string $pathWithQuery): string
    {
        $pathWithQuery = trim($pathWithQuery);
        if ($pathWithQuery === '') return '';

        $parts = parse_url($pathWithQuery);
        $path = (string)($parts['path'] ?? '');
        $query = (string)($parts['query'] ?? '');
        if ($query === '') return $pathWithQuery;

        parse_str($query, $qs);
        if (!is_array($qs) || empty($qs)) return $pathWithQuery;

        $trackingKeys = [
            'utm_source','utm_medium','utm_campaign','utm_term','utm_content',
            'gclid','fbclid','yclid','msclkid','igshid','mc_cid','mc_eid'
        ];
        foreach ($trackingKeys as $k) {
            if (array_key_exists($k, $qs)) unset($qs[$k]);
        }

        if (empty($qs)) return $path !== '' ? $path : $pathWithQuery;
        $newQuery = http_build_query($qs);
        return $path . ($newQuery !== '' ? ('?'.$newQuery) : '');
    }
}
