<?php
namespace App\Controllers\Auth;

use App\Services\Auth\EmailVerificationService;

class EmailVerificationController
{
    public function verify(): void
    {
        $token = $_GET['token'] ?? '';
        if (!$token) { redirect('/login'); return; }

        $svc = new EmailVerificationService(db());

        if ($svc->verifyToken($token)) {
            $currentUid = (int)($_SESSION['uid'] ?? 0);
            if ($currentUid > 0) {
                try {
                    $st = db()->prepare('SELECT email_verified, email_verified_at FROM users WHERE id = ? LIMIT 1');
                    $st->execute([$currentUid]);
                    $row = $st->fetch(\PDO::FETCH_ASSOC) ?: [];
                    $_SESSION['email_verified'] = (int)($row['email_verified'] ?? 0);
                    $_SESSION['email_verified_at'] = $row['email_verified_at'] ?? null;
                } catch (\Throwable $e) {
                    // No bloqueante: el checkout vuelve a sincronizar este dato.
                }
            }

            // Tu partial lee ok/warn/error (no "success")
            flash('ok', 'Email verificado correctamente.');
            redirect('/cuenta');
            return;
        }

        flash('error', 'Link inválido o vencido.');
        redirect('/login');
    }

    public function resend(): void
    {
        auth_required();
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        $ip  = client_ip();

        // Detectar si el cliente espera HTML (form submit) o JSON (fetch)
        $wantsHtml = function_exists('wants_html') ? wants_html() : true;

        // 3 / 30 min por usuario
        $retry = rate_limit_check('auth:verify_resend:uid:' . $uid, 3, 1800);
        if ($retry !== false) {
            http_response_code(429);
            header('Retry-After: ' . (int)$retry);

            if ($wantsHtml) {
                render_page('errors/429', [
                    'pageId' => 'error-429',
                    'reason' => 'email_resend_limit',
                    'retry'  => (int)$retry,
                ]);
                return;
            }

            json([
                'ok' => false,
                'error' => 'too_many_requests',
                'reason' => 'email_resend_limit',
                'retry_after' => (int)$retry,
            ]);
            return;
        }

        // 10 / 30 min por IP
        $retryIp = rate_limit_check('auth:verify_resend:ip:' . $ip, 10, 1800);
        if ($retryIp !== false) {
            http_response_code(429);
            header('Retry-After: ' . (int)$retryIp);

            if ($wantsHtml) {
                render_page('errors/429', [
                    'pageId' => 'error-429',
                    'reason' => 'email_resend_limit',
                    'retry'  => (int)$retryIp,
                ]);
                return;
            }

            json([
                'ok' => false,
                'error' => 'too_many_requests',
                'reason' => 'email_resend_limit',
                'retry_after' => (int)$retryIp,
            ]);
            return;
        }

        // Intento de envío (respuesta neutra)
        try {
            $svc = new EmailVerificationService(db());
            $svc->sendForUser($uid);
        } catch (\Throwable $e) {
            error_log('[EmailVerificationController::resend] ' . $e->getMessage());
        }

        if ($wantsHtml) {
            flash('ok', 'Te reenviamos el email de verificación. Revisá tu casilla.');
            redirect('/cuenta'); // o a la página actual, si después lo queremos mejorar
            return;
        }

        json(['ok' => true]);
    }

}


