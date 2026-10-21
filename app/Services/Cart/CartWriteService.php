<?php
declare(strict_types=1);

namespace App\Services\Cart;

use App\Services\Cart\Legacy\LegacyCartEngine;
use PDO;

final class CartWriteService
{
    public function __construct(private PDO $db)
    {
    }

    /** @return array{0:bool,1:string} */
    public function add(int $productId, int $qty, ?int $variantId = null): array
    {
        return $this->legacy()->add($productId, $qty, $variantId);
    }

    /** @param array<string,string|int> $qtys */
    /** @return array{0:bool,1:string} */
    public function bulkUpdate(array $qtys): array
    {
        return $this->legacy()->bulkUpdate($qtys);
    }

    public function remove(int $productId, ?int $variantId = null): bool
    {
        return $this->legacy()->remove($productId, $variantId);
    }

    public function clear(): void
    {
        $this->legacy()->clear();
    }

    /** @param array<int,array{product_id:int,variant_id?:int,quantity:int}> $items */
    public function mergeItems(array $items): int
    {
        return $this->legacy()->mergeItems($items);
    }

    /** @param array<int,array{product_id:int,variant_id?:int,quantity:int}> $items */
    public function replaceWithItems(array $items): int
    {
        return $this->legacy()->replaceWithItems($items);
    }

    /** @return array{0:bool,1:string} */
    public function applyMergeDecision(string $action): array
    {
        return $this->legacy()->applyMergeDecision($action);
    }

    public function mergeAnonymousToUser(int $userId): void
    {
        $this->legacy()->mergeAnonymousToUser($userId);
    }

    public function prepareMergeOnLogin(int $userId): string
    {
        return $this->legacy()->prepareMergeOnLogin($userId);
    }

    private function legacy(): LegacyCartEngine
    {
        return new LegacyCartEngine($this->db);
    }
}
