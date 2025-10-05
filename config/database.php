<?php
return [
    'driver'   => getenv('DB_DRIVER') ?: 'mysql',
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'mitiendapesca',
    'username' => getenv('DB_USER') ?: 'user',
    'password' => getenv('DB_PASS') ?: 'secret',
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_unicode_ci',
];
