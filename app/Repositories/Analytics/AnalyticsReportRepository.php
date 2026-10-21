<?php
namespace App\Repositories\Analytics;

use PDO;

final class AnalyticsReportRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Landing events: 1er page_view por sesión (últimos N días).
     *
     * Usamos MIN(id) como "primer evento" para evitar empates de timestamp.
     *
     * @return array<int,array<string,mixed>>
     */
    public function landingEvents(int $days, int $maxSessions = 5000): array
    {
        $days = max(1, min(365, (int)$days));
        $maxSessions = max(100, min(50000, (int)$maxSessions));

        // IMPORTANTE (MySQL): el valor de INTERVAL no se puede parametrizar con placeholders.
        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        // 1 evento de landing por session_id (si hay session). Para fuentes/UTM.
        $sql = "
            SELECT
              ae.session_id,
              ae.path,
              ae.referrer,
              ae.meta_json,
              ae.created_at
            FROM analytics_events ae
            INNER JOIN (
              SELECT session_id, MIN(id) AS first_id
              FROM analytics_events
              WHERE event_key = 'page_view'
                AND created_at >= {$cutoff}
                AND session_id IS NOT NULL AND session_id <> ''
              GROUP BY session_id
            ) f ON f.first_id = ae.id
            ORDER BY ae.id DESC
            LIMIT {$maxSessions}
        ";

        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Eventos para atribución de fuentes (por sesión).
     *
     * Problema real: si el usuario ya tenía la sesión abierta y luego entra con UTMs,
     * el "primer page_view" de la sesión no trae UTMs. Para que el Dashboard refleje
     * campañas aun si entran a mitad de sesión, elegimos por sesión:
     *  - el PRIMER page_view que tenga utm_* en meta_json (si existe)
     *  - si no existe, el PRIMER page_view de la sesión
     *
     * Nota: evitamos funciones JSON para compatibilidad, usamos LIKE sobre meta_json.
     *
     * @return array<int,array<string,mixed>>
     */
    public function sourceAttributionEvents(int $days, int $maxSessions = 5000): array
    {
        $days = max(1, min(365, (int)$days));
        $maxSessions = max(100, min(50000, (int)$maxSessions));

        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        $sql = "
            SELECT
              ae.session_id,
              ae.path,
              ae.referrer,
              ae.meta_json,
              ae.created_at
            FROM analytics_events ae
            INNER JOIN (
              SELECT
                session_id,
                COALESCE(
                  MIN(CASE
                        WHEN meta_json IS NOT NULL AND meta_json <> '' AND meta_json LIKE '%\"utm_source\"%'
                        THEN id
                      END),
                  MIN(id)
                ) AS pick_id
              FROM analytics_events
              WHERE event_key = 'page_view'
                AND created_at >= {$cutoff}
                AND session_id IS NOT NULL AND session_id <> ''
              GROUP BY session_id
            ) f ON f.pick_id = ae.id
            ORDER BY ae.id DESC
            LIMIT {$maxSessions}
        ";

        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function topPages(int $days, int $limit = 10): array
    {
        $days = max(1, min(365, (int)$days));
        $limit = max(1, min(50, (int)$limit));

        // IMPORTANTE (MySQL): el valor de INTERVAL no se puede parametrizar con placeholders.
        // Validamos $days arriba y lo inyectamos como int seguro.
        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        // NOTA: mantenemos esta query lo más simple posible para evitar edge-cases de SQL mode.
        // El enriquecimiento (ej. mostrar "Producto: X" para /producto?slug=...) se hace en el Controller.
        $sql = "
            SELECT
              ae.path,
              COUNT(*) AS views,
              MAX(ae.created_at) AS last_seen
            FROM analytics_events ae
            WHERE ae.event_key = 'page_view'
              AND ae.created_at >= {$cutoff}
              AND ae.path IS NOT NULL AND ae.path <> ''
            GROUP BY ae.path
            ORDER BY views DESC, last_seen DESC
            LIMIT {$limit}
        ";

        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function topProducts(int $days, int $limit = 10): array
    {
        $days = max(1, min(365, (int)$days));
        $limit = max(1, min(50, (int)$limit));

        // IMPORTANTE (MySQL): el valor de INTERVAL no se puede parametrizar con placeholders.
        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        $sql = "
            SELECT
              ae.product_id,
              -- Nota: el schema actual usa products.name (no products.title). Evitamos referenciar columnas inexistentes.
              COALESCE(p.name, CONCAT('Producto #', ae.product_id)) AS product_name,
              p.slug AS product_slug,
              COUNT(*) AS views,
              MAX(ae.created_at) AS last_seen
            FROM analytics_events ae
            LEFT JOIN products p ON p.id = ae.product_id
            WHERE ae.event_key = 'product_view'
              AND ae.created_at >= {$cutoff}
              AND ae.product_id IS NOT NULL
            GROUP BY ae.product_id, product_name, product_slug
            ORDER BY views DESC, last_seen DESC
            LIMIT {$limit}
        ";

        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Funnel mini (checkout_view -> purchase).
     *
     * @return array{checkout_views:int,purchases:int,conversion:float}
     */
    public function funnelMini(int $days): array
    {
        $days = max(1, min(365, (int)$days));

        // IMPORTANTE (MySQL): el valor de INTERVAL no se puede parametrizar con placeholders.
        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        $st1 = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM analytics_events WHERE event_key='checkout_view' AND created_at >= {$cutoff}"
        );
        $st1->execute();
        $checkoutViews = (int)($st1->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $st2 = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM analytics_events WHERE event_key='purchase' AND created_at >= {$cutoff}"
        );
        $st2->execute();
        $purchases = (int)($st2->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

        $conv = 0.0;
        if ($checkoutViews > 0) {
            $conv = ($purchases / $checkoutViews) * 100.0;
        }

        return [
            'checkout_views' => $checkoutViews,
            'purchases' => $purchases,
            'conversion' => $conv,
        ];
    }

    /**
     * Top productos por "vista -> carrito" (últimos N días).
     *
     * Base: product_view. Luego agregamos add_to_cart por product_id.
     *
     * @return array<int,array<string,mixed>>
     */
    public function topProductsViewToCart(int $days, int $limit = 10): array
    {
        $days = max(1, min(365, (int)$days));
        $limit = max(1, min(50, (int)$limit));

        // IMPORTANTE (MySQL): el valor de INTERVAL no se puede parametrizar con placeholders.
        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        // Evitamos funciones JSON para compatibilidad. Contamos eventos (no unidades).
        // Versión variante-aware: agrupa adds por (product_id, variant_id) para mostrar qué variante se agrega.
        // Nota: las vistas (product_view) son a nivel producto.
        $sql = "
            SELECT
              v.product_id,
              COALESCE(p.name, CONCAT('Producto #', v.product_id)) AS product_name,
              p.slug AS product_slug,
              v.views,
              c.variant_id,
              COALESCE(NULLIF(pv.title, ''), NULLIF(pv.sku, ''), CASE WHEN c.variant_id IS NULL THEN NULL ELSE CONCAT('Variante #', c.variant_id) END) AS variant_title,
              COALESCE(c.adds, 0) AS adds,
              CASE WHEN v.views > 0 THEN (COALESCE(c.adds, 0) / v.views) * 100.0 ELSE 0 END AS conv
            FROM (
              SELECT product_id, COUNT(*) AS views
              FROM analytics_events
              WHERE event_key = 'product_view'
                AND created_at >= {$cutoff}
                AND product_id IS NOT NULL
              GROUP BY product_id
            ) v
            LEFT JOIN (
              SELECT product_id, variant_id, COUNT(*) AS adds
              FROM analytics_events
              WHERE event_key = 'add_to_cart'
                AND created_at >= {$cutoff}
                AND product_id IS NOT NULL
              GROUP BY product_id, variant_id
            ) c ON c.product_id = v.product_id
            LEFT JOIN product_variants pv ON pv.id = c.variant_id
            LEFT JOIN products p ON p.id = v.product_id
            ORDER BY conv DESC, adds DESC, views DESC
            LIMIT {$limit}
        ";

        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Top productos agregados al carrito (últimos N días).
     *
     * @return array<int,array<string,mixed>>
     */
    public function topAddToCart(int $days, int $limit = 10): array
    {
        $days = max(1, min(365, (int)$days));
        $limit = max(1, min(50, (int)$limit));

        $cutoff = "(NOW() - INTERVAL {$days} DAY)";

        // Variante-aware: agrupa por (product_id, variant_id) para que en productos con variantes
        // se vea qué variante se está agregando al carrito.
        $sql = "
            SELECT
              ae.product_id,
              ae.variant_id,
              COALESCE(p.name, CONCAT('Producto #', ae.product_id)) AS product_name,
              p.slug AS product_slug,
              COALESCE(NULLIF(pv.title, ''), NULLIF(pv.sku, ''), CASE WHEN ae.variant_id IS NULL THEN NULL ELSE CONCAT('Variante #', ae.variant_id) END) AS variant_title,
              COUNT(*) AS adds,
              MAX(ae.created_at) AS last_seen
            FROM analytics_events ae
            LEFT JOIN products p ON p.id = ae.product_id
            LEFT JOIN product_variants pv ON pv.id = ae.variant_id
            WHERE ae.event_key = 'add_to_cart'
              AND ae.created_at >= {$cutoff}
              AND ae.product_id IS NOT NULL
            GROUP BY ae.product_id, ae.variant_id, product_name, product_slug, variant_title
            ORDER BY adds DESC, last_seen DESC
            LIMIT {$limit}
        ";

        $st = $this->db->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
