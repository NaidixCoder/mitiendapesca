<?php
namespace App\Admin\Services;

class AdminAuth
{
    public static function requireAdmin(): void
    {
        if (empty($_SESSION['uid'])) {
            header('Location: /login'); exit;
        }
        if (($_SESSION['urole'] ?? 'customer') !== 'admin') {
            http_response_code(403);
            echo 'Forbidden'; exit;
        }
    }
}
