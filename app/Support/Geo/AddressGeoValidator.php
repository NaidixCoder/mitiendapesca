<?php
namespace App\Support\Geo;

final class AddressGeoValidator
{
    /** @var array<string,array<int,array{city:string,state:string}>>|null */
    private static ?array $zipIndex = null;

    /**
     * @return array{ok:bool,errors:array<string,string>}
     */
    public static function validate(string $zip, string $city, string $state): array
    {
        $errors = [];

        $zipDigits = preg_replace('/\D+/', '', trim($zip)) ?? '';
        $city = trim($city);
        $state = trim($state);

        if ($zipDigits === '') {
            $errors['zip'] = 'Ingresa el codigo postal.';
            return ['ok' => false, 'errors' => $errors];
        }

        if (!preg_match('/^\d{4}$/', $zipDigits)) {
            $errors['zip'] = 'Ingresa un codigo postal valido de 4 digitos.';
            return ['ok' => false, 'errors' => $errors];
        }

        if ($city === '' || $state === '') {
            return ['ok' => true, 'errors' => []];
        }

        $entries = self::zipIndex()[$zipDigits] ?? [];
        if (empty($entries)) {
            $errors['zip'] = 'No encontramos ese codigo postal en el padron.';
            return ['ok' => false, 'errors' => $errors];
        }

        $stateNorm = self::normalize($state);
        $cityNorm = self::normalize($city);

        $stateMatches = array_values(array_filter(
            $entries,
            static fn(array $entry): bool => self::normalize((string)($entry['state'] ?? '')) === $stateNorm
        ));

        if (empty($stateMatches)) {
            $errors['state'] = 'La provincia no coincide con el codigo postal ingresado.';
            return ['ok' => false, 'errors' => $errors];
        }

        $stateCityMatches = array_values(array_filter(
            $stateMatches,
            static fn(array $entry): bool => self::cityMatches($cityNorm, (string)($entry['city'] ?? ''))
        ));

        if (empty($stateCityMatches)) {
            $errors['city'] = 'La localidad no coincide con el codigo postal y la provincia seleccionados.';
            return ['ok' => false, 'errors' => $errors];
        }

        return ['ok' => true, 'errors' => []];
    }

    /**
     * @return array<string,array<int,array{city:string,state:string}>>
     */
    private static function zipIndex(): array
    {
        if (self::$zipIndex !== null) {
            return self::$zipIndex;
        }

        $path = base_path('app/Support/Geo/data/ar_postal_index.json');
        if (!is_file($path)) {
            self::$zipIndex = [];
            return self::$zipIndex;
        }

        $raw = @file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        self::$zipIndex = is_array($data) ? $data : [];

        return self::$zipIndex;
    }

    private static function cityMatches(string $inputNorm, string $candidate): bool
    {
        $candidateNorm = self::normalize($candidate);
        if ($inputNorm === '' || $candidateNorm === '') {
            return false;
        }

        if ($inputNorm === $candidateNorm) {
            return true;
        }

        return (strlen($inputNorm) > 3 && str_contains($candidateNorm, $inputNorm))
            || (strlen($candidateNorm) > 3 && str_contains($inputNorm, $candidateNorm));
    }

    private static function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value, 'UTF-8'));
        if ($value === '') {
            return '';
        }

        $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($trans) && $trans !== '') {
            $value = $trans;
        }

        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }
}
