<?php
declare(strict_types=1);

namespace App\Services\Catalog;

use App\Services\Admin\Categories\CategoryVisibilityPolicyService;
use PDO;

/**
 * Adaptador compatible para consumidores de Catálogo/Admin.
 * La única regla de visibilidad vive en CategoryVisibilityPolicyService.
 */
class CategoryAdminService
{
    private CategoryVisibilityPolicyService $policy;

    public function __construct(PDO $db)
    {
        $this->policy = new CategoryVisibilityPolicyService($db);
    }

    /**
     * Conserva el contrato anterior del toggle usado por CategoriesController:
     * ok, from, to, cascade_ids, children_count, products_count.
     * @return array<string,mixed>
     */
    public function toggleActiveCascade(int $categoryId): array
    {
        return $this->policy->toggle($categoryId);
    }

    /** Estado deseado explícito, idempotente, para el futuro formulario. */
    public function setActive(int $categoryId, int $desiredActive): array
    {
        return $this->policy->setActive($categoryId, $desiredActive);
    }
}
