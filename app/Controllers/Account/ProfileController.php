<?php
namespace App\Controllers\Account;

use App\Repositories\Account\ProfileRepository;
use App\Repositories\Users\UserFiscalProfileRepository;
use App\Repositories\Users\UserRepository;
use App\Services\Account\ProfileService;
use App\Support\Security\CheckoutFiscal;

class ProfileController
{
    private function buildProfileBadgeHint(array $user, ?array $profile): string
    {
        $missing = [];

        $name = isset($user['name']) && is_string($user['name']) ? trim($user['name']) : '';
        $phone = isset($profile['phone']) && is_string($profile['phone']) ? trim($profile['phone']) : '';
        $emailVerified = !empty($user['email_verified']);

        if ($name === '') {
            $missing[] = 'nombre';
        }
        if ($phone === '') {
            $missing[] = 'telefono';
        }
        if (!$emailVerified) {
            $missing[] = 'verificar email';
        }

        if (count($missing) === 0) {
            return 'Completo';
        }
        if (count($missing) === 1) {
            return 'Falta ' . $missing[0];
        }
        if (count($missing) === 2) {
            return 'Falta ' . $missing[0] . ' y ' . $missing[1];
        }

        return 'Faltan datos clave';
    }

    private function normalizeFiscalProfile(?array $fiscalProfile): ?array
    {
        if (!is_array($fiscalProfile) || empty($fiscalProfile)) {
            return $fiscalProfile;
        }

        $taxCondition = isset($fiscalProfile['tax_condition']) && is_string($fiscalProfile['tax_condition'])
            ? $fiscalProfile['tax_condition']
            : 'consumidor_final';

        $fiscalProfile['tax_condition'] = CheckoutFiscal::normalizeTaxCondition($taxCondition);
        return $fiscalProfile;
    }

    public function edit(): void
    {
        auth_required();

        $uid = (int)($_SESSION['uid'] ?? 0);
        $svc = new ProfileService(new ProfileRepository(db()));
        $userRepo = new UserRepository(db());

        $user = $userRepo->findById($uid) ?: [];

        $profile = null;
        try {
            $profile = $svc->getProfile($uid);
        } catch (\Throwable $e) {
            // Si aún no existe la tabla, no romper
        }

        $fiscalProfile = null;
        try {
            $fRepo = new UserFiscalProfileRepository(db());
            if ($fRepo->tableExists()) {
                $fiscalProfile = $fRepo->findByUserId($uid);
            }
        } catch (\Throwable $e) {
            // no bloqueante
        }

        $fiscalProfile = $this->normalizeFiscalProfile($fiscalProfile);
        $profileBadgeHint = $this->buildProfileBadgeHint($user, $profile);

        render_page('cuenta/perfil', [
            'pageId' => 'account_profile',
            'user' => $user,
            'profile' => $profile,
            'fiscalProfile' => $fiscalProfile,
            'profileBadgeHint' => $profileBadgeHint,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        auth_required();
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        $svc = new ProfileService(new ProfileRepository(db()));
        $userRepo = new UserRepository(db());

        $fiscalProfile = null;
        try {
            $fRepo = new UserFiscalProfileRepository(db());
            if ($fRepo->tableExists()) {
                $fiscalProfile = $fRepo->findByUserId($uid);
            }
        } catch (\Throwable $e) {
            // no bloqueante
        }

        $fiscalProfile = $this->normalizeFiscalProfile($fiscalProfile);

        // Nombre (users.name)
        $name = trim((string)($_POST['name'] ?? ''));
        $nameErr = '';
        if ($name === '') {
            $nameErr = 'Ingresá tu nombre.';
        } else {
            $name = preg_replace('/\s+/u', ' ', $name);
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80) {
                $nameErr = 'El nombre debe tener entre 2 y 80 caracteres.';
            } elseif (!preg_match("/^[\p{L}\p{N}\s\.'\-]+$/u", $name)) {
                $nameErr = 'El nombre contiene caracteres no válidos.';
            }
        }

        [$ok, $errors, $clean] = $svc->validateAndNormalize($_POST);

        if (!$ok) {
            $profile = [
                'name' => $name,
                'phone' => (string)($_POST['phone'] ?? ''),
                'birth_date' => (string)($_POST['birth_date'] ?? ''),
                'marketing_email_opt_in' => !empty($_POST['marketing_email_opt_in']) ? 1 : 0,
                'marketing_whatsapp_opt_in' => !empty($_POST['marketing_whatsapp_opt_in']) ? 1 : 0,
            ];

            if ($nameErr !== '') {
                $errors['name'] = $nameErr;
            }

            $user = $userRepo->findById($uid) ?: [];
            $user['name'] = $name;

            render_page('cuenta/perfil', [
                'pageId' => 'account_profile',
                'user' => $user,
                'profile' => $profile,
                'fiscalProfile' => $fiscalProfile,
                'profileBadgeHint' => $this->buildProfileBadgeHint($user, $profile),
                'errors' => $errors,
            ]);
            return;
        }

        if ($nameErr !== '') {
            $user = $userRepo->findById($uid) ?: [];
            $user['name'] = $name;

            $profile = [
                'phone' => $clean['phone'],
                'birth_date' => $clean['birth_date'],
                'marketing_email_opt_in' => $clean['marketing_email_opt_in'],
                'marketing_whatsapp_opt_in' => $clean['marketing_whatsapp_opt_in'],
            ];

            render_page('cuenta/perfil', [
                'pageId' => 'account_profile',
                'user' => $user,
                'profile' => $profile,
                'fiscalProfile' => $fiscalProfile,
                'profileBadgeHint' => $this->buildProfileBadgeHint($user, $profile),
                'errors' => ['name' => $nameErr],
            ]);
            return;
        }

        try {
            // Guarda nombre
            $userRepo->updateName($uid, $name);
            $_SESSION['uname'] = $name;

            $svc->saveProfile($uid, $clean);
            // Auditoría (si existe)
            if (function_exists('audit_log')) {
                audit_log('profile_updated', 'user', $uid, [
                    'name_changed' => true,
                    'has_phone' => $clean['phone'] !== '',
                    'has_birth_date' => $clean['birth_date'] !== '',
                ]);
            }
            flash('ok', 'Tus datos se guardaron correctamente.');
            redirect('/cuenta');
        } catch (\Throwable $e) {
            flash('error', 'No se pudo guardar tu perfil. Intentá nuevamente.');
            redirect('/cuenta/perfil');
        }
    }
}

