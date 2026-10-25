<?php
declare(strict_types=1);
require_once __DIR__.'/qa_mysql_env_guard_main.php';
if (($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' || getenv('MI_PESCA_QA_MAIN') !== '1') {http_response_code(403);exit('QA access denied');}
$root=dirname(__DIR__,2);
$path=(string)(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/');
$asset=$root.'/public_html'.$path;
if ($path!=='/' && is_file($asset) && !str_ends_with(strtolower($asset),'.php'))return false;
if (env('DB_NAME')!=='mi_pesca_qa_local'||env('DB_HOST')!=='127.0.0.1'||env('DB_PORT')!=='3306'){http_response_code(503);exit('QA CONNECTION REFUSED');}
$qaPdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db=(string)$qaPdo->query('SELECT DATABASE()')->fetchColumn();
if($db!=='mi_pesca_qa_local'){http_response_code(503);exit('QA DATABASE GUARD FAILED');}
unset($qaPdo);
if($path==='/__qa_dbcheck_20261025'){header('Content-Type:text/plain;charset=UTF-8');echo 'QA_DATABASE_OK '.$db;exit;}
$_SERVER['SCRIPT_NAME']='/index.php';
require $root.'/public_html/index.php';
