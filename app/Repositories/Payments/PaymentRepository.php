<?php
namespace App\Repositories\Payments;

use PDO;
use PDOException;

final class PaymentRepository
{
    public function __construct(private PDO $db) {}

    public function findByProviderPaymentId(string $provider, string $providerPaymentId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE provider=:p AND provider_payment_id=:id LIMIT 1");
        $stmt->execute([':p'=>$provider, ':id'=>$providerPaymentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findAttachedOrderIdByProviderPaymentId(string $provider, string $providerPaymentId): int
    {
        $stmt = $this->db->prepare(
            "SELECT order_id
               FROM payments
              WHERE provider=:p AND provider_payment_id=:id
              LIMIT 1"
        );
        $stmt->execute([':p' => $provider, ':id' => $providerPaymentId]);
        $orderId = $stmt->fetchColumn();
        return $orderId ? (int)$orderId : 0;
    }

    /** @param array<string,mixed> $data */
    
    public function findLatestByOrderId(int $orderId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE order_id=:oid ORDER BY updated_at DESC, id DESC LIMIT 1");
        $stmt->execute([':oid'=>$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("INSERT INTO payments
            (provider, provider_payment_id, provider_preference_id, status, amount, currency, intent_id, order_id, raw_json, created_at, updated_at)
            VALUES (:provider, :ppid, :pref, :status, :amount, :currency, :intent_id, :order_id, :raw, NOW(), NOW())");
        $stmt->execute([
            ':provider' => (string)$data['provider'],
            ':ppid' => (string)($data['provider_payment_id'] ?? ''),
            ':pref' => (string)($data['provider_preference_id'] ?? ''),
            ':status' => (string)($data['status'] ?? ''),
            ':amount' => (int)($data['amount'] ?? 0),
            ':currency' => (string)($data['currency'] ?? 'ARS'),
            ':intent_id' => (int)($data['intent_id'] ?? 0),
            ':order_id' => !empty($data['order_id']) ? (int)$data['order_id'] : null,
            ':raw' => (string)($data['raw_json'] ?? '{}'),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function createOrUpdateByProviderPaymentId(array $data): int
    {
        $provider = (string)($data['provider'] ?? '');
        $providerPaymentId = (string)($data['provider_payment_id'] ?? '');
        if ($provider === '' || $providerPaymentId === '') {
            return 0;
        }

        $existing = $this->findByProviderPaymentId($provider, $providerPaymentId);
        if ($existing) {
            $paymentId = (int)($existing['id'] ?? 0);
            if ($paymentId > 0) {
                $this->updateById($paymentId, $data);
            }
            return $paymentId;
        }

        try {
            return $this->create($data);
        } catch (PDOException $e) {
            if (($e->getCode() ?? '') !== '23000') {
                throw $e;
            }

            $existing = $this->findByProviderPaymentId($provider, $providerPaymentId);
            if ($existing) {
                $paymentId = (int)($existing['id'] ?? 0);
                if ($paymentId > 0) {
                    $this->updateById($paymentId, $data);
                }
                return $paymentId;
            }

            throw $e;
        }
    }

    /**
     * Actualiza el registro de pago sin tocar su relación con el pedido.
     * Útil para refrescar status/raw_json cuando Mercado Pago cambia de estado.
     *
     * @param array<string,mixed> $data
     */
    public function updateById(int $paymentId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET provider_preference_id=:pref,
                 status=:status,
                 amount=:amount,
                 currency=:currency,
                 raw_json=:raw,
                 updated_at=NOW()
             WHERE id=:id"
        );
        $stmt->execute([
            ':pref' => (string)($data['provider_preference_id'] ?? ''),
            ':status' => (string)($data['status'] ?? ''),
            ':amount' => (int)($data['amount'] ?? 0),
            ':currency' => (string)($data['currency'] ?? 'ARS'),
            ':raw' => (string)($data['raw_json'] ?? '{}'),
            ':id' => $paymentId,
        ]);
        return (int)$stmt->rowCount() > 0;
    }

    public function attachOrder(int $paymentId, int $orderId): void
    {
        // Idempotente: no sobreescribimos un order_id ya asociado.
        // Esto evita dobles enlaces/auditorías cuando el webhook y el checkout corren en carrera.
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET order_id=:oid, updated_at=NOW()
             WHERE id=:id AND (order_id IS NULL OR order_id=0)"
        );
        $stmt->execute([':oid'=>$orderId, ':id'=>$paymentId]);
    }

    /**
     * Devuelve true si efectivamente se enlazó (rowCount>0). Útil para decidir si auditar.
     */
    public function attachOrderIfEmpty(int $paymentId, int $orderId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE payments
             SET order_id=:oid, updated_at=NOW()
             WHERE id=:id AND (order_id IS NULL OR order_id=0)"
        );
        $stmt->execute([':oid'=>$orderId, ':id'=>$paymentId]);
        return (int)$stmt->rowCount() > 0;
    }
}
