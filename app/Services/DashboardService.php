<?php
namespace App\Services;

class DashboardService {
    public function collect(): array {
        $pdo = null; $dbOk = true;
        try { $pdo = db(); $pdo->query('SELECT 1'); } catch (\Throwable $e) { $dbOk = false; }

        $env = env('APP_ENV','production');
        $storageDir = base_path('storage');
        $storageWritable = (is_dir($storageDir) && is_writable($storageDir)) ? 'OK' : 'Revisar';

        $kpis = [
            ['label'=>'Productos activos','value'=>'—','sub'=>'SKU activos'],
            ['label'=>'Bajo stock','value'=>'—','sub'=>'SKUs'],
            ['label'=>'Usuarios nuevos 30d','value'=>'—','sub'=>'Usuarios'],
            ['label'=>'Inactivos','value'=>'—','sub'=>'Productos'],
        ];

        if ($dbOk) {
            try {
                $prodActivos = (int)db()->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
                $lowStock    = (int)db()->query("SELECT COUNT(*) FROM product_inventory i JOIN products p ON p.id=i.product_id WHERE p.is_active=1 AND i.stock < i.low_stock_threshold")->fetchColumn();
                $inactivos   = (int)db()->query("SELECT COUNT(*) FROM products WHERE is_active=0")->fetchColumn();
                $nuevos30    = (int)db()->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn();
                $kpis = [
                    ['label'=>'Productos activos','value'=>number_format($prodActivos,0,',','.'),'sub'=>'SKU activos'],
                    ['label'=>'Bajo stock','value'=>number_format($lowStock,0,',','.'),'sub'=>'SKUs'],
                    ['label'=>'Usuarios nuevos 30d','value'=>number_format($nuevos30,0,',','.'),'sub'=>'Usuarios'],
                    ['label'=>'Inactivos','value'=>number_format($inactivos,0,',','.'),'sub'=>'Productos'],
                ];
            } catch (\Throwable $e) { /* deja KPIs en — */ }
        }

        return [
            'kpis'    => $kpis,
            'checks'  => [
                ['label'=>'Entorno', 'value'=>$env],
                ['label'=>'Storage escribible', 'value'=>$storageWritable],
                ['label'=>'DB', 'value'=>$dbOk ? 'OK' : 'Down'],
            ],
            'featured'   => [],
            'lowStock'   => [],
            'lastImport' => null,
            'events'     => [],
            'pageId'     => 'admin-product-form',
        ];
    }
}
