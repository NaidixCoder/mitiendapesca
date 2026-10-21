<?php
namespace App\Controllers\Admin;

use App\Controllers\Admin\Concerns\VariantAttributeActions;
use App\Controllers\Admin\Concerns\VariantLifecycleActions;
use App\Controllers\Admin\Concerns\VariantReadActions;
use App\Services\Admin\Variants\VariantAuditService;
use PDO;

class ProductVariantsController extends BaseController
{
    use VariantReadActions;
    use VariantAttributeActions;
    use VariantLifecycleActions;

    private function auditService(PDO $db): VariantAuditService
    {
        return new VariantAuditService($db);
    }
}
