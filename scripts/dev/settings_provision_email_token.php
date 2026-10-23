<?php
/**
 * Aprovisionamiento manual y privado del runner de Email Marketing.
 * Uso: php scripts/dev/settings_provision_email_token.php "C:\\privado\\runner.txt" [--rotate]
 * No imprime ni registra el token. NUNCA ejecutar en QA contra DB comercial.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('BASE_PATH', dirname(__DIR__, 2));
// Carga mínima para CLI: evita inicializar vistas, sesiones o helpers web compartidos.
require BASE_PATH . '/config/boot/01-env.php';
require BASE_PATH . '/config/boot/08-db.php';
require BASE_PATH . '/app/Repositories/Settings/AppSettingsRepository.php';

$destination = (string)($argv[1] ?? '');
$rotation = in_array('--rotate', $argv, true);
if ($destination === '' || !preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $destination)) {
    fwrite(STDERR, "Indicá una ruta absoluta fuera del proyecto para el archivo privado.\n");
    exit(2);
}
$parent = realpath(dirname($destination));
$project = realpath(BASE_PATH);
if ($parent === false || $project === false) {
    fwrite(STDERR, "La carpeta de destino no existe.\n");
    exit(2);
}
$parentCheck = strtolower(str_replace('\\', '/', $parent));
$projectCheck = strtolower(rtrim(str_replace('\\', '/', $project), '/'));
if ($parentCheck === $projectCheck || str_starts_with($parentCheck, $projectCheck . '/')) {
    fwrite(STDERR, "Destino rechazado: el archivo debe permanecer fuera del proyecto y del sitio público.\n");
    exit(2);
}
if (is_link($destination) || file_exists($destination)) {
    fwrite(STDERR, "Destino ya existente: no se sobrescriben archivos.\n");
    exit(2);
}

$db = db();
$settings = new \App\Repositories\Settings\AppSettingsRepository($db);
if (!$rotation && (string)$settings->get('email_marketing_web_runner_token', '') !== '') {
    fwrite(STDERR, "Ya hay una clave. Para rotarla, indicá --rotate y un archivo nuevo.\n");
    exit(2);
}

try {
    $token = bin2hex(random_bytes(32));
} catch (\Throwable $e) {
    fwrite(STDERR, "No se pudo generar una clave criptográfica segura.\n");
    exit(1);
}
$root = rtrim((string)env('APP_URL', ''), '/');
if (!preg_match('~^https?://~', $root)) {
    fwrite(STDERR, "APP_URL debe apuntar a la instalación correcta.\n");
    exit(2);
}
$url = $root . '/email/run?token=' . rawurlencode($token) . "\n";
$handle = false;
$committed = false;
try {
    $oldMask = umask(0077);
    try {
        $handle = fopen($destination, 'x');
    } finally {
        umask($oldMask);
    }
    if ($handle === false) throw new \RuntimeException('No se pudo crear archivo.');
    if (fwrite($handle, $url) !== strlen($url) || !fflush($handle)) {
        throw new \RuntimeException('No se pudo escribir el archivo.');
    }
    fclose($handle);
    $handle = false;
    @chmod($destination, 0600);

    $db->beginTransaction();
    if (!$settings->set('email_marketing_web_runner_token', $token)) {
        throw new \RuntimeException('No se pudo persistir el token.');
    }
    $db->commit();
    $committed = true;
    fwrite(STDOUT, "Clave aprovisionada. La URL quedó exclusivamente en el archivo privado indicado.\n");
} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    if (is_resource($handle)) fclose($handle);
    if (!$committed && is_file($destination)) @unlink($destination);
    fwrite(STDERR, "Falló el aprovisionamiento. No se imprimió el secreto.\n");
    exit(1);
}
