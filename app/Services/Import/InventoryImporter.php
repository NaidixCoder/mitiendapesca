<?php
namespace App\Services\Import;

use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Catalog\ProductRepository;

final class InventoryImporter
{
    public function __construct(
        private InventoryRepository $inventory,
        private ProductRepository $products
    ) {}

        /** @return array{ok:bool,changes?:string[],error?:string,rollback?:array<string,mixed>} */
    public function apply(array $d, bool $dry): array
    {
        $sku = trim((string)($d['sku'] ?? ''));
        $prod = $this->products->findBySku($sku);
        if (!$prod) {
            // En dry-run, si el producto no existe todavía (pero será creado por la importación), no bloqueamos.
            if ($dry) return ['ok'=>true, 'changes'=>[]];
            return ['ok'=>false,'error'=>"Producto con sku '$sku' no existe"];
        }

        $stockRaw = $d['stock'] ?? null;
        $stock = ($stockRaw === '' || $stockRaw === null) ? null : (int)$stockRaw;
        $thrRaw = $d['low_stock_threshold'] ?? null;
        $thr   = ($thrRaw === '' || $thrRaw === null) ? 3 : (int)$thrRaw;
        $modeRaw = $d['modo_stock'] ?? null;
        $mode  = ($modeRaw === null || $modeRaw === '') ? null : strtolower(trim((string)$modeRaw));
        if ($stock !== null && !$mode) return ['ok'=>false,'error'=>"Falta modo_stock (suma|fija) para SKU '$sku'"];

        $prev = $this->inventory->getByProductId((int)$prod['id']);
        $prevStock = $prev ? (int)$prev['stock'] : 0;
        $prevThr   = $prev ? (int)$prev['low_stock_threshold'] : 3;

        $newStock = $prevStock;
        if ($stock !== null) {
            if ($mode==='suma') $newStock = $prevStock + (int)$stock;
            elseif ($mode==='fija') $newStock = (int)$stock;
        }

        $changes = [];
        if ($stock !== null) {
            $suffix = $mode ? " ($mode)" : "";
            $changes[] = "stock: {$prevStock} -> {$newStock}{$suffix}";
        }
        if ($thr !== $prevThr) {
            $changes[] = "umbral: {$prevThr} -> {$thr}";
        }

        if ($dry) return ['ok'=>true, 'changes'=>$changes,'rollback'=>null];

        $ok = $prev
            ? $this->inventory->updateByProductId((int)$prod['id'], $newStock, $thr)
            : $this->inventory->insert((int)$prod['id'], $newStock, $thr);

        return ['ok'=>$ok, 'changes'=>$changes,'rollback'=>['entity'=>'inventory_product','action'=>($prev?'update':'create'),'id'=>(int)$prod['id'],'before'=>['stock'=>$prevStock,'low_stock_threshold'=>$prevThr]]];
    }

}

