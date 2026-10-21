<?php
namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\AttributesCatalogActions;
use App\Controllers\Admin\Concerns\AttributesValueSetActions;
use App\Controllers\Admin\Concerns\AttributesWriteActions;

class AttributesController extends BaseController
{
    use AttributesCatalogActions;
    use AttributesWriteActions;
    use AttributesValueSetActions;
}
