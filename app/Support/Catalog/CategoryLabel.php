<?php
namespace App\Support\Catalog;

final class CategoryLabel
{
    public static function isSuspicious(string $name): bool
    {
        $value = trim($name);
        if ($value === '') {
            return true;
        }

        return preg_match('~^/?admin/|^https?://~i', $value) === 1 || str_contains($value, '/');
    }

    public static function sanitize(string $name, string $slug = '', string $default = 'Categoria'): string
    {
        $value = trim($name);
        if ($value !== '' && !self::isSuspicious($value)) {
            return $value;
        }

        $slug = trim($slug);
        if ($slug !== '') {
            $human = preg_replace('/[-_]+/', ' ', $slug);
            $human = trim((string)$human);
            if ($human !== '') {
                return mb_convert_case($human, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return $default;
    }
}
