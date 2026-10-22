<?php
declare(strict_types=1);
/** Portable build gate. Run from any working directory; never connects to a database. */
$root = dirname(__DIR__, 2);
chdir($root);
$failures = [];
$command = static function (array $args, string $label) use (&$failures): void {
    echo "[gate] $label\n";
    $parts = array_map('escapeshellarg', $args);
    $process = proc_open(implode(' ', $parts), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) { $failures[] = "$label: no se pudo ejecutar"; return; }
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    if ($status !== 0) { $failures[] = "$label (codigo $status)"; echo $stdout, $stderr; }
};
$scan = static function (string $dir, string $extension) use ($root): array {
    $files = [];
    $path = $root . '/' . $dir;
    if (!is_dir($path)) { return $files; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === $extension) { $files[] = $file->getPathname(); }
    }
    sort($files);
    return $files;
};
$phpFiles = array_merge($scan('app', 'php'), $scan('config', 'php'), $scan('scripts', 'php'), $scan('public_html', 'php'));
echo '[gate] PHP: ' . count($phpFiles) . " archivos\n";
foreach ($phpFiles as $file) {
    $out = []; $code = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $out, $code);
    if ($code !== 0) { $failures[] = 'Sintaxis PHP: ' . substr($file, strlen($root) + 1); echo implode("\n", $out), "\n"; }
}
// JS syntax checks are meaningful only when Node is available; never run PHP lint on JS/CSS/docs.
$jsFiles = array_merge($scan('public_html/assets/js', 'js'), $scan('scripts', 'js'));
$node = getenv('NODE_BINARY') ?: (PHP_OS_FAMILY === 'Windows' ? 'node.exe' : 'node');
$foundNode = [];
exec((PHP_OS_FAMILY === 'Windows' ? 'where ' : 'command -v ') . escapeshellarg($node) . ' 2>' . (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), $foundNode, $nodeExit);
if ($nodeExit === 0) {
    echo '[gate] JavaScript: ' . count($jsFiles) . " archivos\n";
    foreach ($jsFiles as $file) {
        $out = []; $code = 0;
        exec(escapeshellarg($node) . ' --check ' . escapeshellarg($file) . ' 2>&1', $out, $code);
        if ($code !== 0) { $failures[] = 'Sintaxis JS: ' . substr($file, strlen($root) + 1); echo implode("\n", $out), "\n"; }
    }
    $command([$node, 'scripts/build/sidebar_smoke.mjs'], 'Sidebar admin: inicializacion y toggle');
} else { echo "[gate] AVISO: Node no disponible; comprobacion JS omitida\n"; }
$command([PHP_BINARY, 'scripts/build/sidebar_style_contract.php'], 'Contrato visual de clases del sidebar');
$command([PHP_BINARY, 'scripts/build/admin_mobile_drawer_contract.php'], 'Contrato drawer móvil admin');
$command([PHP_BINARY, 'scripts/build/admin_page_header_contract.php'], 'Contrato de encabezado administrativo');
$command([PHP_BINARY, 'scripts/build/admin_master_layout_contract.php'], 'Contrato layout maestro administrativo');
$command([PHP_BINARY, 'scripts/build/dashboard_responsive_contract.php'], 'Contrato Dashboard responsive');
$command([PHP_BINARY, 'scripts/build/orders_index_contract.php'], 'Contrato listado de Pedidos');
$command([PHP_BINARY, 'scripts/build/seo_admin_contract.php'], 'Contrato Auditoría SEO y editores');
$command([PHP_BINARY, 'scripts/build/marketing_admin_contract.php'], 'Contrato Marketing: funcionalidades y diseño');
$command([PHP_BINARY, 'scripts/build/marketing_email_v3_contract.php'], 'Contrato Email Marketing V3');
$command([PHP_BINARY, 'scripts/build/marketing_email_v3_phase2_contract.php'], 'Contrato Email Marketing V3: Envíos, Cola y Plantillas');
$command([PHP_BINARY, 'scripts/build/marketing_email_editor_contract.php'], 'Contrato Email Marketing V3: Editor y detalle');
$command([PHP_BINARY, 'scripts/build/marketing_email_campaign_lifecycle_contract.php'], 'Contrato Email Marketing: ciclo de vida seguro');
$command([PHP_BINARY, 'scripts/build/admin_css_scope.php'], 'CSS exclusivo del administrador');
$command([PHP_BINARY, 'scripts/build/css_unification_contract.php'], 'Contrato CSS unificado y Tailwind');
$command([PHP_BINARY, 'scripts/build/admin_classnames_contract.php'], 'Contrato de clases administrativas V3');
$command([PHP_BINARY, 'scripts/build/smoke.php'], 'Smoke de clases y checkout');
$command([PHP_BINARY, 'scripts/audit/usage_checkout_cart.php'], 'Contratos de uso checkout/cart');
$command([PHP_BINARY, 'scripts/audit/encoding_scan.php', '--fail-on-bom', '--bom-only', '--quiet'], 'UTF-8 sin BOM');
$command([PHP_BINARY, 'scripts/build/regression.php'], 'Pruebas de regresion');
$command([PHP_BINARY, 'scripts/build/checkout_intent_reuse_contract.php'], 'CheckoutIntent: campañas NULL/0 y reuso seguro');
$command([PHP_BINARY, 'scripts/build/checkout_payment_orphan_contract.php'], 'Pago aprobado sin pedido: respuesta y conciliación');
// Architectural guardrails carried forward from the previous shell/batch gate.
$patterns = [
    'CartService legacy' => '/\\bCartService\\b/',
    'CheckoutService legacy' => '/\\bCheckoutService\\b/',
    'VariantAdminService legacy' => '/\\bVariantAdminService\\b/',
];
foreach (array_merge($scan('app', 'php'), $scan('config', 'php'), $scan('public_html', 'php')) as $file) {
    $source = file_get_contents($file);
    foreach ($patterns as $label => $pattern) {
        if (preg_match($pattern, $source)) { $failures[] = "$label en " . substr($file, strlen($root) + 1); }
    }
}
$limits = [
 'app/Controllers/Admin/ProductsController.php' => 375,
 'app/Controllers/Admin/ProductVariantsController.php' => 80,
 'app/Controllers/Admin/AttributesController.php' => 80,
 'app/Controllers/Admin/Concerns/VariantReadActions.php' => 180,
 'app/Controllers/Admin/Concerns/VariantAttributeActions.php' => 300,
 'app/Controllers/Admin/Concerns/VariantLifecycleActions.php' => 320,
 'app/Services/Admin/Variants/VariantAttributesService.php' => 700,
 'app/Services/Admin/Variants/VariantLifecycleService.php' => 460,
 'app/Services/Admin/Variants/VariantWriteService.php' => 375,
 'public_html/assets/js/admin/product_variants/index.js' => 180,
 'public_html/assets/js/admin/product_variants/bindings.js' => 220,
 'public_html/assets/js/admin/product_variants/pricing_actions.js' => 40,
 'public_html/assets/js/admin/product_variants/pricing_iva_actions.js' => 180,
 'public_html/assets/js/admin/product_variants/pricing_bulk_actions.js' => 380,
];
foreach ($limits as $file => $max) {
    $content = @file_get_contents($root . '/' . $file);
    if ($content === false) { $failures[] = "Archivo clave ausente: $file"; continue; }
    $lines = substr_count($content, "\n") + (str_ends_with($content, "\n") ? 0 : 1);
    if ($lines > $max) { $failures[] = "$file supera $max lineas ($lines)"; }
}
$pricing = @file_get_contents('public_html/assets/js/admin/product_variants/pricing_actions.js');
if ($pricing === false || !str_contains($pricing, 'bindPricingIvaActions') || !str_contains($pricing, 'bindPricingBulkActions')) {
    $failures[] = 'pricing_actions.js no delega en ambos modulos';
}
foreach ($scan('app/Views/pages/admin', 'php') as $file) {
    $src = file_get_contents($file);
    if (preg_match('/=\\s*function\\s*\\(|json_decode\\s*\\(|\\bfunction\\s*\\(/', $src)) {
        $failures[] = 'Logica en vista admin: ' . substr($file, strlen($root) + 1);
    }
}
if ($failures) { fwrite(STDERR, "[gate] ERROR (" . count($failures) . "):\n - " . implode("\n - ", $failures) . "\n"); exit(1); }
echo "[gate] OK\n";
