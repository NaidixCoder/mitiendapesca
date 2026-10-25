<?php
declare(strict_types=1);
// QA-only CLI/localhost web harness; overrides app/.env without modifying it.
if (!in_array(PHP_SAPI, ['cli','cli-server'], true) || getenv('MI_PESCA_QA_MAIN') !== '1') {error_log('QA_GUARD_REFUSED');http_response_code(503);exit(2);}
if (!function_exists('env')) {
 function env(string $key, $default=null) {
  $qa=['DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306','DB_NAME'=>'mi_pesca_qa_local','DB_USER'=>'root','DB_PASS'=>'','APP_ENV'=>'testing','APP_DEBUG'=>'false','APP_URL'=>'http://127.0.0.1:18855','MAIL_TRANSPORT'=>'array','MAIL_DRIVER'=>'log'];
  return array_key_exists($key,$qa) ? $qa[$key] : $default;
 }
}
if (!defined('QA_MAIN_GUARD')) define('QA_MAIN_GUARD', true);
