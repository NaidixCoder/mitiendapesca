<?php
namespace App\Services\Account;

use App\Repositories\Account\ProfileRepository;

class ProfileService
{
    public function __construct(private ProfileRepository $repo) {}

    /**
     * @return array{0:bool,1:array,2:array} [$ok,$errors,$clean]
     */
    public function validateAndNormalize(array $input): array
    {
        $errors = [];

        $phone = trim((string)($input['phone'] ?? ''));
        // Permitimos números, espacios, +, -, (),.
        if ($phone !== '' && !preg_match('/^[0-9\s\+\-\(\)\.]{8,30}$/', $phone)) {
            $errors['phone'] = 'Ingresá un teléfono válido.';
        }

        $birth = trim((string)($input['birth_date'] ?? ''));
        $birthNorm = '';
        if ($birth !== '') {
            // Esperamos YYYY-MM-DD (input type=date)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
                $errors['birth_date'] = 'Ingresá una fecha válida.';
            } else {
                $ts = strtotime($birth);
                if (!$ts) {
                    $errors['birth_date'] = 'Ingresá una fecha válida.';
                } else {
                    $min = strtotime('1900-01-01');
                    $max = strtotime(date('Y-m-d'));
                    if ($ts < $min || $ts > $max) {
                        $errors['birth_date'] = 'La fecha de nacimiento no es válida.';
                    } else {
                        $birthNorm = date('Y-m-d', $ts);
                    }
                }
            }
        }

        $emailOpt = !empty($input['marketing_email_opt_in']) ? 1 : 0;
        $waOpt    = !empty($input['marketing_whatsapp_opt_in']) ? 1 : 0;

        $clean = [
            'phone' => $phone,
            'birth_date' => $birthNorm,
            'marketing_email_opt_in' => $emailOpt,
            'marketing_whatsapp_opt_in' => $waOpt,
        ];

        return [empty($errors), $errors, $clean];
    }

    public function getProfile(int $userId): ?array
    {
        return $this->repo->findByUserId($userId);
    }

    public function saveProfile(int $userId, array $clean): void
    {
        $this->repo->upsert($userId, $clean);
    }
}
