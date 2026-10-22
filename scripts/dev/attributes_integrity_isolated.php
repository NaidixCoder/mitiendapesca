<?php
declare(strict_types=1);
/**
 * PRUEBAS DE INTEGRIDAD AISLADAS EN PDO SQLITE :memory:
 * No carga boot.php, ni .env, ni db(); JAMÁS abre la base comercial.
 * Ejecuta los servicios reales mediante adaptación de sintaxis SHOW TABLES / NOW() solo en memoria.
 * Las funciones guarded* son PROTOTIPOS DE CONTRATO DESEADO, NO código de producción.
 * Uso: C:\xampp\php\php.exe scripts\dev\attributes_integrity_isolated.php
 */
define('BASE_PATH', dirname(__DIR__, 2));
require BASE_PATH . '/vendor/autoload.php';

use App\Services\Admin\Variants\VariantAttributeValueDeleteService;
use App\Services\Admin\Variants\VariantValueSetsService;

final class IsolatedPdo extends PDO {
    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->exec('PRAGMA foreign_keys = ON');
        $this->sqliteCreateFunction('NOW', static fn() => '2026-10-21 00:00:00', 0);
    }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(preg_replace('/\\s+FOR UPDATE\\b/i', '', $query), $options);
    }
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
        if (preg_match("/^SHOW TABLES LIKE '([a-z_]+)'$/i", trim($query), $m)) {
            return parent::query("SELECT name FROM sqlite_master WHERE type='table' AND name=".$this->quote($m[1]));
        }
        return parent::query($query);
    }
}
function fixture(): IsolatedPdo {
    $d = new IsolatedPdo();
    $ddl = [
        'CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT NOT NULL)',
        'CREATE TABLE brands (id INTEGER PRIMARY KEY, name TEXT NOT NULL)',
        'CREATE TABLE attributes (id INTEGER PRIMARY KEY, name TEXT NOT NULL, is_variant_axis INTEGER NOT NULL)',
        'CREATE TABLE attribute_values (id INTEGER PRIMARY KEY, attribute_id INTEGER NOT NULL REFERENCES attributes(id), value TEXT NOT NULL)',
        'CREATE TABLE products (id INTEGER PRIMARY KEY, category_id INTEGER REFERENCES categories(id), brand_id INTEGER REFERENCES brands(id))',
        'CREATE TABLE product_attribute_values (product_id INTEGER REFERENCES products(id), attribute_id INTEGER REFERENCES attributes(id), value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE RESTRICT, UNIQUE(product_id,attribute_id,value_id))',
        'CREATE TABLE product_variant_values (variant_id INTEGER, attribute_id INTEGER REFERENCES attributes(id), value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE RESTRICT, UNIQUE(variant_id,attribute_id,value_id))',
        'CREATE TABLE variant_value_sets (id INTEGER PRIMARY KEY, name TEXT NOT NULL, category_id INTEGER REFERENCES categories(id), brand_id INTEGER REFERENCES brands(id), updated_at TEXT)',
        'CREATE TABLE variant_value_set_items (id INTEGER PRIMARY KEY AUTOINCREMENT, set_id INTEGER NOT NULL REFERENCES variant_value_sets(id), attribute_id INTEGER NOT NULL REFERENCES attributes(id), value_id INTEGER NOT NULL REFERENCES attribute_values(id) ON DELETE RESTRICT, UNIQUE(set_id,attribute_id,value_id))'
    ];
    foreach ($ddl as $sql) $d->exec($sql);
    foreach ([
        "INSERT INTO categories VALUES (1,'Señuelos'),(2,'Cañas')",
        "INSERT INTO brands VALUES (1,'Marca A'),(2,'Marca B')",
        "INSERT INTO attributes VALUES (1,'Color',1),(2,'Medida',1),(3,'Modalidad',0)",
        "INSERT INTO attribute_values VALUES (10,1,'Rojo'),(11,2,'10 cm'),(12,3,'Baitcast'),(13,1,'Azul'),(14,2,'20 cm')",
        "INSERT INTO products VALUES (100,1,1)",
        "INSERT INTO variant_value_sets (id,name,category_id,brand_id) VALUES (5,'Base',1,1),(6,'Otra',1,1)",
        "INSERT INTO variant_value_set_items (set_id,attribute_id,value_id) VALUES (5,1,10)",
    ] as $sql) $d->exec($sql);
    return $d;
}
function must(bool $ok, string $msg): void {
    if (!$ok) throw new RuntimeException($msg);
}
function scalar(PDO $db,string $sql): int {
    return (int)$db->query($sql)->fetchColumn();
}
function state(PDO $db): array {
    $out=[];
    foreach(['attribute_values','product_attribute_values','product_variant_values','variant_value_sets','variant_value_set_items'] as $table) {
        $order=match($table) {
            'attribute_values','variant_value_sets','variant_value_set_items' => 'id',
            'product_attribute_values' => 'product_id, attribute_id, value_id',
            'product_variant_values' => 'variant_id, attribute_id, value_id',
        };
        $out[$table]=$db->query("SELECT * FROM $table ORDER BY $order")->fetchAll(PDO::FETCH_ASSOC);
    }
    return $out;
}
function assertUnchanged(PDO $db,array $before,string $reason):void {
    must(state($db)===$before,"Se perdieron relaciones: ".$reason);
}
/**
 * Candidato backend: bloqueo si existe AL MENOS UNA selección en cualquier relación;
 * nunca hace DELETE de asociaciones de productos para permitir borrar un valor.
 * En MySQL se requerirá SELECT FOR UPDATE en la fila padre y transacción
 * compartida por TODOS los writers, más FK RESTRICT para carreras.
 */
