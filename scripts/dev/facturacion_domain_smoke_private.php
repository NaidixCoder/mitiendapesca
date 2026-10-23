<?php
declare(strict_types=1);
require __DIR__ . '/../../vendor/autoload.php';
use App\Services\Billing\PurchaseInvoiceService;
use App\Services\Accounting\MonthlyClosureService;

function assert_case(bool $ok, string $label): void {
    if (!$ok) { fwrite(STDERR, "FAIL: {$label}\n"); exit(1); }
    echo "OK: {$label}\n";
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE purchase_invoices (id INTEGER PRIMARY KEY, supplier_id INTEGER, cbte_tipo INTEGER, pto_vta INTEGER, cbte_nro INTEGER, status TEXT)');
$svc = new PurchaseInvoiceService($db);
$base = ['supplier_id'=>0,'cbte_tipo'=>11,'pto_vta'=>1,'cbte_nro'=>5,'imp_neto'=>100,'imp_iva'=>0,'imp_total'=>100];
foreach (['2026-02-30','2026-13-05','2026-00-10','2026-10-10junk'] as $bad) {
    $r=$svc->save($base+['issued_at'=>$bad]);
    assert_case(isset($r['errors']['issued_at']), 'fecha imposible: '.$bad);
}
$r=$svc->save($base+['issued_at'=>'2026-02-28']);
assert_case(!isset($r['errors']['issued_at']), 'fecha válida aceptada');
$close=new MonthlyClosureService($db);
foreach (['2026-00','2026-13','2026-99','2026-01x'] as $bad) {
    try { $close->closePeriod($bad,1); assert_case(false,'mes inválido '.$bad); }
    catch (RuntimeException $e) { assert_case(str_contains($e->getMessage(),'inválido'), 'mes inválido rechazado: '.$bad); }
}
try { $close->closePeriod('2026-10',0); assert_case(false,'usuario cero'); }
catch (RuntimeException $e) { assert_case(str_contains($e->getMessage(),'Usuario'), 'usuario cero rechazado'); }

$db->sqliteCreateFunction('NOW', static fn(): string => '2026-10-23 14:00:00');
foreach (['kind TEXT','issued_at TEXT','imp_neto INTEGER','imp_iva INTEGER','perc_iva INTEGER','perc_iibb INTEGER','ret_ganancias INTEGER','imp_total INTEGER','notes TEXT','updated_at TEXT'] as $column) {
    $db->exec('ALTER TABLE purchase_invoices ADD COLUMN ' . $column);
}
$db->exec('CREATE TABLE suppliers (id INTEGER PRIMARY KEY, nombre TEXT, cuit TEXT)');
$db->exec("INSERT INTO suppliers VALUES (1,'Prueba','20111111112')");
$db->exec("INSERT INTO purchase_invoices (id,supplier_id,cbte_tipo,pto_vta,cbte_nro,status) VALUES (9,1,11,1,5,'void')");
$repository = new App\Repositories\Billing\PurchaseInvoiceRepository($db);
$full = ['supplier_id'=>1,'cbte_tipo'=>11,'pto_vta'=>1,'cbte_nro'=>5,'issued_at'=>'2026-02-28','imp_neto'=>100,'imp_iva'=>0,'imp_total'=>100];
$blocked = $svc->save($full,9);
assert_case(!($blocked['ok']??true) && isset($blocked['errors']['id']), 'no revive compra anulada');
$repository->update(9,['supplier_id'=>1,'kind'=>'invoice','cbte_tipo'=>11,'pto_vta'=>1,'cbte_nro'=>5,'issued_at'=>'2026-02-28','imp_neto'=>100,'imp_iva'=>0,'perc_iva'=>0,'perc_iibb'=>0,'ret_ganancias'=>0,'imp_total'=>100,'status'=>'loaded','notes'=>'']);
assert_case($db->query('SELECT status FROM purchase_invoices WHERE id=9')->fetchColumn()==='void','UPDATE SQL no modifica anulada');

$db->exec('ALTER TABLE purchase_invoices ADD COLUMN voided_at TEXT');
$db->exec("INSERT INTO purchase_invoices (id,supplier_id,cbte_tipo,pto_vta,cbte_nro,status) VALUES (10,1,11,1,6,'loaded')");
assert_case(!$svc->validate(9), 'anulada no vuelve a validada');
assert_case($svc->validate(10), 'cargada pasa a validada');
assert_case(!$svc->validate(10), 'validación repetida no muta');
assert_case($svc->void(10), 'validada puede anularse');
assert_case(!$svc->void(10), 'anulación repetida no muta');
assert_case((string)$db->query('SELECT status FROM purchase_invoices WHERE id=10')->fetchColumn()==='void','estado final persisted void');

$closuresDb = new PDO('sqlite::memory:');
$closuresDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$closuresDb->exec('CREATE TABLE accounting_closures (id INTEGER PRIMARY KEY, period TEXT NOT NULL UNIQUE, iva_ventas INTEGER, iva_compras INTEGER, saldo_iva INTEGER, ventas_json TEXT, compras_json TEXT, closed_by_user_id INTEGER, closed_at TEXT, created_at TEXT)');
$closureRepo = new App\Repositories\Accounting\AccountingClosureRepository($closuresDb);
$closureData=['period'=>'2026-10','iva_ventas'=>500,'iva_compras'=>300,'saldo_iva'=>200,'ventas_json'=>'{}','compras_json'=>'{}','closed_by_user_id'=>1,'closed_at'=>'2026-10-23 15:00:00','created_at'=>'2026-10-23 15:00:00'];
assert_case($closureRepo->create($closureData)>0,'snapshot de cierre persistido');
assert_case((int)$closureRepo->findByPeriod('2026-10')['saldo_iva']===200,'snapshot conserva saldo');
try { $closureRepo->create($closureData); assert_case(false,'duplicado cierre'); }
catch (PDOException $e) { assert_case($e->getCode()==='23000', 'UNIQUE rechaza duplicado de cierre'); }

use App\Services\Billing\FiscalExportGuard;
foreach ([['2026-02-29','2026-03-01',false],['2026-03-01','2026-02-28',false],['2026-10-01','2026-10-23',true]] as [$from,$to,$valid]) {
    try { FiscalExportGuard::requireDateRange($from,$to); assert_case($valid, 'rango ' . $from . '/' . $to); }
    catch (InvalidArgumentException $e) { assert_case(!$valid,'rango rechazado ' . $from . '/' . $to); }
}
foreach (['=1+1',' +HYPERLINK(1)','-5+1','@SUM(A1)',"\t=CMD"] as $unsafe) {
    assert_case(str_starts_with(FiscalExportGuard::safeCsvText($unsafe), "'"), 'texto CSV neutralizado');
}
assert_case(FiscalExportGuard::safeCsvText('Proveedor seguro')==='Proveedor seguro','CSV benigno intacto');
assert_case(FiscalExportGuard::safeFilenamePart("prod_\r\n.csv")==='prod_csv','nombre export saneado');

$negative = $svc->save(array_replace($base,['issued_at'=>'2026-02-28','imp_total'=>-100]));
assert_case(isset($negative['errors']['imp_total']), 'total negativo no se autocorrige');
$zero = $svc->save(array_replace($base,['issued_at'=>'2026-02-28','imp_neto'=>0,'imp_total'=>0]));
assert_case(isset($zero['errors']['imp_total']), 'total contable cero rechazado');

assert_case(FiscalExportGuard::safeCsvText('-150.50')==='-150.50','importe negativo numérico preservado');
assert_case(FiscalExportGuard::safeCsvText('-5+1')!=='-5+1','expresión con guion neutralizada');
$csvStream = fopen('php://temp','w+');
FiscalExportGuard::writeCsv($csvStream,['Proveedor seguro','=SUM(A1)',-125,'-125.50']);
rewind($csvStream);
$csvRow = fgetcsv($csvStream);
fclose($csvStream);
assert_case($csvRow[1]==="'=SUM(A1)" && $csvRow[2]==='-125' && $csvRow[3]==='-125.50','CSV real conserva importes y neutraliza fórmula');
$reportController = file_get_contents(__DIR__.'/../../app/Controllers/Admin/ReportesFiscalesController.php');
assert_case(str_contains($reportController,"['duplicates_exact']") && str_contains($reportController,"['non_billable_with_invoice']"),'contratos exportación conciliación alineados (estático)');

$db->exec("INSERT INTO purchase_invoices (id,supplier_id,kind,cbte_tipo,pto_vta,cbte_nro,status,issued_at,imp_neto,imp_iva,imp_total) VALUES (11,1,'invoice',11,1,7,'loaded','2026-10-20',100,0,100),(12,1,'invoice',11,1,8,'loaded','2026-10-21',150,0,150),(13,1,'invoice',11,1,9,'loaded','2026-10-22',170,0,170)");
$total = $repository->count(['status'=>'loaded']);
assert_case($total === 3, 'paginación count filtrado');
$first = $repository->list(['status'=>'loaded','page'=>1,'per_page'=>2]);
assert_case(count($first)===2, 'paginación limita registros');
$second = $repository->list(['status'=>'loaded','page'=>2,'per_page'=>2]);
assert_case(count($second)===1, 'paginación continúa en siguiente página');
