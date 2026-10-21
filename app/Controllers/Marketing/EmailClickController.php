<?php
namespace App\Controllers\Marketing;

use App\Repositories\Marketing\EmailClickTokenRepository;
use PDO;

final class EmailClickController
{
    public function __construct(private PDO $db) {}

    public function click(): void
    {
        $token = (string)($_GET['t'] ?? '');
        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            redirect(url('/'));
            return;
        }

        $repo = new EmailClickTokenRepository($this->db);
        $row = $repo->find($token);
        if (!$row) {
            redirect(url('/'));
            return;
        }

        // Marcar click (idempotente)
        $repo->markClicked($token);

        // Atribución en sesión (se usa al crear checkout_intents / orders)
        $cid = (int)($row['campaign_id'] ?? 0);
        if ($cid > 0) {
            $_SESSION['email_campaign_id'] = $cid;
            $_SESSION['email_campaign_touch_at'] = date('Y-m-d H:i:s');
        }

        $dest = (string)($row['destination_url'] ?? '/');
        $safe = $this->sanitizeDestination($dest);
        redirect($safe);
    }

    private function sanitizeDestination(string $dest): string
    {
        $dest = trim($dest);
        if ($dest === '') return url('/');

        // Permitimos solo URLs internas (relativas) por seguridad.
        if (str_starts_with($dest, '/')) {
            return url($dest);
        }

        // Si viniera una URL absoluta, la bloqueamos.
        return url('/');
    }
}
