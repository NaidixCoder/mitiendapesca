<?php
namespace App\Controllers\Account;

use App\Services\Account\LoyaltyService;

final class LoyaltyController
{
    // GET /cuenta/lealtad
    public function index(): void
    {
        auth_required('/cuenta');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $svc = new LoyaltyService(db());
        $detail = $svc->getDetailForUser($uid, 25);

        render_page('cuenta/lealtad', [
            'loyalty' => $detail,
        ]);
    }
}
