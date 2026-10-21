<?php
namespace App\Controllers\Admin;

use PDO;

class DashboardDataController
{
    private function db(): PDO { return \db(); }
    private function json($data): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function trend(): void
    {
        $days   = max(7, (int)($_GET['days'] ?? 30));
        $metric = $_GET['metric'] ?? 'revenue'; // revenue|orders
        $db = $this->db();
        $since = (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d 00:00:00');

        if ($metric === 'orders') {
            $sql = "SELECT DATE(created_at) d, COUNT(*) v
                    FROM orders WHERE created_at >= ? GROUP BY d ORDER BY d";
        } else { // revenue
            $sql = "SELECT DATE(created_at) d, SUM(total) v
                    FROM orders
                    WHERE status IN ('paid','shipped') AND created_at >= ?
                    GROUP BY d ORDER BY d";
        }

        $st = $db->prepare($sql);
        $st->execute([$since]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->json(['ok'=>true,'series'=>$rows]);
    }

    public function kpis(): void
    {
        $db = $this->db();
        $active   = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
        $inactive = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=0")->fetchColumn();
        $users30  = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 30 DAY)")->fetchColumn();
        $low      = (int)$db->query("SELECT COUNT(*) FROM inventory WHERE stock <= low_stock_threshold")->fetchColumn();

        $this->json([
            'ok'=>true,
            'products_active'=>$active,
            'products_inactive'=>$inactive,
            'users_new_30d'=>$users30,
            'low_stock'=>$low,
        ]);
    }

    public function lowStock(): void
    {
        $db = $this->db();
        $sql = "SELECT p.sku, p.name, COALESCE(i.stock,0) stock, COALESCE(i.low_stock_threshold,0) thr
                FROM products p
                LEFT JOIN inventory i ON i.product_id = p.id
                WHERE COALESCE(i.stock,0) <= COALESCE(i.low_stock_threshold,0)
                ORDER BY p.name LIMIT 20";
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $this->json(['ok'=>true, 'items'=>$rows]);
    }
}
