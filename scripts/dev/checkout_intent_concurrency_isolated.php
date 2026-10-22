<?php
declare(strict_types=1);
/** Two independent DB connections, private MariaDB only. No commercial records. */
$dsn=(string)getenv('MI_CHECKOUT_INTEGRAL_DSN');
if ($dsn!=='mysql:host=127.0.0.1;port=18959;dbname=mi_checkout_integral_v2_test;charset=utf8mb4') {
    fwrite(STDERR,"REFUSED private QA DSN\n");exit(2);
}
$open=static function()use($dsn):PDO {
    $db=new PDO($dsn,(string)getenv('MI_CHECKOUT_INTEGRAL_USER'),
        (string)getenv('MI_CHECKOUT_INTEGRAL_PASSWORD'),
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    if((int)$db->query('SELECT @@port')->fetchColumn()!==18959
       ||$db->query('SELECT DATABASE()')->fetchColumn()!=='mi_checkout_integral_v2_test') {
        throw new RuntimeException('Refused non-private database');
    }
    return $db;
};
$db1=$open();$db2=$open();
require_once dirname(__DIR__,2).'/app/Repositories/Checkout/CheckoutIntentRepository.php';
require_once dirname(__DIR__,2).'/app/Repositories/Payments/PaymentRepository.php';
$repo1=new \App\Repositories\Checkout\CheckoutIntentRepository($db1);
$repo2=new \App\Repositories\Checkout\CheckoutIntentRepository($db2);
$n=0;
$ck=static function($ok,$msg)use(&$n):void{
  $n++;if(!$ok)throw new RuntimeException("FAIL $msg");echo "PASS $msg\n";
};
try {
    $db1->exec('DROP TABLE IF EXISTS payments');
    $db1->exec('DROP TABLE IF EXISTS checkout_intents');
    $db1->exec("CREATE TABLE checkout_intents (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL, address_id BIGINT UNSIGNED NOT NULL,
        token VARCHAR(64) NOT NULL UNIQUE, cart_hash VARCHAR(64) NOT NULL,
        marketing_campaign_id BIGINT UNSIGNED NULL,email_campaign_id BIGINT UNSIGNED NULL,
        order_id BIGINT UNSIGNED NULL,status ENUM('created','used','failed') DEFAULT 'created',
        amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL DEFAULT 'ARS',
        address_json JSON NOT NULL,items_json JSON NOT NULL,totals_json JSON NOT NULL,
        created_at DATETIME NOT NULL,used_at DATETIME NULL,
        INDEX idx_checkout_intents_reuse(user_id,address_id,status,cart_hash,created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $db1->exec("CREATE TABLE payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        provider VARCHAR(32) NOT NULL,provider_payment_id VARCHAR(64) NOT NULL,
        provider_preference_id VARCHAR(64) NULL,status VARCHAR(32),
        amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL,
        intent_id BIGINT UNSIGNED NULL,order_id BIGINT UNSIGNED NULL,
        raw_json JSON NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,
        UNIQUE KEY uk_payments_provider_pid(provider,provider_payment_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $hash=str_repeat('9',64);
    $lock=$repo1->acquireReuseLock(71,90,$hash,null,null);
    $stmt=$db2->prepare('SELECT GET_LOCK(:k,0)');
    $stmt->execute([':k'=>$lock]);
    $ck((int)$stmt->fetchColumn()===0,'simultaneous same-cart connection must wait');
    $payload=[
      'user_id'=>71,'address_id'=>90,'token'=>'qa-race-token',
      'cart_hash'=>$hash,'marketing_campaign_id'=>0,'email_campaign_id'=>0,
      'status'=>'created','amount'=>900,'currency'=>'ARS',
      'address_json'=>'{}','items_json'=>'[]','totals_json'=>'{}'
    ];
    $first=$repo1->create($payload);
    $ck($first>0,'first request creates intent while lock held');
    $repo1->releaseReuseLock($lock);
    $lock2=$repo2->acquireReuseLock(71,90,$hash,null,null);
    $ck($lock2===$lock,'both requests use exact same key');
    $found=$repo2->findReusable(71,90,$hash,30,null,null);
    $ck((int)($found['id']??0)===$first,'second request reuses first token after lock');
    $ck((int)$db2->query('SELECT COUNT(*) FROM checkout_intents')->fetchColumn()===1,'no second intent created');
    $repo2->releaseReuseLock($lock2);

    $different=$repo1->acquireReuseLock(71,90,$hash,2,null);
    $ck($different===$lock,'campaign changes do not bypass shared-cart intent lock');
    $repo1->releaseReuseLock($different);

    $chargeLock=\App\Repositories\Checkout\CheckoutIntentRepository::paymentCartLockName(71,90,$hash);
    $ck($chargeLock===\App\Repositories\Checkout\CheckoutIntentRepository::paymentCartLockName(71,90,$hash),
        'same cart gives identical payment lock regardless of intent');
    $hold=$db1->prepare('SELECT GET_LOCK(:key,2)');
    $hold->execute([':key'=>$chargeLock]);
    $ck((int)$hold->fetchColumn()===1,'first request holds payment lock');
    $wait=$db2->prepare('SELECT GET_LOCK(:key,0)');
    $wait->execute([':key'=>$chargeLock]);
    $ck((int)$wait->fetchColumn()===0,'second payment request cannot enter MP call');
    $db1->prepare('SELECT RELEASE_LOCK(:key)')->execute([':key'=>$chargeLock]);
    $wait->execute([':key'=>$chargeLock]);
    $ck((int)$wait->fetchColumn()===1,'payment lock available after first request');
    $db2->prepare('SELECT RELEASE_LOCK(:key)')->execute([':key'=>$chargeLock]);

    $payments=new \App\Repositories\Payments\PaymentRepository($db1);
    $payments->create([
      'provider'=>'mercadopago','provider_payment_id'=>'qa-pay-race',
      'status'=>'approved','amount'=>900,'currency'=>'ARS','intent_id'=>$first,
      'raw_json'=>'{}'
    ]);
    $ck($payments->findActiveForIntent($first)!==null,'later payment attempt detects active payment');
    $ck($payments->findActiveForIntent($first,'qa-pay-race')===null,'webhook excludes own payment from duplicate scan');
    $ck($payments->findOutstandingForCart(71,90,$hash)!==null,'approved orphan blocks new intent of same cart');
    $db1->exec("UPDATE checkout_intents SET status='failed' WHERE id=".$first);
    $ck($payments->findOutstandingForCart(71,90,$hash)!==null,'failed intent still protects approved orphan');
    $ck($payments->findOutstandingForCart(72,90,$hash)===null,'orphan query isolates users');
    $ck($payments->findOutstandingForCart(71,90,str_repeat('b',64))===null,'orphan query isolates carts');
    $db1->exec("UPDATE payments SET order_id=123 WHERE provider_payment_id='qa-pay-race'");
    $ck($payments->findOutstandingForCart(71,90,$hash)===null,'linked payment no longer orphan');
    $db1->exec("UPDATE payments SET order_id=NULL,status='refunded' WHERE provider_payment_id='qa-pay-race'");
    $ck($payments->findOutstandingForCart(71,90,$hash)===null,'refunded payment does not block retry');    echo "[checkout-intent-concurrency] OK $n MariaDB checks\n";
} finally {
    $db1->exec('DROP TABLE IF EXISTS payments');
    $db1->exec('DROP TABLE IF EXISTS checkout_intents');
}