function guardedDelete(PDO $db,int $valueId,int $attributeId): array {
    $db->beginTransaction();
    try {
        $st=$db->prepare('SELECT attribute_id FROM attribute_values WHERE id=?');
        $st->execute([$valueId]);
        $real=$st->fetchColumn();
        if($real===false) { $db->rollBack(); return ['ok'=>false,'code'=>'NOT_FOUND']; }
        if((int)$real!==$attributeId) { $db->rollBack(); return ['ok'=>false,'code'=>'MISMATCH']; }
        foreach(['product_attribute_values','product_variant_values','variant_value_set_items'] as $table) {
            // Fallo de consulta propaga error y desencadena rollback: nunca ignorar silenciosamente.
            $q=$db->prepare("SELECT 1 FROM $table WHERE value_id=? LIMIT 1");
            $q->execute([$valueId]);
            if($q->fetchColumn()!==false) {
                $db->rollBack();
                return ['ok'=>false,'code'=>'VALUE_IN_USE','table'=>$table];
            }
        }
        $q=$db->prepare('DELETE FROM attribute_values WHERE id=? AND attribute_id=?');
        $q->execute([$valueId,$attributeId]);
        must($q->rowCount()===1,'No se borró exactamente un valor libre');
        $db->commit();
        return ['ok'=>true,'code'=>'DELETED'];
    } catch(Throwable $e) {
        if($db->inTransaction()) $db->rollBack();
        return ['ok'=>false,'code'=>'ERROR','detail'=>$e->getMessage()];
    }
}
/** Candidato al contrato: validaciones transaccionales antes de DELETE+INSERT. */
function validateSet(PDO $db,int $id,string $name,int $cid,int $bid,array $pairs):void {
    if ($id<=0 || trim($name)==='' || mb_strlen($name)>120 || $cid<=0 || $bid<=0 || count($pairs)<1) {
        throw new DomainException('INVALID_INPUT');
    }
    $st=$db->prepare('SELECT id FROM variant_value_sets WHERE id=?');
    $st->execute([$id]);
    if($st->fetchColumn()===false) throw new DomainException('SET_NOT_FOUND');
    foreach(['categories'=>$cid,'brands'=>$bid] as $table=>$key){
        $st=$db->prepare("SELECT id FROM $table WHERE id=?");
        $st->execute([$key]);
        if($st->fetchColumn()===false) throw new DomainException(strtoupper($table).'_NOT_FOUND');
    }
    $st=$db->prepare('SELECT id FROM variant_value_sets WHERE category_id=? AND brand_id=? AND name=? AND id<>?');
    $st->execute([$cid,$bid,$name,$id]);
    if($st->fetchColumn()!==false) throw new DomainException('DUPLICATE_SET');
    $unique=[];
    $st=$db->prepare('SELECT v.id FROM attribute_values v JOIN attributes a ON a.id=v.attribute_id WHERE a.id=? AND v.id=? AND a.is_variant_axis=1');
    foreach($pairs as $pair) {
        if(!is_array($pair) || count($pair)!==2 || !is_int($pair[0]) || !is_int($pair[1]) || $pair[0]<=0 || $pair[1]<=0) throw new DomainException('INVALID_PAIR');
        $key=$pair[0].':'.$pair[1];
        if(isset($unique[$key])) throw new DomainException('DUPLICATE_PAIR');
        $unique[$key]=true;
        $st->execute($pair);
        if($st->fetchColumn()===false) throw new DomainException('PAIR_NOT_ALLOWED');
    }
}
function guardedSet(PDO $db,int $id,string $name,int $cid,int $bid,array $pairs): array {
    $db->beginTransaction();
    try {
        validateSet($db,$id,$name,$cid,$bid,$pairs);
        $db->prepare('UPDATE variant_value_sets SET name=?, category_id=?, brand_id=? WHERE id=?')->execute([$name,$cid,$bid,$id]);
        $db->prepare('DELETE FROM variant_value_set_items WHERE set_id=?')->execute([$id]);
        $insert=$db->prepare('INSERT INTO variant_value_set_items (set_id,attribute_id,value_id) VALUES (?,?,?)');
        foreach($pairs as [$aid,$vid]) $insert->execute([$id,$aid,$vid]);
        $db->commit();
        return ['ok'=>true];
    } catch(Throwable $e) {
        if($db->inTransaction()) $db->rollBack();
        return ['ok'=>false,'code'=>$e instanceof DomainException?$e->getMessage():'STORAGE_ERROR'];
    }
}
$passed=0;
function scenario(string $title,callable $f):void {
    global $passed;
    $f(); $passed++;
    echo "[aislada] OK {$passed}: {$title}\n";
}
scenario('GUARD REAL bloquea selección product-only y conserva asociaciones',function(){
    $d=fixture();
    $d->exec('INSERT INTO product_attribute_values VALUES (100,2,11)');
    $r=(new VariantAttributeValueDeleteService($d))->deleteAttributeValue(11,2);
    must(!$r['ok'] && $r['code']==='VALUE_IN_USE'
        && scalar($d,'SELECT COUNT(*) FROM product_attribute_values WHERE value_id=11')===1
        && scalar($d,'SELECT COUNT(*) FROM attribute_values WHERE id=11')===1,'Guard no bloqueó selección');
});
foreach([
    'producto'=>["INSERT INTO product_attribute_values VALUES (100,2,11)",11,2],
    'variante'=>["INSERT INTO product_variant_values VALUES (900,2,11)",11,2],
    'plantilla'=>["INSERT INTO variant_value_set_items (set_id,attribute_id,value_id) VALUES (6,2,11)",11,2],
] as $kind=>[$sql,$vid,$aid]) {
    scenario("GUARD propuesto bloquea valor usado por {$kind}; 0 asociaciones perdidas",function() use ($sql,$vid,$aid){
        $d=fixture();$d->exec($sql);$before=state($d);
        $r=guardedDelete($d,$vid,$aid);
        must(!$r['ok'] && $r['code']==='VALUE_IN_USE','No bloqueó');
        assertUnchanged($d,$before,'guardedDelete '.$vid);
    });
}
scenario('GUARD propuesto permite borrar solamente valor libre',function(){
    $d=fixture();$old=scalar($d,'SELECT COUNT(*) FROM attribute_values');
    $r=guardedDelete($d,13,1);
    must($r['ok'] && scalar($d,'SELECT COUNT(*) FROM attribute_values')===$old-1,'No eliminó el valor libre');
    must(scalar($d,'SELECT COUNT(*) FROM variant_value_set_items')===1,'Alteró sets');
});
scenario('Prune actual NO borra valor con asociación de producto',function(){
    $d=fixture();$d->exec('INSERT INTO product_attribute_values VALUES (100,2,11)');
    $old=state($d);
    $r=(new VariantAttributeValueDeleteService($d))->pruneAttributeValueIfUnused(11);
    must(!$r,'Prune eliminó valor asociado');assertUnchanged($d,$old,'prune con uso');
});
scenario('GUARD REAL rechaza pareja atributo/valor cruzada',function(){
    $d=fixture();$s=new VariantValueSetsService($d);
    $before=state($d);
    must(!$s->updateValueSet(5,'Base',1,1,[[2,10]]),'Guard aceptó par cruzado');
    assertUnchanged($d,$before,'par cruzado');
});
scenario('GUARD REAL rechaza atributo NO eje',function(){
    $d=fixture();$s=new VariantValueSetsService($d);
    $before=state($d);
    must(!$s->updateValueSet(5,'Base',1,1,[[3,12]]),'Guard aceptó no eje');
    assertUnchanged($d,$before,'atributo no eje');
});
scenario('Rollback del servicio actual ante UNIQUE duplicado mantiene sets',function(){
    $d=fixture();$before=state($d);$s=new VariantValueSetsService($d);
    must(!$s->updateValueSet(5,'Cambio',1,1,[[1,10],[1,10]]),'Debe fallar UNIQUE');
    assertUnchanged($d,$before,'rollback ante duplicado');
});
foreach([
  'PAR CRUZADO' => [5,'Base',1,1,[[2,10]]],
  'NO EJE' => [5,'Base',1,1,[[3,12]]],
  'CATEGORIA INEXISTENTE' => [5,'Base',99,1,[[1,10]]],
  'MARCA INEXISTENTE' => [5,'Base',1,99,[[1,10]]],
  'PLANTILLA INEXISTENTE' => [999,'Base',1,1,[[1,10]]],
  'PARES DUPLICADOS' => [5,'Base',1,1,[[1,10],[1,10]]],
  'PLANTILLA DUPLICADA' => [5,'Otra',1,1,[[1,10]]],
  'SET SIN ITEMS' => [5,'Base',1,1,[]],
] as $title=>$args) {
    scenario("GUARD set rechaza {$title}, snapshot inmutable",function()use($args){
        $d=fixture();$before=state($d);
        $r=guardedSet($d,...$args);
        must(!$r['ok'],'Debería rechazar contrato');
        assertUnchanged($d,$before,'set inválido');
    });
}
scenario('GUARD set permite actualización válida sin alterar selecciones ajenas',function(){
    $d=fixture();$d->exec('INSERT INTO product_attribute_values VALUES (100,2,11)');
    $oldProducts=$d->query('SELECT * FROM product_attribute_values')->fetchAll();
    $r=guardedSet($d,5,'Base mejorada',2,2,[[1,10],[2,11]]);
    must($r['ok'],'Debe permitir combinación válida');
    must(scalar($d,'SELECT COUNT(*) FROM variant_value_set_items WHERE set_id=5')===2,'Items incorrectos');
    must($oldProducts===$d->query('SELECT * FROM product_attribute_values')->fetchAll(),'Selecciones cambiaron');
});
echo "[aislada] TOTAL {$passed} escenarios SQLite memory; servicios reales con guard P0.\n";
