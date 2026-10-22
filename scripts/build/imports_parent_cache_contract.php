<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$variant=file_get_contents($root.'/app/Services/Import/VariantImporter.php');
$parent=file_get_contents($root.'/app/Repositories/Imports/ImportProductStateRepository.php');
$repo=file_get_contents($root.'/app/Repositories/Imports/ImportRevertRepository.php');
$service=file_get_contents($root.'/app/Services/Import/ImportService.php');
$n=0;
function checkQ(bool $v,string $s):void{global $n;$n++;if(!$v)throw new RuntimeException("FAIL ".$s);}
checkQ(str_contains($parent,'FOR UPDATE'),'Locks parent before capture');
foreach(['sale_mode','price_from_cache','list_price_from_cache'] as $field){
 checkQ(str_contains($parent,"'".$field."'"),'Parent BEFORE field '.$field);
 checkQ(str_contains($repo,"'".$field."'"),'Restore whitelist '.$field);
}
checkQ(str_contains($variant,'captureVariantParent('),'Variant writes must capture parent');
checkQ(str_contains($variant,'$parentExistingRollback'),'Parent operation emitted');
checkQ(str_contains($variant,'$parentCreated ? $parentRollbackOps'),'Original implicit parent ops unaffected');
checkQ(str_contains($service,'new \\App\\Repositories\\Imports\\ImportProductStateRepository($db)'),'Dedicated owner injected');
checkQ(str_contains($variant,'SKU de variante pertenece a otro padre'),'Ownership guard');
checkQ(str_contains($variant,'No se pudo actualizar variante sin riesgo'),'Fail closed after parent update');
echo "OK: ".$n." verificaciones de huellas de cache/mode del padre." . PHP_EOL;
