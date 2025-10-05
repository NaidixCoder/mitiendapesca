<?php
header('Content-Type: text/plain; charset=utf-8');
$host = rtrim(base_url('/'), '/');
?>
User-agent: *
Disallow: /login
Disallow: /admin
Disallow: /carrito
Allow: /assets/

Sitemap: <?= $host ?>/sitemap.xml
