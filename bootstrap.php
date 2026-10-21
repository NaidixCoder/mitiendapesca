<?php
if (!defined('BASE_PATH')) { define('BASE_PATH', __DIR__); }

// Composer autoload
$autoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) { require_once $autoload; }

// Run boot scripts in order
$bootDir = BASE_PATH . '/config/boot';
if (is_dir($bootDir)) {
    $files = glob($bootDir . '/*.php');
    natsort($files);
    foreach ($files as $f) require_once $f;
}
