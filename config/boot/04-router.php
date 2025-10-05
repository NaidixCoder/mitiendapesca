<?php
// ---------- Normalización de $uri ----------
$scriptDir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$reqPath   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri       = $reqPath;
if ($scriptDir && str_starts_with($uri, $scriptDir)) $uri = substr($uri, strlen($scriptDir));
$uri = preg_replace('#//+#', '/', str_replace('\\','/',$uri));
$uri = '/' . ltrim($uri, '/');
if (strcasecmp($uri, '/index.php') === 0) $uri = '/';
$uri = preg_replace('#/([A-Za-z0-9\-_]+)\.php$#', '/$1', $uri);
if ($uri !== '/') $uri = rtrim($uri, '/');


