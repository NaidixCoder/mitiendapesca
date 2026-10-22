<?php
declare(strict_types=1);
/**
 * Test destructivo exclusivamente contra la MariaDB privada del módulo.
 * Conexión fija a 127.0.0.1:18991, base mi_attrs_v2_20261022.
 * Nunca importa boot ni .env ni reutiliza credenciales de producción.
 */
define('BASE_PATH', dirname(__DIR__,2));
require BASE_PATH.'/vendor/autoload.php';
use App\Services\Admin\Variants\VariantAttributeValueDeleteService as DeleteService;
use App\Services\Admin\Variants\VariantValueSetsService as SetsService;

const PORT=18991;
const SCHEMA='mi_attrs_v2_20261022';
if (PORT===3306 || !str_starts_with(SCHEMA, 'mi_attrs_v2_')) throw new RuntimeException('REFUSED unsafe database');
function connect():PDO {
    $db=new PDO('mysql:host=127.0.0.1;port='.PORT.';dbname='.SCHEMA.';charset=utf8mb4',
      (string)(getenv('ATTRIBUTES_QA_USER') ?: 'root'), (string)(getenv('ATTRIBUTES_QA_PASSWORD') ?: ''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    if ((string)$db->query('SELECT DATABASE()')->fetchColumn()!==SCHEMA
        || (int)$db->query('SELECT @@port')->fetchColumn()!==PORT) throw new RuntimeException('REFUSED unexpected DB');
    return $db;
}
$db=connect();
function sql(PDO $d,string $q,array $params=[]):void {$s=$d->prepare($q);$s->execute($params);}
function countRows(PDO $d,string $tbl,string $where='1=1'):int {return (int)$d->query("SELECT COUNT(*) FROM $tbl WHERE $where")->fetchColumn();}
function ok(bool $value,string $msg):void {if(!$value)throw new RuntimeException($msg);}
function snapshot(PDO $d):array {
 $snapshot=[];
 foreach(['attribute_values','product_attribute_values','product_variant_values','variant_value_sets','variant_value_set_items'] as $t){
   $snapshot[$t]=$d->query("SELECT * FROM $t ORDER BY 1,2")->fetchAll(PDO::FETCH_ASSOC);
 }
 return $snapshot;
}
function scenario(PDO $d,string $name,callable $fn):void{
 $d->beginTransaction();
 try {$fn();$d->rollBack();echo "[MariaDB] PASS $name\n";}
 catch(Throwable $e){if($d->inTransaction())$d->rollBack();fwrite(STDERR,"[MariaDB] FAIL $name ".$e->getMessage()."\n");throw $e;}
}
// Datos totalmente sintéticos, aislados. No se usan IDs de la base comercial.
sql($db,"INSERT IGNORE INTO categories (id,name,slug,is_active) VALUES (701,'QA Cat 1','qa-cat-701',1),(702,'QA Cat 2','qa-cat-702',1)");
sql($db,"INSERT IGNORE INTO brands (id,name,slug) VALUES (701,'QA Marca 1','qa-marca-701'),(702,'QA Marca 2','qa-marca-702')");
sql($db,"INSERT IGNORE INTO attributes (id,name,slug,is_variant_axis) VALUES (701,'QA Color','qa-color',1),(702,'QA Talle','qa-talle',1),(703,'QA Observación','qa-obs',0)");
sql($db,"INSERT IGNORE INTO attribute_values (id,attribute_id,value) VALUES (701,701,'Rojo'),(702,701,'Azul'),(703,702,'L'),(704,703,'No variante'),(705,701,'Libre'),(706,701,'Disponible')");
sql($db,"INSERT IGNORE INTO products (id,slug,name,category_id,brand_id,sale_mode) VALUES (701,'qa-pesca-a','QA Producto A',701,701,'variants'),(702,'qa-pesca-b','QA Producto B',702,702,'variants')");
sql($db,"INSERT IGNORE INTO product_variants (id,product_id,title) VALUES (701,701,'Rojo'),(702,702,'Azul')");
sql($db,"INSERT IGNORE INTO product_attributes (product_id,attribute_id,sort,is_required) VALUES (701,701,10,1),(701,702,20,1),(702,701,10,1)");
sql($db,"INSERT IGNORE INTO product_attribute_values (product_id,attribute_id,value_id) VALUES (701,701,701),(701,702,703)");
sql($db,"INSERT IGNORE INTO product_variant_values (variant_id,attribute_id,value_id) VALUES (701,701,701)");
sql($db,"INSERT IGNORE INTO variant_value_sets (id,name,category_id,brand_id) VALUES (701,'QA plantilla',701,701),(702,'QA plantilla 2',701,701)");
sql($db,"INSERT IGNORE INTO variant_value_set_items (set_id,attribute_id,value_id) VALUES (701,701,702),(701,702,703)");
$del=new DeleteService($db);
$sets=new SetsService($db);
scenario($db,'bloqueo seleccion producto-only + snapshot', function()use($db,$del){
  $before=snapshot($db);$r=$del->deleteAttributeValue(703,702);
  ok(!$r['ok'] && $r['code']==='VALUE_IN_USE' && $r['usage']['selected_in_products']===1,'faltó bloqueo product-only');
  ok(snapshot($db)===$before,'se alteraron relaciones');
});
scenario($db,'bloqueo valor utilizado por variante',function()use($db,$del){
 $before=snapshot($db);$r=$del->deleteAttributeValue(701,701);
 ok(!$r['ok'] && $r['usage']['used_in_variants']===1,'faltó bloqueo variante');
 ok(snapshot($db)===$before,'perdida de asociación');
});
scenario($db,'bloqueo valor utilizado solo por plantilla',function()use($db,$del){
 $before=snapshot($db);$r=$del->deleteAttributeValue(702,701);
 ok(!$r['ok'] && $r['usage']['used_in_sets']===1,'faltó bloqueo plantilla');
 ok(snapshot($db)===$before,'perdida de asociaciones');
});
scenario($db,'eliminar valor libre permitido',function()use($db,$del){
 $r=$del->deleteAttributeValue(705,701);
 ok($r['ok'] && countRows($db,'attribute_values','id=705')===0,'valor libre no eliminado');
 ok(countRows($db,'product_attribute_values')===2,'selecciones cambiadas');
});
scenario($db,'prune conserva valor con dependencias',function()use($db,$del){
 $before=snapshot($db);ok(!$del->pruneAttributeValueIfUnused(701),'prune borró usado');
 ok(snapshot($db)===$before,'cambió asociaciones');
});
scenario($db,'prune permite huérfano',function()use($db,$del){
 ok($del->pruneAttributeValueIfUnused(706),'prune no eliminó valor libre');
 ok(countRows($db,'attribute_values','id=706')===0,'persistió');
});
scenario($db,'mismatch atributo/valor no borra',function()use($db,$del){
 $before=snapshot($db);$r=$del->deleteAttributeValue(705,702);
 ok(!$r['ok'] && $r['code']==='MISMATCH','mismatch incorrecto');
 ok(snapshot($db)===$before,'borrado ilegal');
});
scenario($db,'valor inexistente no borra',function()use($db,$del){
 ok(($del->deleteAttributeValue(999999)['code']??'')==='NOT_FOUND','not found no detectado');
});
scenario($db,'update plantilla válida guarda todo',function()use($db,$sets){
 $beforeSelected=$db->query('SELECT * FROM product_attribute_values ORDER BY 1,2')->fetchAll();
 ok($sets->updateValueSet(701,'QA final',702,702,[[701,701],[701,702],[702,703]]),'update válida falló '.$sets->lastError());
 ok(countRows($db,'variant_value_set_items','set_id=701')===3,'items count erróneo');
 ok($db->query('SELECT name FROM variant_value_sets WHERE id=701')->fetchColumn()==='QA final','nombre no persistió');
 ok($beforeSelected===$db->query('SELECT * FROM product_attribute_values ORDER BY 1,2')->fetchAll(),'update alteró productos');
});
foreach([
 'par cruzado'=>[[702,701]],
 'atributo no eje'=>[[703,704]],
 'valor inexistente'=>[[701,9999]],
 'duplicado'=>[[701,701],[701,701]],
] as $name=>$pairs){
 scenario($db,'update rechaza '.$name.' y rollback completo',function()use($db,$sets,$pairs){
  $before=snapshot($db);ok(!$sets->updateValueSet(701,'QA cambio rechazado',701,701,$pairs),'aceptó incorrecto');
  ok(snapshot($db)===$before,'rollback incompleto');
 });
}
foreach([
 'categoria inexistente'=>[99999,701,'QA nuevo'],
 'marca inexistente'=>[701,99999,'QA nuevo'],
 'plantilla duplicada'=>[701,701,'QA plantilla 2'],
] as $name=>$params){
 scenario($db,'update rechaza '.$name.' y rollback completo',function()use($db,$sets,$params){
  $before=snapshot($db);ok(!$sets->updateValueSet(701,$params[2],$params[0],$params[1],[[701,702]]),'aceptó inválido');
  ok(snapshot($db)===$before,'rollback incompleto');
 });
}
scenario($db,'update set inexistente devuelve NOT_FOUND',function()use($sets){
 ok(!$sets->updateValueSet(999999,'QA nombre',701,701,[[701,702]]) && $sets->lastErrorCode()==='NOT_FOUND','actualización set inexistente');
});
scenario($db,'update duplicado SQL rollback ambient transaccion',function()use($db,$sets){
 // Set target with duplicate unique pair already checked preflight.
 $before=snapshot($db);
 ok(!$sets->updateValueSet(701,'QA rollback',701,701,[[701,702],[701,702]]),'aceptó duplicados');
 ok($db->inTransaction(),'rompió transacción externa');
 ok(snapshot($db)===$before,'perdió datos');
});
scenario($db,'crear plantilla desde producto',function()use($db,$sets){
 $res=$sets->createValueSetFromProduct(701,'QA desde producto');
 ok(!empty($res['ok']), 'creación falló '.($res['message']??''));
 ok(countRows($db,'variant_value_set_items','set_id='.(int)$res['set_id'])===2,'creación no copió selección completa');
});
scenario($db,'crear duplicado es rechazado',function()use($sets){
 $res=$sets->createValueSetFromProduct(701,'QA plantilla');
 ok(empty($res['ok']), 'duplicado de nombre creado');
});
scenario($db,'importar plantilla válida a producto coincidente',function()use($db,$sets){
 $res=$sets->importValueSet(701,701);
 ok(!empty($res['ok']),'importación falló '.($res['message']??''));
 ok(countRows($db,'product_attribute_values','product_id=701')===3,'importación no conservó relaciones');
});
scenario($db,'importar plantilla rechaza categoría ajena',function()use($db,$sets){
 $before=snapshot($db);$res=$sets->importValueSet(702,701);
 ok(empty($res['ok']),'aceptó categoría ajena');ok(snapshot($db)===$before,'mutó producto');
});
scenario($db,'importar plantilla inválida no modifica producto',function()use($db,$sets){
 // En transacción privada, insertar un item mal emparejado (permitido por esquema).
 sql($db,'INSERT INTO variant_value_set_items (set_id,attribute_id,value_id) VALUES (702,702,702)');
 $before=snapshot($db);$res=$sets->importValueSet(701,702);
 ok(empty($res['ok']),'importó par cruzado');ok(snapshot($db)===$before,'mutó asociaciones');
});
scenario($db,'eliminar plantilla quita solo items y set',function()use($db,$sets){
 $beforeSelected=$db->query('SELECT * FROM product_attribute_values')->fetchAll();
 ok($sets->deleteValueSet(701),'no borró');
 ok(countRows($db,'variant_value_sets','id=701')===0 && countRows($db,'variant_value_set_items','set_id=701')===0,'quedaron items');
 ok($beforeSelected===$db->query('SELECT * FROM product_attribute_values')->fetchAll(),'se perdieron asociaciones');
});
scenario($db,'eliminar plantilla inexistente no declara éxito',function()use($sets){
 ok(!$sets->deleteValueSet(999999),'declaró borrado inexistente');
});
echo "[MariaDB] END: 24 escenarios en MariaDB real con BEGIN/ROLLBACK; puerto privado ".PORT."\n";
