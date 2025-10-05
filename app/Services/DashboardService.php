<?php
namespace App\Services;

class DashboardService {
    public function collect(): array {
        $pdo = db();
        $dbOk = true;
        try { $pdo->query('SELECT 1'); } catch (\Throwable $e) { $dbOk = false; }

        $env = env('APP_ENV','production');
        $storageDir = base_path('storage');
        $storageWritable = (is_dir($storageDir) && is_writable($storageDir)) ? 'OK' : 'Revisar';

        $prodActivos = $dbOk ? cache_remember('kpi:prod_activos',60, fn()
        => (int)db()->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn()
        ) : 0;

        $usuariosTot = $dbOk ? cache_remember('kpi:users_tot',60, fn()
        => (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn()
        ) : 0;

        $stockBajo = $inactivos = $nuevos30 = 0;
        if ($dbOk){
        $stockBajo = cache_remember('kpi:stock_bajo',60, fn() => (int)db()->query("
            SELECT COUNT(*) FROM inventory i
            JOIN products p ON p.id=i.product_id
            WHERE p.is_active=1 AND i.stock < i.low_stock_threshold
        ")->fetchColumn());

        $inactivos = cache_remember('kpi:inactivos',60, fn()
            => (int)db()->query("SELECT COUNT(*) FROM products WHERE is_active=0")->fetchColumn()
        );

        $nuevos30 = cache_remember('kpi:users_new:30d',60, fn()
            => (int)db()->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 30 DAY")->fetchColumn()
        );
        }

        $kpis = [
        ['label'=>'Productos activos','value'=>number_format($prodActivos,0,',','.'),'sub'=>'SKU activos'],
        ['label'=>'Usuarios nuevos (30d)','value'=>number_format($nuevos30,0,',','.'),'sub'=>'Altas recientes'],
        ['label'=>'Productos inactivos','value'=>number_format($inactivos,0,',','.'),'sub'=>'Despublicados'],
        ['label'=>'Stock bajo','value'=>number_format($stockBajo,0,',','.'),'sub'=>'Bajo umbral'],
        ];

        $checks = [
        ['label'=>'Entorno', 'value'=>$env, 'ok'=>true],
        ['label'=>'DB conexión', 'value'=>$dbOk?'OK':'Error', 'ok'=>$dbOk],
        ['label'=>'/storage permisos', 'value'=>$storageWritable, 'ok'=>$storageWritable==='OK'],
        ['label'=>'CSP', 'value'=>'Activo', 'ok'=>true],
        ['label'=>'Sesión', 'value'=>session_id()?'Activa':'—', 'ok'=>true],
        ['label'=>'Versión PHP', 'value'=>PHP_VERSION, 'ok'=>version_compare(PHP_VERSION,'8.1','>=')],
        ];

        $lowStock = $dbOk ? cache_remember('list:low_stock_top10',60, fn() => db()->query("
        SELECT p.id, p.sku, p.name, i.stock, i.low_stock_threshold
        FROM inventory i
        JOIN products p ON p.id = i.product_id
        WHERE p.is_active=1 AND i.stock < i.low_stock_threshold
        ORDER BY (i.low_stock_threshold - i.stock) DESC, p.created_at DESC
        LIMIT 10
        ")->fetchAll(\PDO::FETCH_ASSOC)) : [];

        $lastImport = null;
        if ($dbOk) {
        $hasJobs = cache_remember('table:has_import_jobs',60, fn()
            => (bool)db()->query("SHOW TABLES LIKE 'import_jobs'")->fetchColumn()
        );
        if ($hasJobs) {
            $lastImport = cache_remember('obj:last_import',60, fn()
            => db()->query("SELECT * FROM import_jobs ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC) ?: null
            );
        }
        }

        $events = [];
        if ($dbOk) {
        $hasAudit = cache_remember('table:has_admin_audit',60, fn()
            => (bool)db()->query("SHOW TABLES LIKE 'admin_audit_log'")->fetchColumn()
        );
        if ($hasAudit) {
            $events = cache_remember('feed:audit_recent8',30, fn()
            => db()->query("
                SELECT action, entity_type, entity_id, metadata, created_at
                FROM admin_audit_log
                ORDER BY id DESC
                LIMIT 8
                ")->fetchAll(\PDO::FETCH_ASSOC)
            );
        }
        }

        $featured = $dbOk ? cache_remember('list:featured_recent5',60, fn()
        => db()->query("
            SELECT id, sku, name, price, is_active
            FROM products
            ORDER BY created_at DESC
            LIMIT 5
            ")->fetchAll(\PDO::FETCH_ASSOC)
        ) : [];

        return compact('env','usuariosTot','kpis','checks','lowStock','lastImport','events','featured');
    }
}
