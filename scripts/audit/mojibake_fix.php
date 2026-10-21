<?php
declare(strict_types=1);

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "[mojibake] No se pudo resolver root\n");
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
$skipDirs = ['vendor', 'node_modules', '.git', 'storage/logs', 'storage/cache'];

$write = in_array('--write', $argv, true);
$quiet = in_array('--quiet', $argv, true);

// Reemplazos frecuentes en español (UTF-8 mal decodificado).
$replaceMap = [
    'á' => 'á',
    'é' => 'é',
    'í' => 'í',
    'ó' => 'ó',
    'ú' => 'ú',
    'Á' => 'Á',
    'É' => 'É',
    'Í' => 'Í',
    'Ó' => 'Ó',
    'Ú' => 'Ú',
    'ñ' => 'ñ',
    'Ñ' => 'Ñ',
    'ü' => 'ü',
    'Ü' => 'Ü',
    '¿' => '¿',
    '¡' => '¡',
    '°' => '°',
    '“' => '“',
    '”' => '”',
    '‘' => '‘',
    '’' => '’',
    '–' => '–',
    '—' => '—',
    '•' => '•',
    '←' => '←',
    '→' => '→',
    '↓' => '↓',
    '↑' => '↑',
    '·' => '·',
    '' => '',
];

$hasNeedle = static function (string $text, array $map): bool {
    foreach ($map as $bad => $_good) {
        if (strpos($text, $bad) !== false) {
            return true;
        }
    }
    return false;
};

$shouldSkip = static function (string $path) use ($skipDirs): bool {
    $norm = str_replace('\\', '/', $path);
    foreach ($skipDirs as $skip) {
        if (strpos($norm, '/' . trim($skip, '/') . '/') !== false) {
            return true;
        }
    }
    return false;
};

$rel = static function (string $path) use ($root): string {
    $p = str_replace('\\', '/', $path);
    $r = str_replace('\\', '/', $root);
    return ltrim(str_replace($r, '', $p), '/');
};

$hits = [];
$changed = [];

foreach ($scanRoots as $scanRoot) {
    if (!is_dir($scanRoot)) {
        continue;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($scanRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($it as $fileInfo) {
        /** @var SplFileInfo $fileInfo */
        if (!$fileInfo->isFile()) {
            continue;
        }
        $path = $fileInfo->getPathname();
        if ($shouldSkip($path)) {
            continue;
        }
        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            continue;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            continue;
        }

        if (!$hasNeedle($raw, $replaceMap)) {
            continue;
        }

        $fixed = strtr($raw, $replaceMap);
        if ($fixed === $raw) {
            continue;
        }

        $hits[] = $path;
        if ($write) {
            $ok = @file_put_contents($path, $fixed);
            if ($ok !== false) {
                $changed[] = $path;
            }
        }
    }
}

if (!$quiet) {
    echo "[mojibake] candidatos: " . count($hits) . PHP_EOL;
    foreach ($hits as $p) {
        echo "  - " . $rel($p) . PHP_EOL;
    }
    if ($write) {
        echo "[mojibake] corregidos: " . count($changed) . PHP_EOL;
    } else {
        echo "[mojibake] modo dry-run (sin cambios). Usa --write para aplicar." . PHP_EOL;
    }
}

exit(0);

