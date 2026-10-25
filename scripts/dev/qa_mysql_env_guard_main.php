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
// QA-only mail sink: PHP server MUST have mail disabled at startup.
if (PHP_SAPI === 'cli-server' && function_exists('mail')) {
    http_response_code(503);
    exit('QA REFUSED: native mail() is enabled');
}
if (!function_exists('mail')) {
    function mail($to, $subject, $message, $additional_headers = '', $additional_params = ''): bool {
        if (!str_ends_with(strtolower((string)$to), '@example.invalid')) return false;
        $sink = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mi_pesca_qa_mail_sink_20261025.txt';
        file_put_contents($sink, "TO=".$to."\nSUBJECT=".$subject."\nBODY=".$message."\n--END--\n", FILE_APPEND | LOCK_EX);
        return true;
    }
}
