<?php
namespace App\Controllers;

/**
 * BaseController para controladores PÚBLICOS.
 * No aplica ningún guard/admin.
 */
abstract class BaseController
{
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
