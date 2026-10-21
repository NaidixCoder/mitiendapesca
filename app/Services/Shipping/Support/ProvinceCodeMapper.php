<?php
namespace App\Services\Shipping\Support;

final class ProvinceCodeMapper
{
    public static function fromState(string $state): string
    {
        $s = strtolower(trim($state));
        if ($s === '') return '';

        $map = [
            'buenos aires' => 'B',
            'caba' => 'C',
            'ciudad autonoma de buenos aires' => 'C',
            'capital federal' => 'C',
            'catamarca' => 'K',
            'chaco' => 'H',
            'chubut' => 'U',
            'cordoba' => 'X',
            'córdoba' => 'X',
            'corrientes' => 'W',
            'entre rios' => 'E',
            'entre ríos' => 'E',
            'formosa' => 'P',
            'jujuy' => 'Y',
            'la pampa' => 'L',
            'la rioja' => 'F',
            'mendoza' => 'M',
            'misiones' => 'N',
            'neuquen' => 'Q',
            'neuquén' => 'Q',
            'rio negro' => 'R',
            'río negro' => 'R',
            'salta' => 'A',
            'san juan' => 'J',
            'san luis' => 'D',
            'santa cruz' => 'Z',
            'santa fe' => 'S',
            'santiago del estero' => 'G',
            'tierra del fuego' => 'V',
            'tucuman' => 'T',
            'tucumán' => 'T',
        ];

        if (isset($map[$s])) return $map[$s];

        // Acepta si ya viene en formato código (1 letra)
        if (preg_match('/^[A-Z]$/', strtoupper($state))) return strtoupper($state);

        return '';
    }
}
