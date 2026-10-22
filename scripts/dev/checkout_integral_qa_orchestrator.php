<?php
declare(strict_types=1);
/** Run only in temporary MariaDB with guard. No passwords in source. */
$root = dirname(__DIR__, 2);
$php = PHP_BINARY;
$env = getenv();
$expected = 'mysql:host=127.0.0.1;port=18959;dbname=mi_checkout_integral_v2_test;charset=utf8mb4';
if (($env['MI_CHECKOUT_INTEGRAL_DSN'] ?? '') !== $expected
    || empty($env['MI_CHECKOUT_INTEGRAL_USER'])
    || !array_key_exists('MI_CHECKOUT_INTEGRAL_PASSWORD',$env)) {
    fwrite(STDERR, "REFUSED: disposable 127.0.0.1:18959 checkout QA DSN/credentials required\n");
    exit(2);
}
$run = static function(string $test, ?string $mode = null) use ($php,$root,$env): array {
    $cmd = [$php, $root.'\\scripts\\dev\\'.$test];
    if ($mode !== null) $cmd[] = $mode;
    $spec = [0=>['pipe','r'], 1=>['pipe','w'], 2=>['pipe','w']];
    $process = proc_open($cmd, $spec, $pipes, $root, $env, ['bypass_shell' => true]);
    if (!is_resource($process)) throw new RuntimeException('Cannot start isolated QA');
    fclose($pipes[0]);
    return [$process,$pipes];
};
$finish = static function(array $job): array {
    [$process, $pipes] = $job;
    $out = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    return [$exit, $out, $error];
};
$single = static function(string $test, ?string $mode = null) use ($run,$finish): void {
    echo "# $test", ($mode === null ? '' : " $mode"), "\n";
    [$exit,$out,$error] = $finish($run($test,$mode));
    echo $out, $error;
    if ($exit !== 0) throw new RuntimeException("$test failed exit=$exit");
};
try {
    $single('checkout_payment_integrity_isolated.php');
    $single('checkout_webhook_simulated_isolated.php');
    $single('checkout_intent_concurrency_isolated.php');
    $single('checkout_order_concurrency_isolated.php', 'seed');
    $single('checkout_durable_marker_isolated.php');
    // True concurrency: two independent PHP processes issued before either is joined.
    echo "# CONCURRENT REAL ORDER WORKERS\n";
    $start = microtime(true);
    $a = $run('checkout_order_concurrency_isolated.php', 'attempt');
    $b = $run('checkout_order_concurrency_isolated.php', 'attempt');
    [$aExit,$aOut,$aErr]=$finish($a);
    [$bExit,$bOut,$bErr]=$finish($b);
    printf("worker A exit=%d: %s%s\n", $aExit,trim($aOut),$aErr);
    printf("worker B exit=%d: %s%s\n", $bExit,trim($bOut),$bErr);
    printf("total elapsed=%.3f seconds\n", microtime(true)-$start);
    if ($aExit !== 0 || $bExit !== 0) throw new RuntimeException('Concurrent worker not idempotent');
    $one=json_decode(trim($aOut),true);
    $two=json_decode(trim($bOut),true);
    if (!is_array($one)||!is_array($two)||empty($one['ok'])||empty($two['ok'])
        ||(int)($one['order_id']??0)<=0||(int)($one['order_id']??0)!==(int)($two['order_id']??0)) {
        throw new RuntimeException('Concurrent workers disagree on order');
    }
    $single('checkout_order_concurrency_isolated.php','verify');
    echo "[CHECKOUT_PRIVATE_QA] ALL PASSED\n";
} catch (Throwable $e) {
    fwrite(STDERR,"[CHECKOUT_PRIVATE_QA] FAILED: ".$e->getMessage()."\n");
    exit(1);
}
