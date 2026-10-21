<?php
namespace App\Repositories\Orders;

use PDO;

final class OrderRepository
{
    public function __construct(private PDO $db) {}

    private static array $hasCol = [];

    private function hasColumn(string $col): bool
    {
        if (array_key_exists($col, self::$hasCol)) return self::$hasCol[$col];

        $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME=:c");
        $st->execute([':c' => $col]);
        $ok = ((int)$st->fetchColumn() > 0);
        self::$hasCol[$col] = $ok;
        return $ok;
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        $hasPm = $this->hasColumn('payment_method');
        $hasMkt = $this->hasColumn('marketing_campaign_id');
        $hasToken = $this->hasColumn('checkout_token');
        $hasEmailCampaign = $this->hasColumn('email_campaign_id');

        // Snapshot fiscal (opcional, depende de migración)
        $hasSellerRegime = $this->hasColumn('seller_tax_regime');
        $hasSellerCuit = $this->hasColumn('seller_cuit');
        $hasSellerName = $this->hasColumn('seller_business_name');
        $hasSellerAddr = $this->hasColumn('seller_fiscal_address');
        $hasCustomerCond = $this->hasColumn('customer_tax_condition');
        $hasCustomerCuit = $this->hasColumn('customer_cuit');
        $hasCurrency = $this->hasColumn('currency_code');
        $hasTotalNet = $this->hasColumn('total_net');
        $hasTotalVat = $this->hasColumn('total_vat');

        $cols = [
            'user_id','status',
        ];
        $vals = [
            ':user_id',':status',
        ];

        if ($hasPm) { $cols[]='payment_method'; $vals[]=':payment_method'; }
        if ($hasMkt) { $cols[]='marketing_campaign_id'; $vals[]=':marketing_campaign_id'; }
        if ($hasEmailCampaign) { $cols[]='email_campaign_id'; $vals[]=':email_campaign_id'; }
        if ($hasToken) { $cols[]='checkout_token'; $vals[]=':checkout_token'; }

        if ($hasSellerRegime) { $cols[]='seller_tax_regime'; $vals[]=':seller_tax_regime'; }
        if ($hasSellerCuit) { $cols[]='seller_cuit'; $vals[]=':seller_cuit'; }
        if ($hasSellerName) { $cols[]='seller_business_name'; $vals[]=':seller_business_name'; }
        if ($hasSellerAddr) { $cols[]='seller_fiscal_address'; $vals[]=':seller_fiscal_address'; }
        if ($hasCustomerCond) { $cols[]='customer_tax_condition'; $vals[]=':customer_tax_condition'; }
        if ($hasCustomerCuit) { $cols[]='customer_cuit'; $vals[]=':customer_cuit'; }
        if ($hasCurrency) { $cols[]='currency_code'; $vals[]=':currency_code'; }
        if ($hasTotalNet) { $cols[]='total_net'; $vals[]=':total_net'; }
        if ($hasTotalVat) { $cols[]='total_vat'; $vals[]=':total_vat'; }

        $cols = array_merge($cols, [
            'subtotal','shipping','discount','total',
            'address_id',
            'shipping_full_name','shipping_phone',
            'shipping_street','shipping_number','shipping_floor','shipping_apartment',
            'shipping_city','shipping_state','shipping_zip','shipping_notes',
            'shipping_address_json',
            'created_at'
        ]);

        $vals = array_merge($vals, [
            ':subtotal',':shipping',':discount',':total',
            ':address_id',
            ':shipping_full_name',':shipping_phone',
            ':shipping_street',':shipping_number',':shipping_floor',':shipping_apartment',
            ':shipping_city',':shipping_state',':shipping_zip',':shipping_notes',
            ':shipping_address_json',
            'NOW()'
        ]);

        $sql = "INSERT INTO orders (".implode(',', $cols).") VALUES (".implode(',', $vals).")";

        $params = [
            ':user_id' => $data['user_id'] ?? null,
            ':status' => $data['status'] ?? 'pending_payment',
            ':subtotal' => (int)($data['subtotal'] ?? 0),
            ':shipping' => (int)($data['shipping'] ?? 0),
            ':discount' => (int)($data['discount'] ?? 0),
            ':total' => (int)($data['total'] ?? 0),
            ':address_id' => (int)($data['address_id'] ?? 0),
            ':shipping_full_name' => $data['shipping_full_name'] ?? null,
            ':shipping_phone' => $data['shipping_phone'] ?? null,
            ':shipping_street' => $data['shipping_street'] ?? null,
            ':shipping_number' => $data['shipping_number'] ?? null,
            ':shipping_floor' => $data['shipping_floor'] ?? null,
            ':shipping_apartment' => $data['shipping_apartment'] ?? null,
            ':shipping_city' => $data['shipping_city'] ?? null,
            ':shipping_state' => $data['shipping_state'] ?? null,
            ':shipping_zip' => $data['shipping_zip'] ?? null,
            ':shipping_notes' => $data['shipping_notes'] ?? null,
            ':shipping_address_json' => $data['shipping_address_json'] ?? null,
        ];

        if ($hasEmailCampaign) {
            $params[':email_campaign_id'] = (int)($data['email_campaign_id'] ?? 0);
        }

        if ($hasPm) $params[':payment_method'] = $data['payment_method'] ?? null;
        if ($hasMkt) $params[':marketing_campaign_id'] = (int)($data['marketing_campaign_id'] ?? 0) ?: null;
        if ($hasEmailCampaign) $params[':email_campaign_id'] = (int)($data['email_campaign_id'] ?? 0) ?: null;
        if ($hasToken) $params[':checkout_token'] = $data['checkout_token'] ?? null;

        if ($hasSellerRegime) $params[':seller_tax_regime'] = $data['seller_tax_regime'] ?? null;
        if ($hasSellerCuit) $params[':seller_cuit'] = $data['seller_cuit'] ?? null;
        if ($hasSellerName) $params[':seller_business_name'] = $data['seller_business_name'] ?? null;
        if ($hasSellerAddr) $params[':seller_fiscal_address'] = $data['seller_fiscal_address'] ?? null;
        if ($hasCustomerCond) $params[':customer_tax_condition'] = $data['customer_tax_condition'] ?? null;
        if ($hasCustomerCuit) $params[':customer_cuit'] = $data['customer_cuit'] ?? null;
        if ($hasCurrency) $params[':currency_code'] = $data['currency_code'] ?? null;
        if ($hasTotalNet) $params[':total_net'] = isset($data['total_net']) ? (int)$data['total_net'] : null;
        if ($hasTotalVat) $params[':total_vat'] = isset($data['total_vat']) ? (int)$data['total_vat'] : null;

        $st = $this->db->prepare($sql);
        $st->execute($params);

        return (int)$this->db->lastInsertId();
    }

    public function findIdByCheckoutToken(string $token): int
    {
        if (!$this->hasColumn('checkout_token')) return 0;
        $st = $this->db->prepare("SELECT id FROM orders WHERE checkout_token=:t LIMIT 1");
        $st->execute([':t' => $token]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : 0;
    }
}

