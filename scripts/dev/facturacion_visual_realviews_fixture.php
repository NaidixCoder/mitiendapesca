<?php
declare(strict_types=1);
/** Sólo presentación. Renderiza VISTAS REALES y layout REAL con datos sintéticos;
 * sin bootstrap, sin DB, sin cookies y sin peticiones de red. */
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 'stderr');
define('BASE_PATH', dirname(__DIR__, 2));
$GLOBALS['_admin_master_capture'] = true;
$_SERVER['REQUEST_URI']='/admin/facturacion/'.(string)($argv[1]??'control/index');
function e($x):string{return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function base_url(string $p=''):string{return '/'.ltrim($p,'/');}
function url(string $p=''):string{return '/'.ltrim($p,'/');}
function app_setting(string $key,$fallback=null){return $fallback;}
function csrf_field():string{return '<input type="hidden" name="_csrf" value="visual-only">';}
function money($number):string{return '$ '.number_format((float)$number,2,',','.');}
function admin_crumbs(string $what):array{return [['label'=>'Admin','href'=>'/admin'],['label'=>'Facturación','href'=>'/admin/facturacion/control']];}
function render_partial(string $name,array $vars=[]):void{
    if($name==='admin/subnav')return;
    $file=BASE_PATH.'/app/Views/partials/'.$name.'.php';
    if(is_file($file)){extract($vars,EXTR_SKIP);require $file;}
}
$items=[[
    'id'=>101,'issued_at'=>'2026-10-21','supplier_nombre'=>'Proveedor sintético','supplier_cuit'=>'30123456789',
    'cbte_tipo'=>11,'pto_vta'=>5,'cbte_nro'=>1234,'kind_label'=>'Factura','neto_label'=>'$ 100.000,00',
    'iva_label'=>'$ 21.000,00','total_label'=>'$ 121.000,00','status'=>'validated',
    'status_label'=>'Validada','status_badge_class'=>'badge-success',
    'edit_url'=>'/admin/facturacion/compras/edit?id=101','validate_url'=>'/admin/facturacion/compras/validate',
    'void_url'=>'/admin/facturacion/compras/void','show_validate'=>false,'show_void'=>true
]];
$control_vm=['loaded'=>2,'missing'=>1,'unclosed'=>1,'total_alerts'=>4,'current_period'=>'2026-10',
'last_closure'=>'2026-09','period_state'=>'Con alertas','period_pill_label'=>'Revisar pendientes',
'period_pill_dot_class'=>'is-warning','quick_links'=>[['label'=>'Libro IVA Ventas','url'=>'/admin/facturacion/libro-iva'],['label'=>'Reportes fiscales','url'=>'/admin/facturacion/reportes']],
'latest_closures'=>[['period'=>'2026-09','iva_ventas'=>250000,'iva_compras'=>98000,'saldo_iva'=>152000]]];
$crumbs=admin_crumbs('facturacion.control');
$isRI=true;$is_ri=true;
$filters=['from'=>'2026-10-01','to'=>'2026-10-23','q'=>'','status'=>'','kind'=>'','supplier_id'=>0,'date_field'=>'issued','date_from'=>'','date_to'=>''];
$sampleOptions=[['value'=>'','label'=>'Todos','selected'=>true]];
$supplier_options=$kind_options=$status_options=$env_options=$group_options=$sampleOptions;
$items_prepared=$rows_prepared=$closure_prepared=$items;
$closures_prepared=[['period'=>'2026-09','iva_ventas_label'=>'$ 250.000','iva_compras_label'=>'$ 98.000','saldo_iva_label'=>'$ 152.000','closed_at'=>'2026-10-02','show_url'=>'/admin/facturacion/cierres-mensuales/show?period=2026-09','csv_url'=>'/admin/facturacion/cierres-mensuales/export?period=2026-09']];
$period_options=[['period'=>'2026-10','label'=>'Octubre 2026','has_movements'=>true,'is_closed'=>false,'selected'=>true]];
$totals=['total_label'=>'$ 121.000','neto_label'=>'$ 100.000','iva_label'=>'$ 21.000','comprobantes_count'=>1,'facturas_count'=>1,'notas_credito_count'=>0];
$totals_cards=[['label'=>'Ventas','value'=>'$ 121.000'],['label'=>'IVA','value'=>'$ 21.000']];
$summary_cards=$totals_cards;
$values=['supplier_id'=>1,'issued_at'=>'2026-10-21','imp_neto'=>100000,'imp_iva'=>21000,'imp_total'=>121000];
$errors=[];$attachments_prepared=[];$is_edit=false;$item_id=0;
$csrf=csrf_field();$csrf_field=csrf_field();
$has_rows=$has_items=$has_closures=true;
$loaded_count=1;$missing_count=1;$unclosed_count=1;$closures_count=1;
$total=51;$page=2;$pages=3;$show_pagination=true;
$pagination=['page'=>2,'pages'=>3,'total'=>51,'from'=>26,'to'=>26,'prev_url'=>'/admin/facturacion/compras?page=1','next_url'=>'/admin/facturacion/compras?page=3'];
$apply_url='/admin/facturacion/compras';$reset_url=$apply_url;$new_url='/admin/facturacion/compras/new';
$back_url='/admin/facturacion/control';$export_url='/admin/facturacion/reportes/export';
$export_summary_url=$export_detail_url=$export_url;
$tab_resumen_url='/admin/facturacion/reportes/resumen';
$tab_conciliacion_url='/admin/facturacion/reportes/resumen?tab=conciliacion';
$tab='resumen';
$reportes_url='/admin/facturacion/reportes';
$close_url='/admin/facturacion/cierres-mensuales/close';
$rows_count=1;$series_count=1;
$series_rows=[['period'=>'2026-10','invoices_count'=>1,'invoices_total'=>121000,'credit_notes_count'=>0,'credit_notes_total'=>0]];
$conc_orders_missing_invoice=$conc_comprobantes_not_issued=$conc_deviations=$conc_duplicates_exact=$conc_non_billable_with_invoice=[];
$requested=(string)($argv[1]??'control/index');
$root=realpath(BASE_PATH.'/app/Views/pages/admin/facturacion');
$file=realpath($root.'/'.$requested.'.php');
if(!$file || !str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/')) {
    fwrite(STDERR,"Ruta de presentación fuera del módulo\n");exit(2);
}
ob_start();
require $file;
$adminMasterContent=ob_get_clean();
ob_start();
require BASE_PATH.'/app/Views/layouts/admin.php';
$masterHtml=ob_get_clean();
$styles='';
foreach(['tokens.css','base.css','layout.css','components/master-shell.css','components/page-header.css',
'components/primitives.css','components/design-system.css','components/admin-components.css','pages/billing.css'] as $part){
    $asset=BASE_PATH.'/public_html/assets/css/admin-v3/'.$part;
    if(is_file($asset)) $styles.='<link rel="stylesheet" href="file:///'.e(str_replace('\\','/',$asset)).'">';
}
echo '<!doctype html><html lang="es-AR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'.$styles.'</head><body data-page="admin-facturacion-control" class="av3-expanded">'.$masterHtml.'</body></html>';
