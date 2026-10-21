<?php
declare(strict_types=1);

namespace App\Services\Cart;

use App\Services\Cart\Legacy\LegacyCartEngine;
use PDO;

final class CartReadService
{
    public function __construct(private PDO $db)
    {
    }

    public function getCartView(): array
    {
        return $this->legacy()->getCartView();
    }

    public function raw(): array
    {
        return $this->legacy()->raw();
    }

    public function hasItems(): bool
    {
        return $this->legacy()->hasItems();
    }

    public function getMergePendingInfo(): ?array
    {
        return $this->legacy()->getMergePendingInfo();
    }

    public function syncSessionCount(): void
    {
        $this->legacy();
    }

    private function legacy(): LegacyCartEngine
    {
        return new LegacyCartEngine($this->db);
    }
}
