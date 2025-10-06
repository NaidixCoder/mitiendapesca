<?php
namespace App\Controllers\Admin;

/**
 * BaseController común para todos los controladores.
 * Provee helpers mínimos y es 100% independiente de Admin.
 */
abstract class BaseController
{
    /** Renderiza una vista */
    protected function view(string $name, array $data = []): void
    {
        if (function_exists('render_page')) {
            render_page($name, $data);
        } else {
            // Fallback: incluye directamente si existe
            $vf = base_path('app/Views/pages/' . $name . '.php');
            if (is_file($vf)) {
                extract($data, EXTR_SKIP);
                include $vf;
            } else {
                http_response_code(404);
                echo "Vista no encontrada: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            }
        }
    }

    /** JSON helper */
    protected function json($data, int $status = 200): void
    {
        if (function_exists('json')) {
            json($data, $status);
        } else {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    /** Redirect helper */
    protected function redirect(string $path): void
    {
        if (function_exists('redirect')) {
            redirect($path);
        } else {
            header('Location: ' . $path);
            exit;
        }
    }

    /** Método HTTP en uso */
    protected function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /** Atajo para saber si es POST */
    protected function isPost(): bool
    {
        return $this->method() === 'POST';
    }
}
