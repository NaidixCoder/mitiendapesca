<?php
declare(strict_types=1);
/**
 * Private Checkout HTTP QA router.
 * Serves actual Checkout/Payment controllers with REAL CheckoutOrderService and
 * CheckoutStockPreflightService, but synthetic SQLite repos/auth/CSRF adapter.
 * NEVER a substitute for production bootstrap or real MariaDB E2E.
 */
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(403); exit;
}
$path = (string)getenv('MI_QA_HTTP_SQLITE');
$expected = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mi_checkout_http_qa_20261022'.DIRECTORY_SEPARATOR.'sqlite.db';
$normalize = static fn(string $s): string => strtolower(str_replace('\\','/',$s));
if ($normalize($path) !== $normalize($expected) || !is_dir(dirname($path))) {
    http_response_code(503); exit('REFUSED private SQLite path');
}
session_start();
require __DIR__.'/checkout_http_private_stubs.php';
require dirname(__DIR__,2).'/app/Services/Checkout/CheckoutOrderService.php';
require dirname(__DIR__,2).'/app/Services/Checkout/CheckoutStockPreflightService.php';
require dirname(__DIR__,2).'/app/Controllers/Checkout/CheckoutController.php';
require dirname(__DIR__,2).'/app/Controllers/Checkout/PaymentController.php';

function db(): \PDO {
    static $db=null;
    if ($db === null) {
        $db=new \PDO('sqlite:'.(string)getenv('MI_QA_HTTP_SQLITE'));
        $db->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
    }
    return $db;
}
function auth_required(string $redirectTo): void {
    if ((int)($_SESSION['uid'] ?? 0) !== 7) {
        header('Location: /login', true, 302);exit;
    }
}
function verify_csrf_or_abort(): void {
    $token=(string)($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || !hash_equals((string)($_SESSION['_csrf_token'] ?? ''),$token)) {
        http_response_code(419);exit('csrf_rejected');
    }
}
function json(array $data,int $status=200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;
}
function log_error(string $message): void {}
function flash(string $type,string $message): void {$_SESSION['qa_flash']=$message;}
function cache_forget(string $key): void {}
function redirect(string $to): void {header('Location: '.$to,true,302);exit;}
function url(string $path): string {return $path;}
function base_url(string $path): string {return $path;}

$method=$_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri=(string)(parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH) ?: '/');
if ($uri === '/qa/login' && $method === 'GET') {
    $_SESSION['uid']=7;
    $_SESSION['_csrf_token']=bin2hex(random_bytes(16));
    $_SESSION['checkout_shipping']=['mode'=>'free','cost'=>0,'service_code'=>'FREE'];
    json(['ok'=>true,'csrf'=>$_SESSION['_csrf_token']]);
}
if ($uri === '/qa/logout' && $method === 'GET') {
    unset($_SESSION['uid']);json(['ok'=>true]);
}
if ($uri === '/qa/init' && $method === 'GET') {
    $pdo=db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS products(id INTEGER PRIMARY KEY,iva_rate REAL DEFAULT 0)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS inventory(product_id INTEGER PRIMARY KEY,stock INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS orders(id INTEGER PRIMARY KEY AUTOINCREMENT,checkout_token TEXT UNIQUE,total INTEGER)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS order_items(order_id INTEGER,product_id INTEGER,qty INTEGER)');
    foreach (['order_items','orders','inventory','products'] as $table) $pdo->exec('DELETE FROM '.$table);
    $pdo->exec('INSERT INTO products VALUES(1,0.21)');
    $pdo->exec('INSERT INTO inventory VALUES(1,5)');
    $_SESSION['qa_hidden']=false;
    json(['ok'=>true]);
}
if ($uri === '/qa/state' && $method === 'GET') {
    $pdo=db();
    json(['orders'=>(int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
          'items'=>(int)$pdo->query('SELECT COUNT(*) FROM order_items')->fetchColumn(),
          'stock'=>(int)$pdo->query('SELECT stock FROM inventory WHERE product_id=1')->fetchColumn()]);
}
if ($uri === '/qa/stock' && $method === 'GET') {
    $quantity=(int)($_GET['value'] ?? -1);
    if ($quantity<0 || $quantity>10) json(['error'=>'invalid'],422);
    $st=db()->prepare('UPDATE inventory SET stock=? WHERE product_id=1');
    $st->execute([$quantity]);json(['ok'=>true]);
}
if ($uri === '/qa/hide' && $method === 'GET') {
    $_SESSION['qa_hidden']=(int)($_GET['value'] ?? 0) === 1;
    json(['ok'=>true]);
}
if ($uri === '/checkout/stock/preflight' && $method === 'POST') {
    (new \App\Controllers\Checkout\PaymentController())->stockPreflight();exit;
}
if ($uri === '/checkout/transferencia' && $method === 'POST') {
    (new \App\Controllers\Checkout\CheckoutController())->transferencia();exit;
}
if ($uri === '/checkout/confirmar' && $method === 'POST') {
    (new \App\Controllers\Checkout\CheckoutController())->confirm();exit;
}
http_response_code(404);echo 'No QA route';
