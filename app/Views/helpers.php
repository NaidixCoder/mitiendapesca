<?php
/**
 * Render del layout: head -> header -> page -> footer
 * Ahora acepta variables: render_page('admin/dashboard', ['kpis'=>$kpis, ...])
 */
function render_page(string $name, array $vars = []): void {
    $views  = BASE_PATH . '/app/Views';
    $head   = $views . '/partials/head.php';
    $header = $views . '/partials/header/header.php';
    $footer = $views . '/partials/footer.php';
    $page   = $views . '/pages/' . $name . '.php';

    // Hacer disponibles las variables en todo el layout
    if (!empty($vars)) extract($vars, EXTR_SKIP);

    echo "<!-- RENDER start: {$name} -->";

    if (is_file($head))   require_once $head;
    if (is_file($header)) require_once $header;

    $flash = $views . '/partials/flash.php';
    if (is_file($flash)) require_once $flash;

    if (is_file($page)) {
        require $page;
    } else {
        http_response_code(404);
        echo "<main style='padding:2rem 1rem;'><h1>404</h1><p>Página '".e($name)."' no encontrada.</p></main>";
    }

    if (is_file($footer)) require_once $footer;

    echo "<!-- RENDER end: {$name} -->";
}

/**
 * Incluye una sección/partial con $data extraído como variables locales.
 * section('bloque', ['titulo' => 'Hola']);
 */
function section(string $name, array $data = []): void {
    $file = BASE_PATH . '/app/Views/partials/' . $name . '.php';
    if (!is_file($file)) { echo "<!-- Falta sección: {$name} ({$file}) -->"; return; }
    if (!empty($data)) extract($data, EXTR_SKIP);
    require $file;
}

/* =======================
   CSRF (normalizado)
   - Campo unificado: name="csrf"
   - Si ya existen en boot/12-csrf.php, no se redeclaran
   ======================= */

if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
        return $_SESSION['csrf'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">';
    }
}
if (!function_exists('csrf_verify')) {
    function csrf_verify(string $token): bool {
        return hash_equals($_SESSION['csrf'] ?? '', $token);
    }
}
/** Azúcar para formularios POST actuales */
if (!function_exists('verify_csrf')) {
    function verify_csrf(): bool {
        return csrf_verify($_POST['csrf'] ?? '');
    }
}

/* ====== Helpers de formularios / flujo ====== */

function old(string $key, $default = '') {
    return e($_POST[$key] ?? $default);
}

function flash(string $key, $val = null) {
    if ($val === null) { $v = $_SESSION['flash'][$key] ?? null; unset($_SESSION['flash'][$key]); return $v; }
    $_SESSION['flash'][$key] = $val;
}

function redirect(string $path) {
    header('Location: ' . url($path));
    exit;
}
