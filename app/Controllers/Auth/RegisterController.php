<?php
namespace App\Controllers\Auth;

use PDO;
use App\Services\Auth\EmailVerificationService;
use App\Services\Marketing\EmailMarketingService;

class RegisterController
{
    public function index(): void
    {
        render_page('auth/registro', ['pageId' => 'auth-register']);
    }

    public function post(): void
    {
        verify_csrf_or_abort();

        // Si el request viene por fetch/AJAX devolvemos JSON (progressive enhancement)
        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $xhr    = (string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
        $wantsJson = (stripos($accept, 'application/json') !== false) || ($xhr === 'XMLHttpRequest');

        // Anti-abuso: frena bots que crean cuentas en masa
        rate_limit_or_fail('auth:register:ip:' . client_ip(), 5, 3600); // 5 / hora
        if (function_exists('antibot_check')) {
            $ab = antibot_check('register', 2);

            if ($ab === 'fast_submit') {
                // UX: en fetch/AJAX devolvemos JSON 429 (para no renderizar HTML)
                if ($wantsJson && function_exists('json')) {
                    json([
                        'ok' => false,
                        'errors' => ['general' => 'Esperá un instante y volvé a intentar.'],
                        'error' => 'fast_submit',
                    ], 429);
                    return;
                }

                flash('warn', 'Esperá un instante y volvé a intentar.');
                http_response_code(429);
                render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'fast_submit']);
                return;
            }

            if ($ab === 'invalid') {
                $msg = 'No se pudo crear la cuenta. Revisá los datos e intentá nuevamente.';

                if ($wantsJson && function_exists('json')) {
                    json(['ok' => false, 'errors' => ['general' => $msg], 'error' => 'invalid'], 400);
                    return;
                }

                http_response_code(400);
                flash('error', $msg);
                redirect('/registro');
                return;
            }
        }

        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = (string)($_POST['password'] ?? '');
        $pass2 = (string)($_POST['password_confirm'] ?? '');

        $termsAccepted = !empty($_POST['terms']);
        $newsletterOptin = ((string)($_POST['newsletter_optin'] ?? '1') === '1');

        $errors = [];

