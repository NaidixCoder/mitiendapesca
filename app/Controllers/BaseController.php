<?php
namespace App\Controllers;

/**
 * Bridge/alias para que los controllers públicos (Catalog\*) puedan
 * extender App\Controllers\BaseController, reutilizando la base de Admin.
 */
abstract class BaseController extends \App\Controllers\Admin\BaseController
{
    // Si querés agregar helpers comunes al público, podés hacerlo acá.
}
