<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$head = (string) file_get_contents($root . '/app/Views/partials/head.php');
if (str_contains($head, 'cdn.tailwindcss.com')) {
    throw new RuntimeException('Tailwind CDN sigue cargado');
}
foreach (['adminStyles','publicStyles'] as $group) {
    if (!preg_match('/\\$' . $group . '\\s*=\\s*\\[(.*?)\\];/s', $head, $match)) {
        throw new RuntimeException('Grupo CSS ausente: ' . $group);
    }
    preg_match_all("/'(css\\/[^']+\\.css)'/", $match[1], $found);
    $cssFiles = $found[1] ?? [];
    if (count($cssFiles) !== count(array_unique($cssFiles))) {
        throw new RuntimeException('Hojas duplicadas: ' . $group);
    }
    foreach ($cssFiles as $cssFile) {
        if (!is_file($root . '/public_html/assets/' . $cssFile)) {
            throw new RuntimeException('Hoja inexistente: ' . $cssFile);
        }
    }
}
if (!str_contains($head, 'css/admin-v3/layout.css')) {
    throw new RuntimeException('Layout admin-v3 no cargado');
}
echo "[css-unificado] OK: sin CDN Tailwind, cargas sin duplicados ni archivos ausentes\n";
