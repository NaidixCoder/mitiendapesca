<?php
declare(strict_types=1);
/** Contrato V1E: sin DB, campos exactos + política ship dimensions. */
$root=dirname(__DIR__,2);
$repo=file_get_contents($root.'/app/Repositories/Imports/ProductVariantImportRepository.php');
$imp=file_get_contents($root.'/app/Services/Import/VariantImporter.php');
$schema=file_get_contents($root.'/database/schema.sql');
function assertV(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException('FAIL '.$s);}
$n=0;
preg_match('/CREATE TABLE IF NOT EXISTS product_variants\s*\((.*?)\)\s*ENGINE=InnoDB/s',$schema,$m);
$columns=(string)($m[1]??'');
assertV($columns!=='','Product variants table exists');
foreach(['id','product_id','sku','internal_code','title','price','list_price','price_updated_at','cost',
'cost_currency_id','margin_pct','is_active'] as $c){
 assertV((bool)preg_match('/^\s*'.preg_quote($c,'/').'\s+/m',$columns),'Actual column '.$c);
}
foreach(['ship_weight','ship_height','ship_width','ship_length'] as $c){
 assertV(!preg_match('/^\s*'.preg_quote($c,'/').'\s+/m',$columns),'Ship dimension absent in variants '.$c);
 assertV(!str_contains($repo,"'".$c."'") && !preg_match('/\b'.preg_quote($c,'/').'\b/',$repo),
    'No invalid SQL/field reference '.$c);
 assertV(str_contains($imp,"'".$c."'"),'Explicit guard for '.$c);
}
assertV(str_contains($repo,'price_wh1_override') && str_contains($repo,'price_wh2_override'),
    'Optional wholesale override preserved');
assertV(str_contains($repo,"$".'this->hasColumn('),'Optional real-column detection');
assertV(str_contains($repo,"$".'cols[] = $override;'),'Dynamic insert of optional existing override');
assertV(str_contains($repo,"cost_currency_id") && str_contains($repo,'margin_pct'),'Currency and margin preserved');
assertV(str_contains($repo,"price_updated_at"),'Price tracking preserved');
assertV(str_contains($imp,"if (!empty(".'$opt'."['update_ship_dims']))"),'Guard checks explicit option');
assertV(strpos($imp,'update_ship_dims')<strpos($imp,'$this->products->findBySku'),'Guard before parent query');
assertV(strpos($imp,'update_ship_dims')<strpos($imp,'$this->products->updateById'),'Guard before parent writes');
assertV(str_contains($imp,'Las dimensiones de envío por variante no están disponibles'),'Clear dry-run message');
assertV(!preg_match('/\$fields\[\$k\]\s*=\s*\(int\)\$v/',$imp),'No silent dimension fields');
echo "OK: $n verificaciones de variantes y dimensiones.".PHP_EOL;
