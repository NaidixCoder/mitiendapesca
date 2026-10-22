<?php
declare(strict_types=1);
/** QA estático complementario a MySQL + HTTP; no muta datos. */
$root=dirname(__DIR__,2);
function readQ(string $file):string{
    global $root;
    $text=file_get_contents($root.'/'.$file);
    if(!is_string($text))throw new RuntimeException('Archivo no encontrado: '.$file);
    return $text;
}
$n=0;
function testQ(bool $ok,string $label):void{
    global $n;
    $n++;
    if(!$ok)throw new RuntimeException('FAIL: '.$label);
}
$repo=readQ('app/Repositories/Imports/ImportAttributeRelationsRepository.php');
$rev=readQ('app/Repositories/Imports/ImportRevertRepository.php');
$imp=readQ('app/Services/Import/AttributeImporter.php');
$svc=readQ('app/Services/Import/ImportService.php');
$norm=readQ('app/Services/Import/RowNormalizer.php');
$val=readQ('app/Services/Import/RowValidator.php');
$ui=readQ('app/Views/partials/admin/imports/view/rows.php');
$schema=readQ('database/schema.sql');
foreach(['relation_variant_values','relation_product_attrs','relation_product_attr_values'] as $name){
 testQ(str_contains($repo,"'".$name."'"),'Relation supported: '.$name);
 testQ(str_contains($imp,"'".$name."'"),'Relation captured: '.$name);
}
foreach(['product_variant_values','product_attributes','product_attribute_values'] as $table){
 testQ(str_contains($repo,"'".$table."'"),'Own relation SQL: '.$table);
 testQ(str_contains($schema,'CREATE TABLE IF NOT EXISTS '.$table.' ('),'Actual table exists: '.$table);
}
testQ(str_contains($repo,'array_diff($columns, array_keys($row))'),'BEFORE full columns validated');
testQ(str_contains($repo,'array_diff(array_keys($row), $columns)'),'No unknown columns accepted');
testQ(str_contains($repo,'count($columns)'),'Restore binds every column');
testQ(str_contains($repo,"'rows'=>".'$this->rows('),'BEFORE relationship collection captured');
testQ(str_contains($repo,"$".'this->db->inTransaction()'),'No unsafe restore outside SQL transaction');
testQ(str_contains($rev,'ImportAttributeRelationsRepository::supports'),'Rollback fingerprint routes to owner');
testQ(str_contains($rev,'->restore($entity, $id, $before)'),'Rollback restores relation BEFORE');
testQ(str_contains($imp,'relationOps'),'Importer passes relation ops');
testQ(str_contains($imp,'array_merge($rollbackOps, $relationOps)'),'Creation before relation operation');
testQ(str_contains($imp,'replaceForVariant'),'Existing value set replacement retained');
testQ(str_contains($imp,"throw new \\RuntimeException('No se pudieron reemplazar relaciones"),'SQL relation failures fail closed');
testQ(str_contains($svc,'new \\App\\Repositories\\Imports\\ImportAttributeRelationsRepository($db)'),'Dedicated repo injected');
testQ(str_contains($svc,'Atributos no reversibles'),'Unverifiable attribute write aborts SQL');
testQ(str_contains($norm,"$".'d[\'attr_\'.$i]'),'Official attr alias');
testQ(str_contains($norm,"$".'d[\'atributo_\'.$i]'),'Legacy alias retained');
testQ(str_contains($norm,'_attr_error'),'Incomplete pairs flagged');
testQ(str_contains($val,'_attr_error'),'Validator blocks incomplete pair');
testQ(str_contains($ui,'El detalle refleja los datos registrados'),'Result UI text current contract');
echo "OK: $n verificaciones de relaciones de atributos reversibles.".PHP_EOL;
