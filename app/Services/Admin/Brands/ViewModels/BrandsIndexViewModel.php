<?php
namespace App\Services\Admin\Brands\ViewModels;

final class BrandsIndexViewModel
{
    /** @return array<string,mixed> */
    public function build(array $rows, int $total, string $query, int $requestedPage, int $perPage): array
    {
        $perPage = in_array($perPage, [25, 50], true) ? $perPage : 25;
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($requestedPage, $pages));
        foreach ($rows as &$row) {
            $row['id'] = (int)($row['id'] ?? 0);
            $row['sort'] = (int)($row['sort'] ?? 0);
            $row['product_count'] = (int)($row['product_count'] ?? 0);
            $row['active_product_count'] = (int)($row['active_product_count'] ?? 0);
        }
        unset($row);
        $url = static function (int $number) use ($query, $perPage): string {
            return url('/admin/brands') . '?' . http_build_query([
                'q' => $query, 'per' => $perPage, 'page' => $number,
            ], '', '&', PHP_QUERY_RFC3986);
        };
        return [
            'rows' => $rows, 'q' => $query, 'total' => $total,
            'page' => $page, 'pages' => $pages, 'perPage' => $perPage,
            'start' => $total ? (($page - 1) * $perPage + 1) : 0,
            'end' => $total ? min($total, $page * $perPage) : 0,
            'prevUrl' => $page > 1 ? $url($page - 1) : null,
            'nextUrl' => $page < $pages ? $url($page + 1) : null,
        ];
    }
}
