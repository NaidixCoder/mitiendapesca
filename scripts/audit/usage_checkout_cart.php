<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "[usage_audit] Falta vendor/autoload.php\n");
    exit(1);
}
require_once $autoload;

$routesFile = $root . '/config/routes.php';
if (!is_file($routesFile)) {
    fwrite(STDERR, "[usage_audit] Falta config/routes.php\n");
    exit(1);
}

$routes = require $routesFile;
if (!is_array($routes)) {
    fwrite(STDERR, "[usage_audit] routes.php no devolvio un array\n");
    exit(1);
}

$scopePrefixes = [
    'App\\Controllers\\Cart\\',
    'App\\Controllers\\Checkout\\',
];

$entrypoints = [];
foreach ($routes as $routeKey => $handler) {
    if (!is_string($routeKey) || !is_string($handler)) {
        continue;
    }
    if (strpos($handler, '@') === false) {
        continue;
    }

    [$class, $method] = array_pad(explode('@', $handler, 2), 2, 'index');
    $class = ltrim($class, '\\');
    $isScope = false;
    foreach ($scopePrefixes as $prefix) {
        if (strpos($class, $prefix) === 0) {
            $isScope = true;
            break;
        }
    }
    if (!$isScope) {
        continue;
    }

    [$httpMethod, $path] = array_pad(explode(' ', $routeKey, 2), 2, '');
    $entrypoints[] = [
        'route_key' => $routeKey,
        'http_method' => strtoupper(trim($httpMethod)),
        'path' => trim($path),
        'class' => $class,
        'method' => trim($method),
    ];
}

usort($entrypoints, static function (array $a, array $b): int {
    return strcmp($a['route_key'], $b['route_key']);
});

$errors = [];
echo "== Checkout/Cart Entrypoints ==\n";
foreach ($entrypoints as $row) {
    $class = $row['class'];
    $method = $row['method'];
    $classOk = class_exists($class);
    $methodOk = $classOk && method_exists($class, $method);

    $status = $classOk && $methodOk ? 'OK' : 'MISSING';
    echo sprintf("[%s] %s %s -> %s@%s\n", $status, $row['http_method'], $row['path'], $class, $method);

    if (!$classOk) {
        $errors[] = "Clase no encontrada: {$class}";
    } elseif (!$methodOk) {
        $errors[] = "Metodo no encontrado: {$class}@{$method}";
    }
}

$entryMap = [];
foreach ($entrypoints as $row) {
    $entryMap[$row['class'] . '@' . $row['method']] = true;
}

$classesInScope = [];
foreach ($entrypoints as $row) {
    $classesInScope[$row['class']] = true;
}
ksort($classesInScope);

echo "\n== Public methods fuera de entrypoints (revisar) ==\n";
foreach (array_keys($classesInScope) as $class) {
    if (!class_exists($class)) {
        continue;
    }

    $ref = new ReflectionClass($class);
    $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
    usort($methods, static fn(ReflectionMethod $a, ReflectionMethod $b): int => strcmp($a->getName(), $b->getName()));

    $extra = [];
    foreach ($methods as $m) {
        if ($m->isConstructor() || $m->isDestructor() || $m->isStatic()) {
            continue;
        }
        if ($m->getDeclaringClass()->getName() !== $class) {
            continue;
        }
        $key = $class . '@' . $m->getName();
        if (!isset($entryMap[$key])) {
            $extra[] = $m->getName();
        }
    }

    if ($extra === []) {
        continue;
    }

    echo '- ' . $class . ': ' . implode(', ', $extra) . "\n";
}

if ($errors !== []) {
    echo "\n== Errores ==\n";
    foreach ($errors as $err) {
        echo '- ' . $err . "\n";
    }
    exit(1);
}

echo "\n[usage_audit] OK\n";
