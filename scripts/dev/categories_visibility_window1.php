<?php
declare(strict_types=1);

/**
 * Contrato de estado Categorías - VENTANA 1.
 * Crea y destruye SU PROPIA BD de prueba; jamás escribe en DB_NAME habitual.
 * No requiere navegador ni servidor web.
 * Ejecutar: php scripts/dev/categories_visibility_window1.php
 */
$projectRoot = dirname(__DIR__, 2);
require $projectRoot.'/vendor/autoload.php';

function check(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}

function credentials(string $projectRoot): array
{
    // .env contiene otras variables con caracteres que no son sintaxis INI.
    // Leer únicamente las claves DB y no imprimir ni almacenar credenciales.
    $lines = file($projectRoot.'/.env', FILE_IGNORE_NEW_LINES) ?: [];
    $env = [];
    foreach ($lines as $line) {
        if (!preg_match('/^\\s*(DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS)\\s*=(.*)$/', $line, $m)) continue;
        $value = trim($m[2]);
        if (strlen($value) >= 2) {
            $first = $value[0];
            if (($first === '"' || $first === "'") && substr($value, -1) === $first) {
                $value = substr($value, 1, -1);
            }
        }
        $env[$m[1]] = $value;
    }
    foreach (['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'] as $key) {
        if (!array_key_exists($key, $env)) throw new RuntimeException('Falta '.$key);
    }
    return $env;
}

function connection(array $env, ?string $database = null): PDO
{
    $host = (string)$env['DB_HOST'];
    $port = (int)$env['DB_PORT'];
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    if ($database !== null) $dsn .= ';dbname='.$database;
    return new PDO($dsn, (string)$env['DB_USER'], (string)$env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 8,
    ]);
}

function states(PDO $db): array
{
    $result = [];
    foreach ($db->query('SELECT id,is_active FROM categories ORDER BY id')->fetchAll() as $r) {
        $result[(int)$r['id']] = (int)$r['is_active'];
    }
    return $result;
}

function fresh(PDO $db): void
{
    $db->exec('UPDATE categories SET is_active = CASE WHEN id IN (5,6,9) THEN 0 ELSE 1 END');
    $db->exec('UPDATE categories SET parent_id = 7 WHERE id=9');
}

function ensureStates(PDO $db, array $expected, string $label): void
{
    $all = states($db);
    foreach ($expected as $id => $value) {
        check(($all[$id] ?? -1) === $value, $label.': id '.$id.' esperado='.$value.' actual='.($all[$id] ?? 'ausente'));
    }
}

function report(string $label): void { echo '[CATS_W1] PASS '.$label.PHP_EOL; }

