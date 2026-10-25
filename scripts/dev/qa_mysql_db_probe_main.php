<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__,2));
foreach (glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;
$db=db();
echo "QA_DB_PROBE=".($db->query('SELECT DATABASE()')->fetchColumn())."\n";
echo "PRODUCTS=".($db->query('SELECT COUNT(*) FROM products')->fetchColumn())."\n";
