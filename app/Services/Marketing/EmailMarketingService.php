<?php
namespace App\Services\Marketing;

use App\Mail\Core\Mailer;
use App\Repositories\Marketing\EmailSubscriberRepository;
use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class EmailMarketingService
{
    public function __construct(private PDO $db) {}

    public function doubleOptInEnabled(): bool
    {
        // Prioridad: setting en DB (Ajustes) -> .env fallback
        $v = null;
        try {
            $v = (new AppSettingsRepository($this->db))->get('email_marketing_double_optin', null);
        } catch (\Throwable $e) {
            $v = null;
        }
        if ($v === null) {
            $v = (string)env('EMAIL_MARKETING_DOUBLE_OPTIN', '0');
        }
        return in_array(strtolower(trim((string)$v)), ['1','true','yes','on'], true);
    }

    /**
     * Suscribe o re-suscribe un email.
     * Devuelve ['status' => ..., 'subscriber' => ...].
     */
    public function subscribe(string $email, ?string $source = null): array
    {
        $repo = new EmailSubscriberRepository($this->db);
        $email = strtolower(trim($email));

        $existing = $repo->findByEmail($email);
        $double = $this->doubleOptInEnabled();

        // Tokens: siempre generamos unsubscribe_token; confirm_token solo si pending
        $unsubscribeToken = $existing['unsubscribe_token'] ?? $this->token();
        $status = $double ? 'pending' : 'subscribed';
        $confirmToken = $double ? $this->token() : null;

        // Si ya estaba suscripto y no usamos double opt-in, no cambiamos estado.
        if ($existing && ($existing['status'] ?? '') === 'subscribed' && !$double) {
            return ['status' => 'already', 'subscriber' => $existing];
        }

        $subscriber = $repo->upsertSubscribe(
            $email,
            $status,
            $confirmToken,
            $unsubscribeToken,
            null,
            $source
        );

        if ($double) {
            $this->sendConfirm($subscriber);
        }

        return ['status' => $double ? 'pending' : 'subscribed', 'subscriber' => $subscriber];
    }

    public function confirm(string $token): bool
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 32) return false;
        return (new EmailSubscriberRepository($this->db))->confirmByToken($token);
    }

    public function unsubscribe(string $token): bool
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 32) return false;
        return (new EmailSubscriberRepository($this->db))->unsubscribeByToken($token);
    }

    private function sendConfirm(array $subscriber): void
    {
        $email = (string)($subscriber['email'] ?? '');
        $token = (string)($subscriber['confirm_token'] ?? '');
        $unsub = (string)($subscriber['unsubscribe_token'] ?? '');
        if ($email === '' || $token === '' || $unsub === '') return;

        $confirmUrl = url('/newsletter/confirm?token=' . urlencode($token));
        $unsubUrl   = url('/newsletter/unsubscribe?token=' . urlencode($unsub));

        $content = Mailer::render('newsletter_confirm', [
            'confirmUrl' => $confirmUrl,
            'unsubUrl' => $unsubUrl,
        ]);
        $html = Mailer::wrapLayout('Confirmá tu suscripción', $content);

        Mailer::sendHtml($email, 'Confirmá tu suscripción', $html);
    }

    private function token(): string
    {
        return hash('sha256', random_bytes(32));
    }
}
