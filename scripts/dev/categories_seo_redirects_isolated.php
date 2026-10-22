<?php
declare(strict_types=1);
/**
 * Smoke de redirects sobre fixture privado solamente.
 * NO invocar con base habitual. Solamente SELECT; flag se setea desde fixture separado.
 */
$expect = (string)getenv('CATS_REDIRECT_FIXTURE_DB');
if ($expect !== 'mi_pesca_cat_w6cb_20261022') {
    fwrite(STDERR, "[redirects-isolated] REFUSED: synthetic DB name required\n");
    exit(2);
}
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = '127.0.0.1:18158';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/old-direct';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH.'/vendor/autoload.php';
foreach (glob(BASE_PATH.'/config/boot/*.php') ?: [] as $boot) require_once $boot;
$db = db();
if ((string)$db->query('SELECT DATABASE()')->fetchColumn() !== $expect) {
    fwrite(STDERR, "[redirects-isolated] REFUSED: wrong database\n");
    exit(2);
}
$resolver = new \App\Services\Seo\PublicRedirectTargetResolver($db);
$hidden = (string)$db->query("SELECT setting_value FROM app_settings WHERE setting_key='catalog_hide_inactive_category_products'")->fetchColumn() === '1';
$valid = [
    '/old-direct' => '/producto?slug=good-item',
    '/old-first' => '/producto?slug=good-item',
    '/old-cat-visible' => '/categoria?slug=root-ok',
    '/old-brand' => '/marca?slug=marca-qa',
];
foreach ($valid as $from=>$expected) {
    $res = $resolver->resolve($from);
    if (!is_array($res) || $res['target'] !== $expected || (int)$res['status'] !== 301) {
        throw new \RuntimeException('Invalid redirect '.$from.' '.json_encode($res));
    }
}
$bad = ['/old-cycle-a','/old-self','/old-missing','/old-cat-hidden',
        '/old-out','/old-proto','/old-private','/old-traversal','/old-crlf',
        '/old-product-noindex','/old-product-altcanon','/old-long-0'];
if ($hidden) $bad[] = '/old-product-hidden';
foreach ($bad as $from) {
    if ($resolver->resolve($from) !== null) throw new \RuntimeException('Unsafe redirect '.$from);
}
if (!$hidden && ($resolver->resolve('/old-product-hidden')['target'] ?? '') !== '/producto?slug=ancestor-branch-item') {
    throw new \RuntimeException('B1 OFF unexpectedly blocked active product');
}
echo '[redirects-isolated] PASS B1='.($hidden?'ON':'OFF').' '.count($valid).' valid + '.count($bad).' invalid paths'.PHP_EOL;
