<?php
namespace App\Services\Admin\Audit\ViewModels;

/**
 * Normaliza payload para app/Views/pages/admin/audit/index.php.
 *
 * Este ViewModel define keys estables para que la vista haga solo render.
 */
final class AuditIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        return [
            'filters' => is_array($input['filters'] ?? null) ? $input['filters'] : [],
            'rows' => is_array($input['rows'] ?? null) ? $input['rows'] : [],
            'pagination' => is_array($input['pagination'] ?? null) ? $input['pagination'] : [],
            'meta' => is_array($input['meta'] ?? null) ? $input['meta'] : [],
            'links' => is_array($input['links'] ?? null) ? $input['links'] : [],
        ];
    }
}




