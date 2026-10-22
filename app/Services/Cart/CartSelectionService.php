<?php
declare(strict_types=1);

namespace App\Services\Cart;

final class CartSelectionService
{
    private const SESSION_KEY = 'cart_selected_keys';

    /**
     * @param array<string,mixed> $cart
     * @return array<int,string>
     */
    public function selectedKeys(array $cart): array
    {
        $available = $this->availableKeys($cart);
        if ($available === []) {
            $this->clear();
            return [];
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($stored)) {
            $state = array_fill_keys($available, true);
            $_SESSION[self::SESSION_KEY] = $state;
            return $available;
        }

        $state = [];
        foreach ($stored as $key => $value) {
            if (is_int($key)) {
                $normalized = trim((string)$value);
                if ($normalized !== '') {
                    $state[$normalized] = true;
                }
                continue;
            }

            $normalized = trim((string)$key);
            if ($normalized !== '') {
                $state[$normalized] = !empty($value);
            }
        }

        foreach ($available as $key) {
            if (!array_key_exists($key, $state)) {
                $state[$key] = true;
            }
        }

        $state = array_intersect_key($state, array_flip($available));
        $_SESSION[self::SESSION_KEY] = $state;

        $selected = [];
        foreach ($available as $key) {
            if (!empty($state[$key])) {
                $selected[] = $key;
            }
        }

        return $selected;
    }

    /**
     * @param array<string,mixed> $cart
     * @param array<int|string,mixed> $keys
     * @return array<int,string>
     */
    public function save(array $cart, array $keys): array
    {
        $available = $this->availableKeys($cart);
        if ($available === []) {
            $this->clear();
            return [];
        }

        $requested = array_flip(array_map('strval', $keys));
        $state = [];
        $selected = [];
        foreach ($available as $key) {
            $isSelected = isset($requested[$key]);
            $state[$key] = $isSelected;
            if ($isSelected) {
                $selected[] = $key;
            }
        }

        $_SESSION[self::SESSION_KEY] = $state;

        return $selected;
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<string,mixed>
     */
    public function apply(array $cart): array
    {
        $selectedKeys = array_flip($this->selectedKeys($cart));
        $items = [];
        $subtotal = 0;
        $totalQty = 0;

        foreach ((array)($cart['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = $this->itemKey($item);
            if ($key === '' || !isset($selectedKeys[$key])) {
                continue;
            }

            $items[] = $item;
            $qty = max(0, (int)($item['qty'] ?? 0));
            $price = (int)($item['price'] ?? 0);
            $subtotal += $price * $qty;
            $totalQty += $qty;
        }

        $selectedCart = $cart;
        $selectedCart['items'] = $items;
        $selectedCart['subtotal'] = $subtotal;
        $selectedCart['total_qty'] = $totalQty;
        $selectedCart['items_count'] = count($items);

        return $selectedCart;
    }

    public function clear(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @param array<string,mixed> $cart
     * @return array<int,string>
     */
    private function availableKeys(array $cart): array
    {
        $keys = [];
        foreach ((array)($cart['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (!$this->isItemSelectable($item)) {
                continue;
            }

            $key = $this->itemKey($item);
            if ($key !== '') {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param array<string,mixed> $item
     */
    private function isItemSelectable(array $item): bool
    {
        if (array_key_exists('is_publicly_available', $item) && empty($item['is_publicly_available'])) {
            return false;
        }
        if (array_key_exists('is_selectable', $item)) {
            return !empty($item['is_selectable']);
        }

        $isActive = !array_key_exists('is_active', $item) || !empty($item['is_active']);
        $stock = (int)($item['stock'] ?? 0);

        return $isActive && $stock > 0;
    }

    /**
     * @param array<string,mixed> $item
     */
    private function itemKey(array $item): string
    {
        $key = trim((string)($item['key'] ?? ''));
        if ($key !== '') {
            return $key;
        }

        $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
        $variantId = (int)($item['variant_id'] ?? 0);
        if ($productId <= 0) {
            return '';
        }

        return $productId . ':' . $variantId;
    }
}
