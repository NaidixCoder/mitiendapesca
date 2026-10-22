<?php
declare(strict_types=1);
/** Importaciones V1C: contrato de snapshots, whitelist y fail-closed, sin DB. */
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
use App\Services\Import\ImportRevertService;
$checks=0;
function verify(bool $ok,string $label): void {
    global $checks;
    $checks++;
    if (!$ok) throw new RuntimeException("FAIL $label");
}
$base=['version'=>3,'job_id'=>10,'complete'=>true,'ops'=>[
 ['entity'=>'product','action'=>'update','pk'=>['id'=>7],'before'=>['price'=>1000,'cost'=>null],'after_sha256'=>str_repeat('a',64)],
 ['entity'=>'inventory_product','action'=>'create','pk'=>['id'=>7],'after_sha256'=>str_repeat('b',64),'after_rel_sha256'=>str_repeat('c',64)],
]];
verify(ImportRevertService::isSnapshotSafe($base),'v3 válido');
foreach ([
 ['version'=>2],
 ['complete'=>false],
 ['ops'=>[]],
 ['ops'=>[['entity'=>'product','action'=>'update','pk'=>['id'=>7],'before'=>['price'=>0]]]],
 ['ops'=>[['entity'=>'product','action'=>'delete','pk'=>['id'=>7],'after_sha256'=>str_repeat('c',64)]]],
 ['ops'=>[['entity'=>'product','action'=>'update','pk'=>['id'=>0],'before'=>['price'=>1],'after_sha256'=>str_repeat('c',64)]]],
 ['ops'=>[['entity'=>'product','action'=>'update','pk'=>['id'=>7],'before'=>[],'after_sha256'=>str_repeat('c',64)]]],
 ['ops'=>[['entity'=>'product','action'=>'create','pk'=>['id'=>7],'after_sha256'=>'bad']]],
 ['ops'=>[$base['ops'][0], $base['ops'][0]]],
 ['ops'=>[['entity'=>'product','action'=>'create','pk'=>['id'=>7],'after_sha256'=>str_repeat('a',64)]]],
] as $change) {
 verify(!ImportRevertService::isSnapshotSafe(array_replace($base,$change)), 'snapshot inválido fail-closed');
}
verify(!ImportRevertService::isSnapshotSafe(null),'snapshot ausente');
$repo=file_get_contents($root.'/app/Repositories/Imports/ImportRevertRepository.php');
verify(str_contains($repo,'FOR UPDATE'),'lectura con lock');
verify(str_contains($repo,"'inventory_variant' => ['inventory_variants', 'variant_id']"),'variante inventario');
verify(str_contains($repo,"'attribute_value' => ['attribute_values', 'id']"),'atributos');
verify(str_contains($repo,'JSON_THROW_ON_ERROR'),'hash sin serialización silenciosa');
verify(str_contains($repo,'dependentFingerprint('),'dependencias FK verificables');
verify(str_contains($repo,"'product_variant_values','variant_id'"),'dependencias variantes y atributos');
$svc=file_get_contents($root.'/app/Services/Import/ImportRevertService.php');
verify(str_contains($svc,'lockLatestPendingJobId()'),'LIFO transaccional');
verify(strpos($svc,'fingerprint($ent, $id, true)') < strpos($svc,'->restore('),'verificar primero, restaurar después');
$import=file_get_contents($root.'/app/Services/Import/ImportService.php');
verify(str_contains($import,"'version'=>3") && str_contains($import,"'after_sha256'"),'generación v3');
echo "OK: $checks verificaciones de rollback v3 (sin BD).".PHP_EOL;
