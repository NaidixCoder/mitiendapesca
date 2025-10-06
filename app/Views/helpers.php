<?php
/**
 * Helpers de vistas y layout
 * Requisitos: base_path(), base_url(), sesiones activas (para flash)
 */

if (!function_exists('render_page')) {
  /**
   * Renderiza una página en app/Views/pages/{name}.php
   * Incluye head, header y footer si existen.
   */
  function render_page(string $name, array $vars = []): void {
      $viewsDir = base_path('app/Views');
      $head   = $viewsDir . '/partials/head.php';
      $header = $viewsDir . '/partials/header/header.php';
      $footer = $viewsDir . '/partials/footer.php';
      $page   = $viewsDir . '/pages/' . $name . '.php';

      if (!empty($vars)) { extract($vars, EXTR_SKIP); }

      echo "<!-- RENDER start: {$name} -->\n";

      if (is_file($head))   include $head;
      if (is_file($header)) include $header;

      if (is_file($page)) {
          include $page;
      } else {
          http_response_code(404);
          echo '<main class="container mx-auto p-6"><h1>Vista no encontrada</h1><p>' . e($name) . '</p></main>';
      }

      if (is_file($footer)) include $footer;
      echo "\n<!-- RENDER end: {$name} -->\n";
  }
}

if (!function_exists('section')) {
  /**
   * Incluye una sección/partial de app/Views/{path}.php
   * Ej: section('admin/dashboard/low_stock', ['rows'=>$lowStock])
   */
  function section(string $path, array $vars = []): void {
      $file = base_path('app/Views/' . ltrim($path,'/') . '.php');
      if (!empty($vars)) extract($vars, EXTR_SKIP);
      if (is_file($file)) {
          include $file;
      } else {
          echo "<!-- section missing: " . e($path) . " -->";
      }
  }
}

if (!function_exists('flash')) {
  /**
   * Flash messages usando $_SESSION['flash'][$key]
   * - Al leerlas, se consumen (1 sólo uso).
   */
  function flash(?string $key=null, $val=null) {
      if (session_status() !== PHP_SESSION_ACTIVE) @session_start();

      if ($key === null) {
          $all = $_SESSION['flash'] ?? [];
          unset($_SESSION['flash']);
          return $all;
      }
      if ($val === null) {
          $v = $_SESSION['flash'][$key] ?? null;
          if (isset($_SESSION['flash'][$key])) unset($_SESSION['flash'][$key]);
          return $v;
      }
      $_SESSION['flash'][$key] = $val;
      return null;
  }
}

if (!function_exists('redirect')) {
  /** Redirect helper */
  function redirect(string $path) {
      header('Location: ' . base_url($path));
      exit;
  }
}

if (!function_exists('json')) {
  /** JSON response helper */
  function json($data, int $status = 200): void {
      http_response_code($status);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      exit;
  }
}
