<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_pesca_qa_local')throw new RuntimeException('REFUSED');
$key='catalog_hide_inactive_category_products';
$repo=new \App\Repositories\Settings\AppSettingsRepository($db);
$st=$db->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?');$st->execute([$key]);$original=$st->fetchColumn();$missing=$original===false;
$cat=(int)$db->query("SELECT id FROM categories WHERE slug='qa-local-main-categoria'")->fetchColumn();
$originalActive=(int)$db->query("SELECT is_active FROM categories WHERE id=$cat")->fetchColumn();
$url='http://127.0.0.1:18855/producto?slug=qa-local-main-producto';
function check_http(string $url):int {$ctx=stream_context_create(['http'=>['ignore_errors'=>true,'timeout'=>8]]);@file_get_contents($url,false,$ctx);$h=$http_response_header[0]??'';preg_match('/\\s(\\d{3})\\s?/',$h,$m);return (int)($m[1]??0);}
try{
 $db->exec('UPDATE categories SET is_active=0 WHERE id='.$cat);
 if(!$repo->set($key,'1'))throw new RuntimeException('FAIL set hide');
 if($repo->get($key)!=='1')throw new RuntimeException('FAIL persistence');
 echo "PASS SETTINGS_SQL set and read\n";
 $status=check_http($url);if($status!==404)throw new RuntimeException('FAIL PUBLIC consumer hide HTTP '.$status);
 echo "PASS SETTINGS_HTTP hidden category gives 404\n";
 if(!$repo->set($key,'0'))throw new RuntimeException('FAIL set show');
 $status=check_http($url);if($status!==200)throw new RuntimeException('FAIL PUBLIC consumer show HTTP '.$status);
 echo "PASS SETTINGS_HTTP policy OFF restores 200\n";
 echo "QA_SETTINGS_CONSUMER_OK\n";
} finally{
 $db->exec('UPDATE categories SET is_active='.$originalActive.' WHERE id='.$cat);
 if($missing){$del=$db->prepare('DELETE FROM app_settings WHERE setting_key=?');$del->execute([$key]);}
 else $repo->set($key,(string)$original);
}
