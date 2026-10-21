<?php
namespace App\Support\Format;

final class Slug
{
    public static function slugify(string $s): string
    {
        $s2 = @iconv('UTF-8','ASCII//TRANSLIT',$s);
        if ($s2 === false) $s2 = $s;
        $s2 = strtolower($s2);
        $s2 = preg_replace('/[^a-z0-9]+/','-',$s2);
        $s2 = trim($s2,'-');
        return $s2 ?: 'n-a';
    }
}

