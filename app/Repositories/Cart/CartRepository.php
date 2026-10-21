<?php
namespace App\Repositories\Cart;

use PDO;
use PDOException;

final class CartRepository
{
    public function __construct(private PDO $db) {}

    public function findActiveCartIdForUser(int $userId): int
    {
        $st = $this->db->prepare("SELECT id FROM carts WHERE user_id=? AND status='active' ORDER BY id DESC LIMIT 1");
        $st->execute([$userId]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function findActiveCartIdByToken(string $token): int
    {
        $st = $this->db->prepare("SELECT id FROM carts WHERE token=? AND status='active' ORDER BY id DESC LIMIT 1");
        $st->execute([$token]);
        return (int)($st->fetchColumn() ?: 0);
    }

    /**
     * Devuelve el carrito asociado a un token sin filtrar por estado.
     * Útil para evitar colisiones por UNIQUE(token) cuando el carrito quedó en estado no-activo.
     * @return array{id:int,status:string}|null
     */
    public function findAnyCartByToken(string $token): ?array
    {
        $st = $this->db->prepare("SELECT id, status FROM carts WHERE token=? ORDER BY id DESC LIMIT 1");
        $st->execute([$token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return ['id' => (int)$row['id'], 'status' => (string)$row['status']];
    }

    public function createCartForUser(int $userId): int
    {
        $st = $this->db->prepare("INSERT INTO carts (user_id, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())");
        $st->execute([$userId]);
        return (int)$this->db->lastInsertId();
    }

    public function createCartForToken(string $token): int
    {
        // Idempotente: si el token ya existe (cookie persistente), devolvemos el carrito activo existente.
        $existing = $this->findActiveCartIdByToken($token);
        if ($existing > 0) return $existing;

        try {
            $st = $this->db->prepare("INSERT INTO carts (token, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())");
            $st->execute([$token]);
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            // 23000 / 1062: duplicado por uk_carts_token. Otro request pudo crear el carrito primero.
            $any = $this->findAnyCartByToken($token);
            if ($any && (int)$any['id'] > 0) return (int)$any['id'];
            throw $e;
        }
    }

    public function attachTokenCartToUser(int $cartId, int $userId): void
    {
        $st = $this->db->prepare("UPDATE carts SET user_id=?, token=NULL, updated_at=NOW() WHERE id=?");
        $st->execute([$userId, $cartId]);
    }



    // ---- Alias de compatibilidad legacy de carrito ----
    public function createUserCart(int $userId): int { return $this->createCartForUser($userId); }
    public function createAnonymousCart(string $token): int { return $this->createCartForToken($token); }
    public function attachCartToUser(int $cartId, int $userId): void { $this->attachTokenCartToUser($cartId, $userId); }

    public function markAbandoned(int $cartId): void
    {
        $st = $this->db->prepare("UPDATE carts SET status='abandoned', updated_at=NOW() WHERE id=?");
        $st->execute([$cartId]);
    }
public function markMerged(int $cartId, int $mergedIntoCartId): void
{
    $st = $this->db->prepare("UPDATE carts SET status='merged', merged_into_cart_id=?, merged_at=NOW(), updated_at=NOW() WHERE id=?");
    $st->execute([$mergedIntoCartId, $cartId]);
}

    /** @return array<int,array<string,mixed>> */
    public function items(int $cartId): array
    {
        $st = $this->db->prepare("SELECT product_id, variant_id, quantity FROM cart_items WHERE cart_id=? ORDER BY id ASC");
        $st->execute([$cartId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countQty(int $cartId): int
    {
        $st = $this->db->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE cart_id=?");
        $st->execute([$cartId]);
        return (int)$st->fetchColumn();
    }

    public function upsertItem(int $cartId, int $productId, ?int $variantId, int $qtyDelta): void
    {
        $qtyDelta = max(1, (int)$qtyDelta);

        // Para productos simples usamos variant_id = NULL (FK válida),
        // pero como UNIQUE permite múltiples NULL, hacemos upsert manual.
        if ($variantId === null) {
            $st = $this->db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id=? AND product_id=? AND variant_id IS NULL LIMIT 1");
            $st->execute([$cartId, $productId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['id'])) {
                $id = (int)$row['id'];
                $st2 = $this->db->prepare("UPDATE cart_items SET quantity = quantity + ?, updated_at=NOW() WHERE id=?");
                $st2->execute([$qtyDelta, $id]);
                return;
            }

            $st3 = $this->db->prepare("
                INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, created_at, updated_at)
                VALUES (?,?,NULL,?,NOW(),NOW())
            ");
            $st3->execute([$cartId, $productId, $qtyDelta]);
            return;
        }

        $st = $this->db->prepare("
            INSERT INTO cart_items (cart_id, product_id, variant_id, quantity, created_at, updated_at)
            VALUES (?,?,?,?,NOW(),NOW())
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity), updated_at=NOW()
        ");
        $st->execute([$cartId, $productId, $variantId, $qtyDelta]);
    }

    public function setQuantity(int $cartId, int $productId, ?int $variantId, int $qty): void
    {
        $qty = (int)$qty;
        if ($qty <= 0) { $this->deleteItem($cartId, $productId, $variantId); return; }

        if ($variantId === null) {
            $st = $this->db->prepare("UPDATE cart_items SET quantity=?, updated_at=NOW() WHERE cart_id=? AND product_id=? AND variant_id IS NULL");
            $st->execute([$qty, $cartId, $productId]);
            return;
        }

        $st = $this->db->prepare("UPDATE cart_items SET quantity=?, updated_at=NOW() WHERE cart_id=? AND product_id=? AND variant_id=?");
        $st->execute([$qty, $cartId, $productId, $variantId]);
    }

    public function deleteItem(int $cartId, int $productId, ?int $variantId): void
    {
        if ($variantId === null) {
            $st = $this->db->prepare("DELETE FROM cart_items WHERE cart_id=? AND product_id=? AND variant_id IS NULL");
            $st->execute([$cartId, $productId]);
            return;
        }

        $st = $this->db->prepare("DELETE FROM cart_items WHERE cart_id=? AND product_id=? AND variant_id=?");
        $st->execute([$cartId, $productId, $variantId]);
    }

    public function hasItem(int $cartId, int $productId, ?int $variantId): bool
    {
        if ($variantId === null) {
            $st = $this->db->prepare("SELECT 1 FROM cart_items WHERE cart_id=? AND product_id=? AND variant_id IS NULL LIMIT 1");
            $st->execute([$cartId, $productId]);
            return (bool)$st->fetchColumn();
        }

        $st = $this->db->prepare("SELECT 1 FROM cart_items WHERE cart_id=? AND product_id=? AND variant_id=? LIMIT 1");
        $st->execute([$cartId, $productId, $variantId]);
        return (bool)$st->fetchColumn();
    }

    public function clearItems(int $cartId): void
    {
        $st = $this->db->prepare("DELETE FROM cart_items WHERE cart_id=?");
        $st->execute([$cartId]);
    }
}

