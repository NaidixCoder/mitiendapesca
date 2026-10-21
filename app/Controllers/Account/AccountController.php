<?php
namespace App\Controllers\Account;

use PDO;
use App\Repositories\Account\AddressRepository;
use App\Repositories\Account\ProfileRepository;
use App\Repositories\Account\OrderRepository;
use App\Repositories\Account\ProductFavoriteRepository;
use App\Services\Account\OrderService;
use App\Services\Account\LoyaltyService;

class AccountController
{
    public function index(): void
    {
        auth_required('/cuenta');

        // ✅ Solución pro: refrescar estado real desde DB
        $uid = (int)($_SESSION['uid'] ?? 0);
        $userMeta = [
            'email_verified' => (int)($_SESSION['email_verified'] ?? 0),
            'created_at'     => null,
            'last_login_at'  => null,
            'avatar_url'     => (string)($_SESSION['uavatar'] ?? ''),
        ];
        if ($uid > 0) {
            try {
                /** @var PDO $db */
                $db = db();

                $stmt = $db->prepare(
                    'SELECT name, email, role, avatar_url, email_verified, email_verified_at
                     , created_at, last_login_at
                     FROM users
                     WHERE id = ?
                     LIMIT 1'
                );
                $stmt->execute([$uid]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

                if ($row) {
                    // Mantener consistencia con el resto del proyecto
                    $_SESSION['uname'] = (string)($row['name'] ?? ($_SESSION['uname'] ?? ''));
                    $_SESSION['email'] = (string)($row['email'] ?? ($_SESSION['email'] ?? ''));
                    $_SESSION['urole'] = (string)($row['role'] ?? ($_SESSION['urole'] ?? 'customer'));

                    $avatar = (string)($row['avatar_url'] ?? '');
                    if ($avatar !== '') {
                        $_SESSION['uavatar'] = $avatar;
                    }

                    // Flags de verificación (lo que usa el banner)
                    $_SESSION['email_verified'] = (int)($row['email_verified'] ?? 0);
                    $_SESSION['email_verified_at'] = $row['email_verified_at'] ?? null;

                    // Meta para la vista (evitar queries en la view)
                    $userMeta['email_verified'] = (int)($_SESSION['email_verified'] ?? 0);
                    $userMeta['created_at']     = $row['created_at'] ?? null;
                    $userMeta['last_login_at']  = $row['last_login_at'] ?? null;
                    $userMeta['avatar_url']     = (string)($row['avatar_url'] ?? ($_SESSION['uavatar'] ?? ''));
                } else {
                    // Si el usuario no existe (DB reseteada / inconsistencia), cerramos sesión
                    $_SESSION = [];
                    session_regenerate_id(true);
                    flash('error', 'Tu sesión no es válida. Iniciá sesión nuevamente.');
                    redirect('/login');
                }
            } catch (\Throwable $e) {
                // No rompemos la página si falla DB, pero dejamos log
                if (function_exists('log_error')) {
                    log_error('AccountController@index refresh user: ' . $e->getMessage());
                }
            }
        }

        // Direcciones (estado real)
        $addressCount = 0;
        $defaultAddress = null;
        try {
            $repo = new AddressRepository(db());
            $addressCount = $repo->countByUser($uid);
            $defaultAddress = $addressCount > 0 ? $repo->getDefaultByUser($uid) : null;
        } catch (\Throwable $e) {
            // Si todavía no existe la tabla o falla DB, no rompemos /cuenta
        }

        // Pedidos (estado real)
        $ordersCount = 0;
        $latestOrder = null;
        $favoritesCount = 0;
        try {
            $oRepo = new OrderRepository(db());
            if ($oRepo->tableExists()) {
                $ordersCount = $oRepo->countByUser($uid);
                if ($ordersCount > 0) {
                    $oSvc = new OrderService($oRepo);
                    $latestOrder = $oSvc->latestForUserView($uid);
                }
            }
        } catch (\Throwable $e) {
            $ordersCount = 0;
            $latestOrder = null;
        }

        try {
            $favoritesCount = (new ProductFavoriteRepository(db()))->countByUser($uid);
        } catch (\Throwable $e) {
            $favoritesCount = 0;
        }

        // Perfil (estado real)
        $profile = null;
        $profileHasPhone = false;
        $profileHasBirth = false;
        try {
            $pRepo = new ProfileRepository(db());
            $profile = $pRepo->findByUserId($uid);
            $profile = $this->syncProfilePhoneFromAddressesIfEmpty($uid, $profile, $defaultAddress);
            if ($profile) {
                $profileHasPhone = !empty($profile['phone']);
                $profileHasBirth = !empty($profile['birth_date']);
            }
        } catch (\Throwable $e) {
            // Si aún no existe la tabla o falla DB, no rompemos /cuenta
        }

        // Programa de lealtad (server-render, sin lógica en la vista)
        $loyalty = [
            'enabled' => false,
            'points' => 0,
            'tier' => 'Bronce',
            'paid_total' => 0,
            'paid_orders_count' => 0,
            'rate_label' => '1 punto cada $ 1.000',
            'progress_pct' => 0,
            'next_tier' => 'Plata',
            'next_target' => 100000,
            'to_next_tier' => 100000,
            'pill_class' => '',
        ];
        try {
            $loySvc = new LoyaltyService(db());
            $loyalty = $loySvc->getProgressForUser($uid);
        } catch (\Throwable $e) {
            // best-effort
        }

 
        // Formateos para la vista (mantenerla "tonta")
        $profilePhone = ($profile && !empty($profile['phone'])) ? (string)$profile['phone'] : '—';
        $profileBirthFmt = ($profile && !empty($profile['birth_date'])) ? date('d/m/Y', strtotime((string)$profile['birth_date'])) : '—';
        // Perfil "listo" = teléfono cargado (nacimiento queda opcional)
        $profileComplete = $profileHasPhone;

        // Verificación de email (fuente: refresh de DB -> session -> fallback)
        $emailVerified = ((int)($_SESSION['email_verified'] ?? ($userMeta['email_verified'] ?? 0))) === 1;

        // Estado global de la cuenta (para UX: mostrar qué falta)
        // IMPORTANTE: mantenemos $accountMissing como lista de strings por compatibilidad,
        // pero además exponemos $accountMissingItems con href para CTAs.
        $accountMissing = [];
        $accountMissingItems = [];

        if ($addressCount <= 0) {
            $accountMissing[] = 'Agregar dirección';
            $accountMissingItems[] = [
                'label' => 'Agregar dirección',
                'href'  => base_url('/cuenta/direcciones/nueva'),
            ];
        }
        if (!$profileHasPhone) {
            $accountMissing[] = 'Agregar teléfono';
            $accountMissingItems[] = [
                'label' => 'Agregar teléfono',
                'href'  => base_url('/cuenta/perfil'),
            ];
        }
        if (!$emailVerified) {
            $accountMissing[] = 'Verificar email';
            $accountMissingItems[] = [
                'label' => 'Verificar email',
                'href'  => base_url('/cuenta/seguridad'),
            ];
        }

        $accountMissingCount = count($accountMissing);

        $addressSummary = '—';
        if (is_array($defaultAddress)) {
            $street = trim((string)($defaultAddress['street'] ?? '') . ' ' . (string)($defaultAddress['number'] ?? ''));
            $city   = (string)($defaultAddress['city'] ?? '');
            $state  = (string)($defaultAddress['state'] ?? '');
            $addressSummary = trim($street);
            if ($city || $state) {
                $addressSummary .= ' · ' . trim($city . ($state ? ', ' . $state : ''));
            }
        }

        // CTA dinámico (server-render)
        // Prioridad: completar cuenta (dirección/teléfono/verificación) → primera compra → pedidos
        if ($accountMissingCount > 0) {
            $cta = [
                'title' => 'Completá tus datos',
                'desc'  => 'Dejá tu cuenta lista para comprar en segundos. Recomendado: dirección y teléfono.',
                'label' => 'Completar datos',
                'href'  => base_url('/cuenta/completar'),
            ];
        } elseif ($ordersCount <= 0) {
            $cta = [
                'title' => 'Tu primera compra te está esperando',
                'desc'  => 'Explorá el catálogo y empezá a sumar puntos en el programa de lealtad.',
                'label' => 'Explorar productos',
                'href'  => base_url('/productos'),
            ];
        } else {
            $cta = [
                'title' => 'Seguimiento de pedidos',
                'desc'  => 'Revisá el estado de tus compras y el historial.',
                'label' => 'Ver pedidos',
                'href'  => base_url('/cuenta/pedidos'),
            ];
        }

        render_page('cuenta/index', [
            'pageId' => 'account',
            'userMeta' => $userMeta,
            'profile' => $profile,
            'profilePhone' => $profilePhone,
            'profileBirthFmt' => $profileBirthFmt,
            'profileComplete' => $profileComplete,
            'profileHasPhone' => $profileHasPhone,
            'profileHasBirth' => $profileHasBirth,
            'accountMissing' => $accountMissing,
            'accountMissingItems' => $accountMissingItems,
            'accountMissingCount' => $accountMissingCount,
            'addressCount' => $addressCount,
            'defaultAddress' => $defaultAddress,
            'addressSummary' => $addressSummary,
            'ordersCount' => $ordersCount,
            'latestOrder' => $latestOrder,
            'favoritesCount' => $favoritesCount,
            'cta' => $cta,
            'loyalty' => $loyalty,
        ]);
    }


    public function complete(): void
    {
        auth_required('/cuenta/completar');

        $uid = (int)($_SESSION['uid'] ?? 0);

        // Estado real (sin lógica en la vista)
        $addressCount = 0;
        $defaultAddress = null;
        $profileHasPhone = false;
        $emailVerified = ((int)($_SESSION['email_verified'] ?? 0)) === 1;

        try {
            $aRepo = new AddressRepository(db());
            $addressCount = $aRepo->countByUser($uid);
            $defaultAddress = $addressCount > 0 ? $aRepo->getDefaultByUser($uid) : null;
        } catch (\Throwable $e) {
            $addressCount = 0;
            $defaultAddress = null;
        }

        try {
            $pRepo = new ProfileRepository(db());
            $profile = $pRepo->findByUserId($uid);
            $profile = $this->syncProfilePhoneFromAddressesIfEmpty($uid, $profile, $defaultAddress);
            $profileHasPhone = $profile ? !empty($profile['phone']) : false;
        } catch (\Throwable $e) {
            $profileHasPhone = false;
        }

        // Pasos (orden por impacto en compra)
        $steps = [];
        $steps[] = [
            'key' => 'address',
            'title' => 'Dirección de entrega',
            'desc' => 'Agregá una dirección principal para que el checkout sea más rápido.',
            'done' => ($addressCount > 0),
            'label' => ($addressCount > 0 ? 'Gestionar' : 'Agregar'),
            'href' => base_url($addressCount > 0 ? '/cuenta/direcciones' : '/cuenta/direcciones/nueva'),
        ];
        $steps[] = [
            'key' => 'phone',
            'title' => 'Teléfono',
            'desc' => 'Lo usamos para coordinación de envíos y WhatsApp (opcional, pero recomendado).',
            'done' => (bool)$profileHasPhone,
            'label' => ($profileHasPhone ? 'Editar' : 'Agregar'),
            'href' => base_url('/cuenta/perfil'),
        ];
        $steps[] = [
            'key' => 'email',
            'title' => 'Verificación de email',
            'desc' => 'Mejora la seguridad de tu cuenta y evita problemas de acceso.',
            'done' => (bool)$emailVerified,
            'label' => ($emailVerified ? 'Listo' : 'Verificar'),
            'href' => base_url('/cuenta/seguridad'),
        ];

        $nextStep = null;
        foreach ($steps as $s) {
            if (empty($s['done'])) {
                $nextStep = $s;
                break;
            }
        }
        if (!$nextStep) {
            $nextStep = [
                'title' => 'Todo listo',
                'desc' => 'Tu cuenta ya está completa.',
                'label' => 'Volver a Mi cuenta',
                'href' => base_url('/cuenta'),
                'done' => true,
            ];
        }

        render_page('cuenta/complete', [
            'pageId' => 'account_complete',
            'steps' => $steps,
            'nextStep' => $nextStep,
        ]);
    }

    public function logout(): void
    {
        verify_csrf_or_abort();

        // Si existe token "Recordarme", invalidarlo en DB + cookie
        if (function_exists('clear_remember_me')) {
            clear_remember_me();
        }
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        session_start();

        flash('ok', 'Sesión cerrada.');
        redirect('/');
    }

    public function clearSessions(): void
    {
        auth_required('/cuenta');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        try {
            /** @var \PDO $db */
            $db = db();

            // Incrementa versión (revoca otras sesiones)
            $db->prepare('UPDATE users SET session_version = session_version + 1 WHERE id = ?')->execute([$uid]);

            // Obtiene nuevo valor y lo aplica a esta sesión (para NO desloguear acá)
            $stmt = $db->prepare('SELECT session_version FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$uid]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
            $_SESSION['sv'] = (int)($row['session_version'] ?? ((int)($_SESSION['sv'] ?? 1) + 1));

            // Invalida "recordarme" (extra)
            if (function_exists('clear_remember_me')) {
                clear_remember_me();
            }

            // Endurecer (anti fixation)
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }

            flash('ok', 'Se cerraron las otras sesiones.');
        } catch (\Throwable $e) {
            if (function_exists('log_error')) log_error('clearSessions failed: '.$e->getMessage());
            flash('error', 'No se pudo cerrar otras sesiones. Intentá nuevamente.');
        }

        redirect('/cuenta');
    }

    private function syncProfilePhoneFromAddressesIfEmpty(int $userId, ?array $profile, ?array $defaultAddress): ?array
    {
        if ($userId <= 0) {
            return $profile;
        }

        $profilePhone = trim((string)($profile['phone'] ?? ''));
        if ($profilePhone !== '') {
            return $profile;
        }

        $phone = trim((string)($defaultAddress['phone'] ?? ''));
        if ($phone === '') {
            try {
                $addressRepo = new AddressRepository(db());
                foreach ($addressRepo->listByUser($userId) as $address) {
                    $candidate = trim((string)($address['phone'] ?? ''));
                    if ($candidate !== '') {
                        $phone = $candidate;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                if (function_exists('log_error')) {
                    log_error('AccountController@syncProfilePhoneFromAddressesIfEmpty address lookup: ' . $e->getMessage());
                }
                return $profile;
            }
        }

        if ($phone === '') {
            return $profile;
        }

        try {
            $profileRepo = new ProfileRepository(db());
            $payload = [
                'phone' => $phone,
                'birth_date' => (string)($profile['birth_date'] ?? ''),
                'marketing_email_opt_in' => (int)($profile['marketing_email_opt_in'] ?? 0),
                'marketing_whatsapp_opt_in' => (int)($profile['marketing_whatsapp_opt_in'] ?? 0),
            ];
            $profileRepo->upsert($userId, $payload);

            $profile = $profile ?? ['user_id' => $userId];
            $profile['phone'] = $phone;
            $profile['birth_date'] = $payload['birth_date'] !== '' ? $payload['birth_date'] : null;
            $profile['marketing_email_opt_in'] = $payload['marketing_email_opt_in'];
            $profile['marketing_whatsapp_opt_in'] = $payload['marketing_whatsapp_opt_in'];
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('AccountController@syncProfilePhoneFromAddressesIfEmpty upsert: ' . $e->getMessage());
            }
        }

        return $profile;
    }
}
