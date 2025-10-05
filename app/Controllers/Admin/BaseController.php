<?php
namespace App\Controllers\Admin;

abstract class BaseController {
  public function __construct() { require_admin(); }
}
