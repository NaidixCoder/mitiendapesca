<?php
declare(strict_types=1);
/** Tags QA-only router: Windows PHP dirname('/index.php') yields '\\'.
 * Simulate a normal hosted subfolder, without modifying production helpers. */
$raw=(string)($_SERVER['REQUEST_URI'] ?? '/');
$p=(string)(parse_url($raw, PHP_URL_PATH) ?: '/');
if (str_starts_with($p, '/qa/assets/')) {
    $root=realpath(__DIR__.'/site/public_html/assets');
    $file=realpath(__DIR__.'/site/public_html'.substr($p,3));
    if (!$root || !$file || !str_starts_with(strtolower($file), strtolower($root.DIRECTORY_SEPARATOR)) || !is_file($file)) {
        http_response_code(404);exit;
    }
    $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
    $types=['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','webp'=>'image/webp','woff2'=>'font/woff2','gif'=>'image/gif','ico'=>'image/x-icon','json'=>'application/json'];
    header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
    readfile($file);exit;
}
$physical=__DIR__.'/site/public_html'.$p;
if ($p!=='/' && is_file($physical)) { return false; }
if ($p==='/qa') {$p='/';}
elseif (str_starts_with($p,'/qa/')) {$p=substr($p,3);}
$query=parse_url($raw,PHP_URL_QUERY);
$_SERVER['REQUEST_URI']=$p.($query!==null?'?'.$query:'');
$_SERVER['SCRIPT_NAME']='/qa/index.php';
require __DIR__.'/site/public_html/index.php';
