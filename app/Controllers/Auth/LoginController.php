<?php
namespace App\Controllers\Auth;

use App\Services\Auth\Auth;
use App\Services\Cart\CartWriteService;
use Throwable;

class LoginController
{
    public function index(): void
    {
        // pageId opcional por si usas loader JS por data-page
        $next = sanitize_next_url((string)($_GET['next'] ?? ''), '');
        render_page('auth/login', ['pageId' => 'auth-google', 'next' => $next]);
    }

    public function post(): void
    {
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        // Anti-bot (honeypot + tiempo mínimo, sin JS)
        if (function_exists('antibot_check')) {
            $ab = antibot_check('login', 2);
            if ($ab === 'fast_submit') {
                // No es fuerza bruta: es submit demasiado rápido (Enter apenas carga)
                flash('warn', 'Esperá un instante y volvé a intentar.');
                http_response_code(429);
                render_page('errors/429', ['pageId' => 'error-429', 'reason' => 'fast_submit']);
                return;
            }
            if ($ab === 'invalid') {
                // Neutro (anti-bot / form raro)
                http_response_code(400);
                flash('error', 'No se pudo iniciar sesión. Revisá tus datos e intentá nuevamente.');
                redirect(base_url('/login'));
                return;
            }
        }

        // Anti-abuso (fuerza bruta)
        // - IP: limita intentos por ventana
        // - Email+IP: limita intentos sobre un mismo usuario
        rate_limit_or_fail('auth:login:ip:' . client_ip(), 10, 600); // 10 / 10min
        $emailForKey = trim((string)($_POST['email'] ?? ''));
        if ($emailForKey !== '') {
            rate_limit_or_fail('auth:login:email_ip:' . mb_strtolower($emailForKey) . ':' . client_ip(), 5, 600);
        }

        [$ok, $errors] = Auth::attemptFromPost();

        if ($ok) {
            // âœ… cargar estado de verificación en sesión (para banner)
            try {
                $uid = (int)($_SESSION['uid'] ?? 0);
                if ($uid > 0) {
                    $stmt = db()->prepare('SELECT email_verified, password_changed_at, session_version FROM users WHERE id = ? LIMIT 1');
                    $stmt->execute([$uid]);
	                    $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
                    $_SESSION['email_verified'] = (int)($row['email_verified'] ?? 0);
                    $_SESSION['_pwd_changed_at'] = (string)($row['password_changed_at'] ?? '');
                    $_SESSION['sv'] = (int)($row['session_version'] ?? 1);
	                }
            } catch (\Throwable $e) {
                // no bloqueamos login por esto
            }

            // âœ… endurecer sesión (anti session fixation)
            if (session_status() === PHP_SESSION_ACTIVE) {
                @session_regenerate_id(true);
            }

            if (!empty($_POST['remember'])) {
                set_remember_me((int)($_SESSION['uid'] ?? 0), 30);
            }

            if (function_exists('auth_metrics_log')) {
                auth_metrics_log('login_success', 'password', null, (int)($_SESSION['uid'] ?? 0), (string)($_SESSION['email'] ?? ''));
            }

// ðŸ›’ Carrito: si hay conflicto real (carrito guardado + carrito como visitante), pedimos decisión en /carrito
try {
    $cartDecision = (new CartWriteService(db()))->prepareMergeOnLogin($uid);
    if ($cartDecision === 'conflict') {
        redirect('/carrito?merge=1');
        return;
    }
} catch (\Throwable $e) {
    // best effort
}

            // Destino post-login (prioridad: next explícito, luego back_to en sesión)
            $dest = sanitize_next_url((string)($_POST['next'] ?? ''), '');
            if ($dest === '') {
                $dest = sanitize_next_url(Auth::intended('/cuenta'), '/cuenta');
            }

            redirect($dest);
            return;
        }

        // Respuesta neutra (anti-enumeración)
        if (function_exists('auth_metrics_log')) {
            $email = (string)($_POST['email'] ?? '');
            auth_metrics_log('login_failed', 'password', 'invalid_credentials', null, $email);
        }

        flash('error', 'No se pudo iniciar sesión. Revisá tus datos e intentá nuevamente.');
        redirect(base_url('/login'));
    }
}

