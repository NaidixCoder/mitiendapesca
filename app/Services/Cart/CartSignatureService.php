<?php
declare(strict_types=1);

namespace App\Services\Cart;

use App\Services\Cart\Legacy\LegacyCartEngine;

final class CartSignatureService
{
    /**
     * @param array<int,array<string,mixed>> $items
     */
    public static function build(array $items): string
    {
        return LegacyCartEngine::buildCartSignature($items);
    }
}
