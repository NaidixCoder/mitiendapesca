<?php
declare(strict_types=1);

/**
 * QA destructivo EXCLUSIVAMENTE para MariaDB efímera 127.0.0.1:18959.
 * Crea/baja solo tablas del esquema privado. Nunca apuntar a XAMPP habitual.
 */
$dsn = (string)(getenv('MI_CHECKOUT_INTEGRAL_DSN') ?: '');
if ($dsn !== 'mysql:host=127.0.0.1;port=18959;dbname=mi_checkout_integral_v2_test;charset=utf8mb4') {
    fwrite(STDERR, "REFUSED private DSN mismatch\n"); exit(2);
}
$db = new PDO($dsn, (string)(getenv('MI_CHECKOUT_INTEGRAL_USER') ?: 'root'),
    (string)(getenv('MI_CHECKOUT_INTEGRAL_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
if ((string)$db->query('SELECT DATABASE()')->fetchColumn() !== 'mi_checkout_integral_v2_test'
    || (int)$db->query('SELECT @@port')->fetchColumn() !== 18959) {
    throw new RuntimeException('Private MariaDB identity mismatch');
}
require_once dirname(__DIR__,2).'/app/Repositories/Checkout/CheckoutIntentRepository.php';
require_once dirname(__DIR__,2).'/app/Repositories/Payments/PaymentRepository.php';
require_once dirname(__DIR__,2).'/app/Services/Payments/MercadoPago/MercadoPagoPaymentMatchGuard.php';
use App\Repositories\Checkout\CheckoutIntentRepository;
use App\Repositories\Payments\PaymentRepository;
use App\Services\Payments\MercadoPago\MercadoPagoPaymentMatchGuard;

$count=0;
$ck=static function(bool $pass,string $label) use (&$count):void {
    $count++;
    if(!$pass)throw new RuntimeException("FAIL $label");
    echo "PASS $label\n";
};

try {
    $db->exec('DROP TABLE IF EXISTS payments');
    $db->exec('DROP TABLE IF EXISTS checkout_intents');
    $db->exec(<<<'SQL'
CREATE TABLE checkout_intents (
id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
user_id BIGINT UNSIGNED NOT NULL, address_id BIGINT UNSIGNED NOT NULL,
token VARCHAR(64) NOT NULL UNIQUE, cart_hash VARCHAR(64) NOT NULL,
marketing_campaign_id BIGINT UNSIGNED NULL,email_campaign_id BIGINT UNSIGNED NULL,
order_id BIGINT UNSIGNED NULL,status ENUM('created','used','failed') NOT NULL DEFAULT 'created',
amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL DEFAULT 'ARS',
address_json JSON NOT NULL,items_json JSON NOT NULL,totals_json JSON NOT NULL,
created_at DATETIME NOT NULL,used_at DATETIME NULL,
INDEX idx_checkout_intents_reuse(user_id,address_id,status,cart_hash,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);
    $db->exec(<<<'SQL'
CREATE TABLE payments (
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
provider VARCHAR(32) NOT NULL, provider_payment_id VARCHAR(64) NOT NULL,
provider_preference_id VARCHAR(64) NULL,status VARCHAR(32) NULL,
amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL DEFAULT 'ARS',
intent_id BIGINT UNSIGNED NULL,order_id BIGINT UNSIGNED NULL,raw_json JSON NULL,
created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,
UNIQUE KEY uk_payments_provider_pid(provider,provider_payment_id),
INDEX idx_payments_intent(intent_id),INDEX idx_payments_order(order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $repo=new CheckoutIntentRepository($db);
    $data=[
      'user_id'=>17,'address_id'=>21,'token'=>'test-one',
      'cart_hash'=>str_repeat('a',64),'status'=>'created','amount'=>1500,'currency'=>'ARS',
      'address_json'=>'{}','items_json'=>'[]','totals_json'=>'{}',
      'marketing_campaign_id'=>0,'email_campaign_id'=>0
    ];
    $id=$repo->create($data);
    $ck($id>0,'intent persisted');
    $ck($repo->findReusable(17,21,str_repeat('a',64))['id']===$repo->findByToken('test-one')['id'],'reuse without campaign NULL');
    $ck($repo->findReusable(17,21,str_repeat('a',64),30,9,null)===null,'distinct campaign isolated');
    $ck($repo->findReusable(17,22,str_repeat('a',64))===null,'address isolated');
    $ck($repo->findReusable(18,21,str_repeat('a',64))===null,'user isolated');
    $ck($repo->findReusable(17,21,str_repeat('b',64))===null,'cart isolated');

    $payRepo=new PaymentRepository($db);
    $pay=[
        'provider'=>'mercadopago','provider_payment_id'=>'QA-MP-101','provider_preference_id'=>'',
        'status'=>'approved','amount'=>1500,'currency'=>'ARS','intent_id'=>$id,
        'order_id'=>null,'raw_json'=>'{}',
    ];
    $p1=$payRepo->createOrUpdateByProviderPaymentId($pay);
    $p2=$payRepo->createOrUpdateByProviderPaymentId($pay);
    $ck($p1>0 && $p1===$p2,'provider notification replay idempotent');
    $ck((int)$db->query("SELECT COUNT(*) FROM payments")->fetchColumn()===1,'unique provider payment row');
    $ck($payRepo->attachOrderIfEmpty($p1,33),'first attachment succeeds');
    $ck(!$payRepo->attachOrderIfEmpty($p1,55),'duplicate cannot attach other order');
    $pay['status']='refunded';
    $ck($payRepo->createOrUpdateByProviderPaymentId($pay)===$p1,'status transition reuses payment row');
    $ck($payRepo->findAttachedOrderIdByProviderPaymentId('mercadopago','QA-MP-101')===33,'status update preserves order link');

    $trusted=['id'=>'QA-MP-101','status'=>'approved','transaction_amount'=>1500,'currency_id'=>'ARS',
        'external_reference'=>'test-one'];
    [$ok]=$check=MercadoPagoPaymentMatchGuard::check($trusted,$repo->findByToken('test-one'),'QA-MP-101');
    $ck($ok,'exact approved amount/currency/reference/id trusted');
    foreach ([
      ['external_reference'=>'other-token'],
      ['external_reference'=>''],
      ['transaction_amount'=>1499],
      ['transaction_amount'=>1500.01],
      ['transaction_amount'=>0],
      ['currency_id'=>'USD'],
      ['status'=>'pending'],
      ['id'=>'OTHER'],
    ] as $mutation) {
        [$ok] = MercadoPagoPaymentMatchGuard::check(array_replace($trusted,$mutation),$repo->findByToken('test-one'),'QA-MP-101');
        $ck(!$ok,'reject unsafe change '.json_encode($mutation));
    }
    [$ok]=MercadoPagoPaymentMatchGuard::check($trusted,null,'QA-MP-101');
    $ck(!$ok,'reject missing intent');
    echo "[checkout-integral-db] OK $count private MariaDB checks\n";
} finally {
    $db->exec('DROP TABLE IF EXISTS payments');
    $db->exec('DROP TABLE IF EXISTS checkout_intents');
}