// Worker: solo acepta identificador de BD con prefijo y no crea/elimina tablas.
if (($argv[1] ?? '') === '--hold' || ($argv[1] ?? '') === '--activate') {
    $database = (string)($argv[2] ?? '');
    check((bool)preg_match('/^mi_pesca_cat_w1_[a-f0-9]{8}$/', $database), 'BD worker no permitida');
    $env = credentials($projectRoot);
    check($database !== $env['DB_NAME'], 'Worker prohibido en base habitual');
    $db = connection($env, $database);
    check((string)$db->query('SELECT DATABASE()')->fetchColumn() === $database, 'DB equivocada');
    $service = new App\Services\Admin\Categories\CategoryVisibilityPolicyService($db);
    if ($argv[1] === '--hold') {
        $marker = (string)($argv[3] ?? '');
        check(str_contains($marker, 'categories_phase3_window1_20261021'), 'Marcador fuera del backup');
        $db->beginTransaction();
        try {
            $result = $service->setActive(1, 0);
            check((bool)($result['ok'] ?? false), 'Worker hold falló');
            file_put_contents($marker, 'locked');
            usleep(1700000);
            $db->commit();
            report('worker-lock-and-commit');
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    } else {
        $started = microtime(true);
        $result = $service->setActive(1, 1);
        check((bool)($result['ok'] ?? false), 'Worker activate falló: '.json_encode($result));
        $elapsed = microtime(true)-$started;
        echo '[CATS_W1] worker-activate-seconds='.round($elapsed,3).PHP_EOL;
        check($elapsed > 0.65, 'Worker no esperó lock (test concurrente inválido)');
    }
    exit(0);
}

$env = credentials($projectRoot);
$sourceName = (string)$env['DB_NAME'];
$testDb = 'mi_pesca_cat_w1_'.bin2hex(random_bytes(4));
check($testDb !== $sourceName, 'BD aislada coincide con habitual');
$admin = connection($env);
$created = false;

try {
    $stmt = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
    $stmt->execute([$testDb]);
    check((int)$stmt->fetchColumn() === 0, 'BD temporal ya existe');
    $admin->exec("CREATE DATABASE `{$testDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $db = connection($env, $testDb);
    check((string)$db->query('SELECT DATABASE()')->fetchColumn() === $testDb, 'Sin aislamiento');
    check($sourceName !== $testDb, 'Sin aislamiento fuente');

    $db->exec('CREATE TABLE categories (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        parent_id BIGINT UNSIGNED NULL,
        name VARCHAR(120) NOT NULL,
        slug VARCHAR(140) NOT NULL UNIQUE,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        KEY idx_cat_parent(parent_id),
        CONSTRAINT fk_cat_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB');
    $db->exec('CREATE TABLE products (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id BIGINT UNSIGNED NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        CONSTRAINT fk_prod_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB');
    $rows = [
        [1,null,1],[2,1,1],[3,2,1],[4,1,1],
        [5,null,0],[6,5,0],[7,null,1],[8,7,1],
        [9,7,0],[10,9,1],
    ];
    $ins = $db->prepare('INSERT INTO categories(id,parent_id,name,slug,is_active) VALUES (?,?,?,?,?)');
    foreach ($rows as [$id,$parent,$active]) {
        $ins->execute([$id,$parent,'Fixture '.$id,'fixture-'.$id,$active]);
    }
    $db->exec('INSERT INTO products(id,category_id,is_active) VALUES(101,1,1),(102,2,1),(103,3,1)');
    $productsBefore = $db->query('SELECT id,category_id,is_active FROM products ORDER BY id')->fetchAll();
    report('isolated-db-created: '.$testDb);

    $svc = new App\Services\Admin\Categories\CategoryVisibilityPolicyService($db);
    $facade = new App\Services\Catalog\CategoryAdminService($db);
    $guard = new App\Services\Admin\Categories\CategoryHierarchyGuard($db);

    // V01: explicit + all generations
    $res = $svc->setActive(1,0);
    check(($res['ok'] ?? false) === true && ($res['changed'] ?? false) === true, 'V01 status');
    check(($res['children_count'] ?? -1) === 3 && ($res['products_count'] ?? -1) === 3, 'V01 counts');
    check($res['cascade_ids'] === [1,2,3,4], 'V01 IDs');
    ensureStates($db, [1=>0,2=>0,3=>0,4=>0], 'V01');
    check($db->query('SELECT id,category_id,is_active FROM products ORDER BY id')->fetchAll() === $productsBefore, 'Productos alterados');
    report('V01 explicit-cascade-products-unchanged');

    // V04: request 0->0 is idempotent, historical children are not sanitized.
    $db->exec('UPDATE categories SET is_active=1 WHERE id=2');
    $res = $svc->setActive(1,0);
    check(($res['ok'] ?? false) === true && ($res['changed'] ?? true) === false, 'V04 not idempotent');
    ensureStates($db, [1=>0,2=>1,3=>0], 'V04');
    report('V04 no-cascade-on-repeated-state');
    fresh($db);

    // V02/V03: legacy toggle, activation of root only.
    $res = $facade->toggleActiveCascade(1);
    check(($res['from'] ?? -1) === 1 && ($res['to'] ?? -1) === 0, 'V02 legacy response');
    ensureStates($db, [1=>0,2=>0,3=>0,4=>0], 'V02');
    $res = $facade->toggleActiveCascade(1);
    check(($res['from'] ?? -1) === 0 && ($res['to'] ?? -1) === 1, 'V03 legacy response');
    ensureStates($db, [1=>1,2=>0,3=>0,4=>0], 'V03');
    report('V02-V03 legacy-toggle-cascade-reactivation');
    fresh($db);

    // V05: inactive ancestors; no write for rejected activation.
    $svc->setActive(1,0);
    $before = states($db);
    $bad = $svc->setActive(2,1);
    check(($bad['ok'] ?? true) === false && ($bad['code'] ?? '') === 'inactive_ancestor', 'V05 ancestor rejection');
    check(states($db) === $before, 'V05 mutated rows');
    report('V05 inactive-ancestor-rejected');
    fresh($db);

    // V06-V08: hierarchy validations without persistence.
    check($guard->validateParent(1,3) !== null, 'V08 cycle accepted');
    check($guard->validateParent(2,2) !== null, 'V08 self accepted');
    check($guard->validateParent(2,999999) !== null, 'V08 missing accepted');
    check($guard->validatePlacement(11,5,1) !== null, 'V06 inactive parent accepted');
    check($guard->validatePlacement(11,5,0) === null, 'V06 inactive node under inactive rejected');
    // Fixture 9 is inactive but has active descendant 10. Cannot move it under 5.
    check($guard->validatePlacement(9,5,0) !== null, 'V07 incoherent subtree move accepted');
    check($guard->validatePlacement(2,7,1) === null, 'V06 active under active rejected');
    $bad = $svc->setActive(999999,1);
    check(($bad['ok'] ?? true) === false, 'V08 missing target accepted');
    report('V06-V08 parent-cycle-placement-guards');

    // SQL trigger forces failure on 3 after attempting a parent cascade.
    $db->exec("CREATE TRIGGER cat_w1_fail BEFORE UPDATE ON categories FOR EACH ROW
        BEGIN IF OLD.id=3 AND NEW.is_active=0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected failure';
        END IF; END");
    $before = states($db);
    $bad = $svc->setActive(1,0);
    check(($bad['ok'] ?? true) === false && ($bad['code'] ?? '') === 'transaction_failed', 'Rollback not surfaced');
    check(states($db) === $before, 'Rollback left partial cascade');
    // Si el formulario utiliza una transacción externa en Ventana 2,
    // el servicio debe PROPAGAR la excepción: no permitir commit parcial.
    $db->beginTransaction();
    try {
        $threw = false;
        try {
            $svc->setActive(1,0);
        } catch (PDOException $e) {
            $threw = true;
        }
        check($threw, 'Exception swallowed inside caller-owned transaction');
        $db->rollBack();
    } finally {
        if ($db->inTransaction()) $db->rollBack();
    }
    check(states($db) === $before, 'External injected failure modified rows');
    $db->exec('DROP TRIGGER cat_w1_fail');
    report('rollback-on-injected-SQL-failure-and-external-propagation');

    // A containing transaction can explicitly roll back the service update.
    $db->beginTransaction();
    try {
        $res = $svc->setActive(1,0);
        check(($res['ok'] ?? false) === true, 'External txn service failed');
        ensureStates($db, [1=>0,2=>0,3=>0], 'external while active');
        $db->rollBack();
    } finally {
        if ($db->inTransaction()) $db->rollBack();
    }
    ensureStates($db,[1=>1,2=>1,3=>1,4=>1],'external rollback');
    report('external-transaction-ownership-rollback');

    // Real concurrency: two separate PHP processes and PDO connections.
    $backupDir = 'C:\\xampp\\mi-pesca-backups\\categories_phase3_window1_20261021';
    check(is_dir($backupDir), 'Private backup missing');
    $marker = $backupDir.'\\lock_ready_'.bin2hex(random_bytes(4)).'.tmp';
    $php = PHP_BINARY;
    $start = static function(array $command): array {
        $pipes = [];
        $proc = proc_open($command, [1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($proc)) throw new RuntimeException('Could not spawn worker');
        return [$proc,$pipes];
    };
    $workers = [];
    try {
        $workers[] = $start([$php,__FILE__,'--hold',$testDb,$marker]);
        $until = microtime(true)+8;
        while (!is_file($marker) && microtime(true)<$until) usleep(40000);
        check(is_file($marker), 'Worker never acquired lock');
        $workers[] = $start([$php,__FILE__,'--activate',$testDb]);
        foreach ($workers as [$process,$pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $exit = proc_close($process);
            check($exit === 0, 'Worker exit='.$exit.' stderr='.$stderr.' out='.$stdout);
            echo trim($stdout).PHP_EOL;
        }
        $workers = [];
    } finally {
        foreach ($workers as [$process,$pipes]) {
            $status = proc_get_status($process);
            if ($status['running']) proc_terminate($process);
            foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
            if (is_resource($process)) proc_close($process);
        }
        if (is_file($marker)) unlink($marker);
    }
    ensureStates($db, [1=>1,2=>0,3=>0,4=>0], 'concurrent-state');
    report('concurrency-two-PDO-connections-serialized');

    check($db->query('SELECT id,category_id,is_active FROM products ORDER BY id')->fetchAll() === $productsBefore, 'Products changed after concurrency');
    report('all-cases-successful');
} finally {
    // Only the fresh, generated database can be destroyed.
    if ($created && preg_match('/^mi_pesca_cat_w1_[a-f0-9]{8}$/', $testDb)
        && $testDb !== $sourceName) {
        $admin->exec("DROP DATABASE IF EXISTS `{$testDb}`");
        report('isolated-db-dropped: '.$testDb);
    }
}
