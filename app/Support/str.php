<?php
function str_slug(string $s): string {
    $s = iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/','-',$s);
    $s = trim($s,'-');
    return $s ?: 'item';
}
