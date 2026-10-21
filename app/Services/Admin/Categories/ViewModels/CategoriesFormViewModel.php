<?php
namespace App\Services\Admin\Categories\ViewModels;

use App\Support\Catalog\CategoryLabel;

final class CategoriesFormViewModel
{
    /**
     * @param array<string,mixed> $row
     * @param array<int,array<string,mixed>> $parents
     * @param array<string,mixed>|null $seoAudit
     * @param array<string,mixed> $seoSettings
     * @param array<string,mixed> $seoBlocks
     * @return array{categories_form_view: array<string,mixed>}
     */
    public function build(array $row, array $parents, ?array $seoAudit, array $seoSettings, array $seoBlocks): array
    {
        $id = (int)($row['id'] ?? 0);
        $slug = (string)($row['slug'] ?? '');
        $name = CategoryLabel::sanitize((string)($row['name'] ?? ''), $slug);
        $row['name'] = $name;
        $parents = array_map(
            static function (array $parent): array {
                $parent['name'] = CategoryLabel::sanitize(
                    (string)($parent['name'] ?? ''),
                    (string)($parent['slug'] ?? ''),
                    'Categoria'
                );
                return $parent;
            },
            $parents
        );

        return [
            'categories_form_view' => [
                'pageTitle' => $id > 0 ? 'Editar categoria' : 'Nueva categoria',
                'crumbs' => admin_crumbs('categories.edit', ['name' => $name]),
                'cat' => $row,
                'id' => $id,
                'categoryName' => $name,
                'slug' => $slug,
                'coverImage' => (string)($row['cover_image'] ?? ''),
                'parentId' => (int)($row['parent_id'] ?? 0),
                'order' => (int)($row['sort'] ?? 0),
                'isActive' => (int)($row['is_active'] ?? 1),
                'parents' => $parents,
                'seoAudit' => is_array($seoAudit) ? $seoAudit : [],
                'seoSettings' => $seoSettings,
                'seoBlocks' => $seoBlocks,
            ],
        ];
    }
}
