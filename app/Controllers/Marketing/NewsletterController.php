<?php
namespace App\Controllers\Marketing;

use App\Services\Marketing\EmailMarketingService;

final class NewsletterController
{
    public function subscribe(): void
    {
        // Rate limit básico anti-spam
        if (function_exists('rate_limit_or_fail')) {
            rate_limit_or_fail('newsletter:' . (function_exists('client_ip') ? client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0')), 20, 60);
        }

        if (function_exists('verify_csrf') && !verify_csrf()) {
            http_response_code(403);
            exit('CSRF');
        }

        // Honeypot opcional
        $hp = trim((string)($_POST['website'] ?? ''));
        if ($hp !== '') {
            // Fingimos éxito para bots
            if (function_exists('flash')) flash('ok', '¡Listo!');
            if (function_exists('redirect')) redirect('/');
            return;
        }

        $email = trim((string)($_POST['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (function_exists('flash')) flash('error', 'Email inválido.');
            if (function_exists('redirect')) redirect('/');
            return;
        }

        $svc = new EmailMarketingService(db());
        $res = $svc->subscribe($email, 'public');

        $double = $svc->doubleOptInEnabled();
        if (($res['status'] ?? '') === 'already') {
            flash('ok', 'Ya estás suscripto.');
        } elseif ($double) {
            flash('ok', 'Te enviamos un email para confirmar tu suscripción.');
        } else {
            flash('ok', '¡Gracias! Ya estás suscripto.');
        }

        redirect('/');
    }

    public function confirm(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $ok = (new EmailMarketingService(db()))->confirm($token);

        if ($ok) {
            flash('ok', 'Suscripción confirmada. ¡Bienvenido!');
        } else {
            flash('error', 'No se pudo confirmar. El link puede haber expirado.');
        }

        redirect('/');
    }

    public function unsubscribe(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $ok = (new EmailMarketingService(db()))->unsubscribe($token);

        if ($ok) {
            flash('ok', 'Listo. Te diste de baja del newsletter.');
        } else {
            flash('error', 'No se pudo procesar la baja.');
        }

        redirect('/');
    }
}
