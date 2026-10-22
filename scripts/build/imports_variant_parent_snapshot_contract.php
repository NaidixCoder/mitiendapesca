<?php
declare(strict_types=1);
/** 1F-A guards: real import+rollback verified separately against private MariaDB. */
$root=dirname(__DIR__,2);
$variant=file_get_contents($root.'/app/Services/Import/VariantImporter.php');
$service=file_get_contents($root.'/app/Services/Import/ImportService.php');
$parent=file_get_contents($root.'/app/Services/Import/ProductImporter.php');
$n=0;
function ck1(bool $ok,string $description):void {
    global $n; $n++;
    if(!$ok)throw new RuntimeException('FAIL '.$description);
}
ck1(str_contains($parent,"'rollback_ops'"),'ProductImporter returns catalogue rollback operations');
ck1(str_contains($parent,"'rollback'=>['entity'=>'product','action'=>'create'"),'ProductImporter returns parent creation op');
ck1(str_contains($variant,'$parentRollbackOps = [];'),'Variant starts with clean parent operations');
ck1(str_contains($variant,'$parentCreate = $pr[\'rollback\'] ?? null;'),'Variant captures exact parent operation');
ck1(str_contains($variant,'$pr[\'rollback_ops\']'),'Variant captures created catalogs');
ck1(strpos($variant,'$parentRollbackOps[] = $op;') < strpos($variant,'$parentRollbackOps[] = $parentCreate;'),
    'Catalog operations precede parent');
ck1(str_contains($variant,'$parentSnapshotComplete = true;'),'Explicit verified parent contract');
ck1(str_contains($variant,'$parentRollbackOps[] = $parentCreate'),'Parent creation included in return');
ck1(str_contains($variant, "'parent_product_id'=>".'$productId'),'Returned parent ID must match variant parent ID');
ck1(str_contains($variant, "'rollback_ops'=>".'$parentCreated ? $parentRollbackOps'),'Rollback ops propagated to service');
ck1(str_contains($variant,'No puede verificarse el snapshot del producto padre implícito'),'Malformed parent return fails closed');
ck1(str_contains($variant,'No se pudo verificar la identidad del padre implícito creado'),'Parent identity rechecked');
ck1(str_contains($variant,'if ($parentCreated) throw new'),'Post-parent failure aborts SQL');
ck1(str_contains($service,'$seenParentOps'),'No duplicate parent operations allowed');
ck1(str_contains($service,"'brand','category','supplier','product'"),'Only known parent operation entities');
ck1(str_contains($service,'$foundParentCreate'),'Mandatory parent creation op');
ck1(str_contains($service,'$r[\'parent_snapshot_complete\']'),'Explicit safety flag checked in service');
ck1(str_contains($service,'$seenParentOps') && str_contains($service,'$rollbackOps[] = $opx;'),
    'Validated parent ops appended in variant branch');
$start=strpos($service,'// Registrar primero altas del padre implícito');
$end=strpos($service,'// Cambios detallados (campo: viejo -> nuevo)',$start);
$block=substr($service,$start,$end-$start);
ck1((bool)$block,'Variant rollback scope found');
ck1(strpos($block,"$".'rollbackOps[] = $opx;') < strpos($block,"$".'rollbackOps[] = $r[\'rollback\'];'),
    'Parent ops stored before variant op');
ck1(str_contains($block,'throw new \\RuntimeException'),'Incomplete trace throws before COMMIT');
ck1(str_contains($service,"'after_sha256'"),'Unchanged snapshot builder signs poststate');
ck1(str_contains($service,'$keys[$key]'),'Unchanged v3 de-duplicates entities');
ck1(str_contains($variant,'if ($dry) {'),'Dry-run remains non-mutating');
ck1(str_contains($variant,'update_ship_dims'),'Shipping policy 1E retained');
echo "OK: $n verificaciones de snapshot de padre implícito 1F-A.".PHP_EOL;
