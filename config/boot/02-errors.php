<?php
// ---------- Errores ----------
$APP_DEBUG = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
ini_set('display_errors', $APP_DEBUG ? '1' : '0');
error_reporting($APP_DEBUG ? E_ALL : (E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED));

// Registrar SIEMPRE en archivo
@mkdir(BASE_PATH.'/storage/logs', 0775, true);
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH.'/storage/logs/php-error.log');
