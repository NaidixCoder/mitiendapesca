<?php
namespace App\Controllers\Admin;

use App\Services\DashboardService;

class DashboardController extends BaseController {
  public function index(){
    $data = (new DashboardService())->collect();   // ← junta kpis, checks, featured, lowStock, etc.
    render_page('admin/dashboard', $data);         // ← PASAR $data A LA VISTA
  }

  public function chartData(){
    $range = (int)($_GET['range'] ?? 7);
    if(!in_array($range,[7,30,365],true)) $range = 7;

    $st = db()->prepare("
      SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(total_amount),0) amt
      FROM orders
      WHERE created_at >= (CURRENT_DATE - INTERVAL :days DAY)
      GROUP BY DATE(created_at)
      ORDER BY d
    ");
    $st->execute([':days'=>$range]);
    $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

    json([
      'ok'=>true,
      'range'=>$range,
      'labels'=>array_column($rows,'d'),
      'series'=>[
        ['name'=>'Pedidos','data'=>array_map('intval',array_column($rows,'c'))],
        ['name'=>'Ingresos','data'=>array_map('floatval',array_column($rows,'amt'))],
      ]
    ]);
  }
}
