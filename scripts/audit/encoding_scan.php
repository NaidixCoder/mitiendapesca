<?php
declare(strict_types=1);

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "[encoding] No se pudo resolver root\n");
    exit(1);
}

$scanRoots = [
    $root . DIRECTORY_SEPARATOR . 'app',
    $root . DIRECTORY_SEPARATOR . 'config',
    $root . DIRECTORY_SEPARATOR . 'public_html',
    $root . DIRECTORY_SEPARATOR . 'scripts',
    $root . DIRECTORY_SEPARATOR . 'docs',
];

$allowedExt = ['php', 'js', 'css', 'md', 'html', 'txt', 'json', 'yml', 'yaml', 'xml', 'sql'];
$skipDirs = ['vendor', 'node_modules', '.git', 'storage\\logs', 'storage/logs', 'storage\\cache', 'storage/cache'];

$failOnBom = in_array('--fail-on-bom', $argv, true);
$quiet = in_array('--quiet', $argv, true);
$bomOnly = in_array('--bom-only', $argv, true);

$mojibakePatterns = [
    '/Ã./u',
    '/./u',
    '/â€|’|“|”/u',
    '/ï¿½/u',
    '/�/u',
];

$bomHits = [];
$mojiHits = [];

$shouldSkip = static function (string $path) use ($skipDirs): bool {
    $norm = str_replace('/', DIRECTORY_SEPARATOR, $path);
    foreach ($skipDirs as $skip) {
        $skipNorm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $skip);
        if (stripos($norm, DIRECTORY_SEPARATOR . $skipNorm . DIRECTORY_SEPARATOR) !== false) {
            return true;
        }
    }
    return false;
};

foreach ($scanRoots as $scanRoot) {
    if (!is_dir($scanRoot)) {
        continue;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        $path = $fileInfo->getPathname();
        if ($shouldSkip($path)) {
            continue;
        }
        if (!$fileInfo->isFile()) {
            continue;
        }
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            continue;
        }

        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            continue;
        }

        if (strlen($bytes) >= 3 && substr($bytes, 0, 3) === "\xEF\xBB\xBF") {
            $bomHits[] = $path;
        }

        if ($bomOnly) {
            continue;
        }

        foreach ($mojibakePatterns as $rx) {
            if (@preg_match($rx, $bytes) === 1) {
                $mojiHits[] = $path;
                break;
            }
        }
    }
}

$rel = static function (string $path) use ($root): string {
    $p = str_replace('\\', '/', $path);
    $r = str_replace('\\', '/', $root);
    return ltrim(str_replace($r, '', $p), '/');
};

if (!$quiet) {
    echo "[encoding] BOM encontrados: " . count($bomHits) . PHP_EOL;
    foreach ($bomHits as $p) {
        echo "  - " . $rel($p) . PHP_EOL;
    }
    if (!$bomOnly) {
        echo "[encoding] Posible mojibake: " . count($mojiHits) . PHP_EOL;
        foreach ($mojiHits as $p) {
            echo "  - " . $rel($p) . PHP_EOL;
        }
    }
}

if ($failOnBom && count($bomHits) > 0) {
    fwrite(STDERR, "[encoding] ERROR: hay archivos con BOM.\n");
    exit(2);
}

exit(0);