        if ($name === '')  { $errors['name']  = 'Ingresá tu nombre.'; }
        if ($email === '') { $errors['email'] = 'Ingresá tu email.'; }
        if ($pass === '')  { $errors['password'] = 'Ingresá una clave.'; }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido.';
        }

        if ($pass !== '' && strlen($pass) < 8) {
            $errors['password'] = 'La clave debe tener al menos 8 caracteres.';
        }

        if ($pass !== '') {
            if ($pass2 === '') {
                $errors['password_confirm'] = 'Repetí la clave.';
            } elseif ($pass !== $pass2) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden.';
            }
        }

        if (!$termsAccepted) {
            $errors['terms'] = 'Debés aceptar los Términos y la Privacidad para crear una cuenta.';
        }

        if ($errors) {
            // Persistimos datos de input (sin passwords) para repoblar el form
            if (function_exists('remember_input')) {
                remember_input([
                    'name'  => $name,
                    'email' => $email,
                    'terms' => $termsAccepted ? '1' : '',
                    'newsletter_optin' => $newsletterOptin ? '1' : '0',
                ]);
            }

            flash('form_errors', $errors);
            flash('error', $errors['terms'] ?? reset($errors));

            if ($wantsJson && function_exists('json')) {
                json(['ok' => false, 'errors' => $errors], 422);
                return;
            }

            redirect('/registro');
            return;
        }

        $hash = password_hash($pass, PASSWORD_BCRYPT);

        $termsVersion   = (string)env('TERMS_VERSION', date('Y-m-d'));
        $privacyVersion = (string)env('PRIVACY_VERSION', date('Y-m-d'));

        /** @var PDO $pdo */
        $pdo = db();
        $pdo->beginTransaction();

        // Atribución marketing: si el usuario llegó desde /promo/* guardamos campaign_id (best-effort)
        $campaignId = 0;
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $campaignId = (int)($_SESSION['marketing_campaign_id'] ?? 0);
            }
        } catch (\Throwable $e) {
            $campaignId = 0;
        }

        $usersHasCampaignCol = false;
        if ($campaignId > 0) {
            try {
                $stc = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS\n                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='marketing_campaign_id'");
                $stc->execute();
                $usersHasCampaignCol = ((int)$stc->fetchColumn() > 0);
            } catch (\Throwable $e) {
                $usersHasCampaignCol = false;
            }
        }

        try {
            // Verificar email duplicado
            $stmt = $pdo->prepare(
                'SELECT id FROM users WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                $errors = ['email' => 'Ese email ya está registrado.'];

                if (function_exists('remember_input')) {
                    remember_input([
                        'name'  => $name,
                        'email' => $email,
                        'terms' => $termsAccepted ? '1' : '',
                        'newsletter_optin' => $newsletterOptin ? '1' : '0',
                    ]);
                }
                flash('form_errors', $errors);
                flash('error', $errors['email']);

                if ($wantsJson && function_exists('json')) {
                    json(['ok' => false, 'errors' => $errors], 422);
                    return;
                }

                redirect('/registro');
                return;
            }

            // Crear usuario (email_verified queda en 0 por default)
            $cols = 'name, email, password_hash, created_at, terms_accepted_at, terms_version, privacy_version';
            $vals = ':name, :email, :hash, NOW(), NOW(), :terms_version, :privacy_version';
            $params = [
                ':name'  => $name,
                ':email' => $email,
                ':hash'  => $hash,
                ':terms_version'   => $termsVersion,
                ':privacy_version' => $privacyVersion,
            ];

            if ($usersHasCampaignCol && $campaignId > 0) {
                $cols .= ', marketing_campaign_id';
                $vals .= ', :mkt_campaign_id';
                $params[':mkt_campaign_id'] = $campaignId;
            }

            $stmt = $pdo->prepare("INSERT INTO users ({$cols}) VALUES ({$vals})");
            $stmt->execute($params);

            $uid = (int)$pdo->lastInsertId();

            // Enviar email de verificación
            $verif = new EmailVerificationService($pdo);
            $verif->sendForUser($uid);

            $pdo->commit();

            if (function_exists('clear_old')) {
                clear_old();
            }

            // Sesión inmediata (no bloqueamos login)
            session_regenerate_id(true);
            $_SESSION['uid']   = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['name']  = $name;
            $_SESSION['urole'] = 'customer';
            $_SESSION['sv'] = 1;

            // Newsletter (ofertas): opt-in por defecto, best-effort (no bloquea el registro)
            if ($newsletterOptin) {
                try {
                    (new EmailMarketingService($pdo))->subscribe($email, 'register');
                } catch (\Throwable $e) {
                    if (function_exists('log_error')) {
                        log_error('Newsletter subscribe (register) failed: ' . $e->getMessage());
                    }
                }
            }

            flash(
                'ok',
                'Cuenta creada con éxito. Te enviamos un email para verificar tu dirección.'
            );

            $dest = (string)($_SESSION['back_to'] ?? '/cuenta');
            unset($_SESSION['back_to']);

            // Normalizar destino a path interno (por seguridad y para evitar URLs mal formadas)
            if (!preg_match('#^https?://#i', $dest)) {
                if ($dest === '' || $dest[0] !== '/') {
                    $dest = '/' . ltrim($dest, '/');
                }
            }

            if ($wantsJson && function_exists('json')) {
                // Para front-end (fetch) devolvemos un path interno (el JS lo resuelve con la base real en subcarpeta)
                $redir = $dest;
                json(['ok' => true, 'redirect' => $redir], 200);
                return;
            }

            redirect($dest);
            return;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (function_exists('log_error')) {
                log_error('Register error: ' . $e->getMessage());
            }

            flash('error', 'No se pudo crear la cuenta. Intentá más tarde.');

            if ($wantsJson && function_exists('json')) {
                json(['ok' => false, 'errors' => ['general' => 'No se pudo crear la cuenta. Intentá más tarde.']], 500);
                return;
            }

            redirect('/registro');
            return;
        }
    }
}


