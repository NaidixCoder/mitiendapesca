<?php
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

foreach ([
  'config/boot/00-constants.php',
  'config/boot/01-autoload.php',
  'config/boot/01-env.php',
  'config/boot/02-errors.php',
  'config/boot/03-security_headers.php',
  'config/boot/04-router.php',
  'config/boot/05-paths.php',
  'config/boot/06-url_helpers.php',
  'config/boot/07-session.php',
  'config/boot/08-db.php',
  'config/boot/09-auth.php',
  'config/boot/10-rate_limit.php',
  'config/boot/11-remember_me.php',
  'config/boot/12-csrf.php',
] as $f) require_once BASE_PATH.'/'.$f;

// helpers de vistas DESPUÉS de URL helpers
require_once BASE_PATH.'/app/Views/helpers.php';

// (lo que ya tenías)
require_once BASE_PATH.'/config/boot/13-audit.php';
require_once base_path('app/Support/cache.php');
require_once base_path('app/Support/str.php');
