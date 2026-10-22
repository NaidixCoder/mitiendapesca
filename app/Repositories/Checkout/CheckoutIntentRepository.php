<?php
namespace App\Repositories\Checkout;

use PDO;

final class CheckoutIntentRepository
{
    public function __construct(private PDO $db) {}

    private static array $hasCol = [];

    private function hasColumn(string $col): bool
    {
        if (array_key_exists($col, self::$hasCol)) return self::$hasCol[$col];
        $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='checkout_intents' AND COLUMN_NAME=:c");
        $st->execute([':c' => $col]);
        $ok = ((int)$st->fetchColumn() > 0);
        self::$hasCol[$col] = $ok;
        return $ok;
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $cols = ['user_id','address_id','token','cart_hash','status','amount','currency','address_json','items_json','totals_json','created_at'];
        $vals = [':user_id',':address_id',':token',':cart_hash',':status',':amount',':currency',':address_json',':items_json',':totals_json','NOW()'];

        if ($this->hasColumn('marketing_campaign_id')) {
            array_splice($cols, 5, 0, ['marketing_campaign_id']); // después de cart_hash
            array_splice($vals, 5, 0, [':marketing_campaign_id']);
        }

        // Opcionales (según migraciones): Email marketing + snapshot fiscal/contable
        $appendBeforeCreatedAt = function (string $col, string $ph) use (&$cols, &$vals) {
            // insertamos antes de created_at (último)
            $i = max(0, count($cols) - 1);
            array_splice($cols, $i, 0, [$col]);
            array_splice($vals, $i, 0, [$ph]);
        };

        if ($this->hasColumn('email_campaign_id')) {
            $appendBeforeCreatedAt('email_campaign_id', ':email_campaign_id');
        }
        if ($this->hasColumn('seller_tax_regime')) {
            $appendBeforeCreatedAt('seller_tax_regime', ':seller_tax_regime');
        }
        if ($this->hasColumn('seller_cuit')) {
            $appendBeforeCreatedAt('seller_cuit', ':seller_cuit');
        }
        if ($this->hasColumn('seller_business_name')) {
            $appendBeforeCreatedAt('seller_business_name', ':seller_business_name');
        }
        if ($this->hasColumn('seller_fiscal_address')) {
            $appendBeforeCreatedAt('seller_fiscal_address', ':seller_fiscal_address');
        }
        if ($this->hasColumn('customer_tax_condition')) {
            $appendBeforeCreatedAt('customer_tax_condition', ':customer_tax_condition');
        }
        if ($this->hasColumn('customer_cuit')) {
            $appendBeforeCreatedAt('customer_cuit', ':customer_cuit');
        }
        if ($this->hasColumn('currency_code')) {
            $appendBeforeCreatedAt('currency_code', ':currency_code');
        }
        if ($this->hasColumn('total_net')) {
            $appendBeforeCreatedAt('total_net', ':total_net');
        }
        if ($this->hasColumn('total_vat')) {
            $appendBeforeCreatedAt('total_vat', ':total_vat');
        }

        $stmt = $this->db->prepare(
            "INSERT INTO checkout_intents (".implode(',', $cols).") VALUES (".implode(',', $vals).")"
        );

        $params = [
            ':user_id' => (int)$data['user_id'],
            ':address_id' => (int)$data['address_id'],
            ':token' => (string)$data['token'],
            ':cart_hash' => (string)$data['cart_hash'],
            ':status' => (string)($data['status'] ?? 'created'),
            ':amount' => (int)($data['amount'] ?? 0),
            ':currency' => (string)($data['currency'] ?? 'ARS'),
            ':address_json' => (string)($data['address_json'] ?? '{}'),
            ':items_json' => (string)($data['items_json'] ?? '[]'),
            ':totals_json' => (string)($data['totals_json'] ?? '{}'),
        ];
        if ($this->hasColumn('marketing_campaign_id')) {
            $params[':marketing_campaign_id'] = (int)($data['marketing_campaign_id'] ?? 0) ?: null;
        }
        if ($this->hasColumn('email_campaign_id')) {
            $params[':email_campaign_id'] = (int)($data['email_campaign_id'] ?? 0) ?: null;
        }
        if ($this->hasColumn('seller_tax_regime')) {
            $params[':seller_tax_regime'] = (string)($data['seller_tax_regime'] ?? '');
        }
        if ($this->hasColumn('seller_cuit')) {
            $params[':seller_cuit'] = $data['seller_cuit'] ?? null;
        }
        if ($this->hasColumn('seller_business_name')) {
            $params[':seller_business_name'] = $data['seller_business_name'] ?? null;
        }
        if ($this->hasColumn('seller_fiscal_address')) {
            $params[':seller_fiscal_address'] = $data['seller_fiscal_address'] ?? null;
        }
        if ($this->hasColumn('customer_tax_condition')) {
            $params[':customer_tax_condition'] = (string)($data['customer_tax_condition'] ?? 'consumidor_final');
        }
        if ($this->hasColumn('customer_cuit')) {
            $params[':customer_cuit'] = $data['customer_cuit'] ?? null;
        }
        if ($this->hasColumn('currency_code')) {
            $params[':currency_code'] = (string)($data['currency_code'] ?? ($data['currency'] ?? 'ARS'));
        }
        if ($this->hasColumn('total_net')) {
            $params[':total_net'] = isset($data['total_net']) ? (int)$data['total_net'] : null;
        }
        if ($this->hasColumn('total_vat')) {
            $params[':total_vat'] = isset($data['total_vat']) ? (int)$data['total_vat'] : null;
        }
        $stmt->execute($params);

        return (int)$this->db->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findByToken(string $token): ?array
    {
        $st = $this->db->prepare("SELECT * FROM checkout_intents WHERE token = :t LIMIT 1");
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Busca un intent reutilizable (status=created) para mismo user+address+car_hash
     * dentro de una ventana corta, para evitar crear múltiples tokens por doble submit.
     * @return array<string,mixed>|null
     */
    public function findReusable(int $userId, int $addressId, string $cartHash, int $maxAgeMinutes = 30, ?int $marketingCampaignId = null, ?int $emailCampaignId = null): ?array
    {
        $maxAgeMinutes = max(1, (int)$maxAgeMinutes);

        $where = "user_id=:u AND address_id=:a AND status='created' AND cart_hash=:h";
        if ($this->hasColumn('marketing_campaign_id')) {
            $where .= " AND COALESCE(marketing_campaign_id, 0) = :m";
        }
        if ($this->hasColumn('email_campaign_id')) {
            $where .= " AND COALESCE(email_campaign_id, 0) = :e";
        }

        // IMPORTANTE: MySQL no acepta bind de :mins dentro de INTERVAL.
        $st = $this->db->prepare(
            "SELECT *
               FROM checkout_intents
              WHERE {$where}
                AND created_at >= (NOW() - INTERVAL {$maxAgeMinutes} MINUTE)
              ORDER BY id DESC
              LIMIT 1"
        );
        $st->bindValue(':u', $userId, PDO::PARAM_INT);
        $st->bindValue(':a', $addressId, PDO::PARAM_INT);
        $st->bindValue(':h', $cartHash, PDO::PARAM_STR);
        if ($this->hasColumn('marketing_campaign_id')) {
            $st->bindValue(':m', (int)($marketingCampaignId ?? 0), PDO::PARAM_INT);
        }
        if ($this->hasColumn('email_campaign_id')) {
            $st->bindValue(':e', (int)($emailCampaignId ?? 0), PDO::PARAM_INT);
        }
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Serializa la decisiÃ³n buscar/crear intent para el MISMO carrito/campaÃ±a.
     * GET_LOCK es por conexiÃ³n; no requiere esquema ni afecta otros clientes.
     */
    public static function paymentCartLockName(int $userId, int $addressId, string $cartHash): string
    {
        return 'mipt:charge:' . substr(hash('sha256', implode('|',[$userId,$addressId,$cartHash])),0,45);
    }

    public function acquireReuseLock(int $userId, int $addressId, string $cartHash, ?int $mkt, ?int $email): string
    {
        $identity = implode('|', [$userId,$addressId,$cartHash]);
        $name = 'mipt:intent:' . substr(hash('sha256', $identity),0,48);
        $st=$this->db->prepare('SELECT GET_LOCK(:name, 8)');
        $st->execute([':name'=>$name]);
        if ((int)$st->fetchColumn() !== 1) {
            throw new \RuntimeException('Checkout en proceso. VolvÃ© a intentarlo sin duplicar el pago.');
        }
        return $name;
    }

    public function releaseReuseLock(string $name): void
    {
        if ($name === '' || !str_starts_with($name,'mipt:intent:')) return;
        $st=$this->db->prepare('SELECT RELEASE_LOCK(:name)');
        $st->execute([':name'=>$name]);
    }
    /**
     * Marca durablemente el intent antes de llamar a Mercado Pago.
     * Un worker caÃ­do conserva el bloqueo hasta conciliaciÃ³n supervisada.
     */
    public function markPaymentInFlight(int $id): bool
    {
        $st=$this->db->prepare(
            "UPDATE checkout_intents SET fail_reason='payment_in_flight'
             WHERE id=:id AND status='created' AND (fail_reason IS NULL OR fail_reason='')"
        );
        $st->execute([':id'=>$id]);
        return (int)$st->rowCount()===1;
    }

    public function clearPaymentInFlight(int $id): void
    {
        $st=$this->db->prepare(
            "UPDATE checkout_intents SET fail_reason=NULL
             WHERE id=:id AND fail_reason='payment_in_flight'"
        );
        $st->execute([':id'=>$id]);
    }

    public function findInFlightForCart(int $userId,int $addressId,string $cartHash): ?array
    {
        $st=$this->db->prepare(
            "SELECT id,token,status FROM checkout_intents
             WHERE status='created' AND user_id=:u AND address_id=:a AND cart_hash=:h
             AND fail_reason='payment_in_flight'
             ORDER BY id DESC LIMIT 1"
        );
        $st->execute([':u'=>$userId,':a'=>$addressId,':h'=>$cartHash]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
    /**
     * Lockea el intent por token (FOR UPDATE). Debe llamarse dentro de una transacción.
     * @return array<string,mixed>|null
     */
    public function lockByToken(string $token): ?array
    {
        $st = $this->db->prepare("SELECT * FROM checkout_intents WHERE token=:t LIMIT 1 FOR UPDATE");
        $st->execute([':t' => $token]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function attachOrder(int $id, int $orderId): void
    {
        $st = $this->db->prepare("UPDATE checkout_intents SET order_id=:oid WHERE id=:id");
        $st->execute([':id' => $id, ':oid' => $orderId]);
    }

    public function markUsed(int $id): void
    {
        $st = $this->db->prepare("UPDATE checkout_intents SET status='used', used_at=NOW() WHERE id=:id");
        $st->execute([':id' => $id]);
    }

    public function markFailed(int $id, string $reason): void
    {
        $st = $this->db->prepare("UPDATE checkout_intents SET status='failed', fail_reason=:r WHERE id=:id");
        $st->execute([':id' => $id, ':r' => $reason]);
    }
}
