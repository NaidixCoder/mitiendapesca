<?php
return [
    'name' => 'Mi Tienda Pesca',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => (bool)(getenv('APP_DEBUG') ?: true),
    'timezone' => 'America/Argentina/Cordoba',
    'locale' => 'es_AR',
];
