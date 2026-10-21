<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/marketing/campanas/new';
$_SERVER['REQUEST_METHOD']='GET';
require BASE_PATH.'/vendor/autoload.php';
foreach (glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH.'/app/Views/helpers.php';
try {
  $formRow=[
    'id'=>0,'name'=>'','slug'=>'','destination_path'=>'/productos',
    'utm_source'=>'','utm_medium'=>'','utm_campaign'=>'',
    'utm_content'=>'','utm_term'=>'','is_active'=>1,'notes'=>'',
  ];
  $renderForm = static function(array $row): string {
    ob_start();
    render_page('admin/marketing_campaigns/form', ['pageId'=>'admin-marketing-campaign-form','campaign'=>$row]);
    return (string)ob_get_clean();
  };
  $new = $renderForm($formRow);
  $updated = $renderForm(array_replace($formRow, [
    'id'=>117,'name'=>'Instagram primavera','slug'=>'instagram-primavera','utm_source'=>'instagram',
    'utm_content'=>'historia','utm_term'=>'reeles','notes'=>'Prueba de notas',
  ]));
  foreach(['data-marketing-campaign-form','data-marketing-slug','data-marketing-preview-copy','name="name"','name="destination_path"','name="utm_source"','name="utm_term"','Crear campaña'] as $t)
    if (!str_contains($new, $t)) throw new RuntimeException('Formulario nuevo sin '.$t);
  foreach(['Guardar cambios','name="id" value="117"','instagram-primavera','Parámetros y notas opcionales'] as $t)
    if (!str_contains($updated, $t)) throw new RuntimeException('Formulario edición sin '.$t);
  if (!str_contains($new, 'aria-disabled="true"') || !str_contains($updated,'target="_blank"'))
    throw new RuntimeException('Preview del enlace incorrecto');
  $campaign=['id'=>117,'name'=>'Instagram primavera','slug'=>'instagram-primavera','destination_path'=>'/productos',
    'utm_campaign'=>'primavera','notes'=>'Notas de campaña','is_active'=>1];
  $model=new App\Services\Admin\MarketingCampaigns\ViewModels\MarketingCampaignsShowViewModel();
  $rich=$model->build([
    'campaign'=>$campaign,'days'=>30,
    'report'=>['hits'=>50,'new_users'=>7,'add_to_cart'=>4,'purchases'=>2,'revenue'=>120000,
      'ratio_new_users'=>14.0,'ratio_add_to_cart'=>8.0,'ratio_purchases'=>4.0],
    'series'=>[['date'=>'2026-10-20','hits'=>3,'new_users'=>1,'add_to_cart'=>2,'purchases'=>1,'revenue'=>120000]],
    'recentHits'=>[['created_at'=>'2026-10-20','referrer'=>'https://instagram.com/link']],
    'recentOrders'=>[['id'=>10,'total'=>120000,'shipping_full_name'=>'Cliente Demo','created_at'=>'2026-10-20']],
    'topPages'=>[['path'=>'/productos','views'=>3]],
    'topProductsViewed'=>[['product_name'=>'Caña','product_slug'=>'cana','variant_title'=>'','views'=>5]],
    'topProductsAdded'=>[['product_name'=>'Reel','product_slug'=>'reel','variant_title'=>'','qty'=>2]],
  ]);
  $empty=$model->build(['campaign'=>$campaign,'days'=>7]);
  $renderShow=static function(array $payload):string {
    ob_start();
    render_page('admin/marketing_campaigns/show',array_merge(['pageId'=>'admin-marketing-campaigns'], $payload));
    return (string)ob_get_clean();
  };
  $shown=$renderShow($rich);
  $blank=$renderShow($empty);
  foreach(['data-marketing-detail','Enlace de campaña','Rendimiento','Evolución diaria',
    'Qué visitaron los clientes','Visitas recientes','Compras atribuidas','Descargar PNG',
    'data-copy-text', 'Cliente Demo', 'Notas de campaña'] as $t)
    if (!str_contains($shown,$t)) throw new RuntimeException('Detalle completo sin '.$t);
  foreach(['No hay visitas registradas','No hay compras atribuidas','Todavía no hay actividad registrada'] as $t)
    if (!str_contains($blank,$t)) throw new RuntimeException('Detalle vacío sin '.$t);
  $showHeads = preg_match_all('/<header class="av3-page-header(?:\\s|")/', $shown);
  $newHeads = preg_match_all('/<header class="av3-page-header(?:\\s|")/', $new);
  if ($showHeads !== 1 || $newHeads !== 1)
    throw new RuntimeException('Header maestro duplicado o ausente: show='.$showHeads.' new='.$newHeads);
  echo "[marketing-forms] OK: nuevo, editar, detalle con datos, detalle vacío; sin escritura ni envíos\n";
  if (in_array('--snapshots',$argv??[],true)) {
    $dest = 'C:/xampp/mi-pesca-backups/marketing_campaign_editor_20261020_153336';
    foreach (['new'=>$new,'edit'=>$updated,'show'=>$shown,'show_empty'=>$blank] as $k=>$html) {
      $file=$dest.'/snapshot_'.$k.'.html'; file_put_contents($file,$html);
      echo "[marketing-forms] Snapshot: ".$file."\n";
    }
  }
} catch (Throwable $e) {
  while(ob_get_level()>0)ob_end_clean();
  fwrite(STDERR,"[marketing-forms] ERROR: ".$e->getMessage()."\n"); exit(1);
}
