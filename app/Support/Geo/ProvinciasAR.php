<?php
namespace App\Support\Geo;

/**
 * Catálogo simple de provincias argentinas para formularios.
 *
 * - Fuente única para UI + validación server-side.
 * - Guardamos en DB el nombre (string) para mantenerlo simple.
 */
final class ProvinciasAR
{
    /**
     * @return string[]
     */
    public static function all(): array
    {
        return [
            'Buenos Aires',
            'Ciudad Autónoma de Buenos Aires',
            'Catamarca',
            'Chaco',
            'Chubut',
            'Córdoba',
            'Corrientes',
            'Entre Ríos',
            'Formosa',
            'Jujuy',
            'La Pampa',
            'La Rioja',
            'Mendoza',
            'Misiones',
            'Neuquén',
            'Río Negro',
            'Salta',
            'San Juan',
            'San Luis',
            'Santa Cruz',
            'Santa Fe',
            'Santiago del Estero',
            'Tierra del Fuego',
            'Tucumán',
        ];
    }
}
