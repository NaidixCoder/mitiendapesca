<?php
declare(strict_types=1);
namespace App\Services\Payments {
    class MercadoPagoService {
        public static array $fixtures=[];
        public static bool $throw=false;
        public function getPayment(string $id): array {
            if (self::$throw) throw new \RuntimeException('Simulated MP timeout');
            return self::$fixtures[$id] ?? throw new \RuntimeException('Mock payment missing');
        }
    }
}
namespace App\Services\Audit {
    class Audit {
        public static array $events=[];
        public static function log(...$args): void {self::$events[]=$args;}
    }
}
namespace App\Services\Orders {
    class OrderFromIntentService {
        public static int $calls=0;
        public function __construct(\PDO $db){}
        public function createOrderFromIntent(string $token): array {
            ++self::$calls;
            return [true,'QA mock synthetic order',901];
        }
    }
}
namespace App\Services\Admin {
    class AdminOrderService {
        public static int $markCalls=0;
        public function __construct(\PDO $db){}
        public function markPaidMercadoPago(...$args): array {
            ++self::$markCalls;return [true,'Already paid',['noop'=>true]];
        }
    }
}
namespace {
    use App\Controllers\Webhooks\MercadoPagoWebhookController as Hook;
    use App\Repositories\Checkout\CheckoutIntentRepository as IntentRepo;
    use App\Repositories\Payments\PaymentRepository as PayRepo;
    use App\Services\Payments\MercadoPagoService as MockMP;
    use App\Services\Orders\OrderFromIntentService as MockOrder;
    use App\Services\Audit\Audit as MockAudit;
    $dsn=(string)getenv('MI_CHECKOUT_INTEGRAL_DSN');
    if($dsn!=='mysql:host=127.0.0.1;port=18959;dbname=mi_checkout_integral_v2_test;charset=utf8mb4'){
        fwrite(STDERR,"REFUSED private DSN\n");exit(2);
    }
    $db=new \PDO($dsn,(string)getenv('MI_CHECKOUT_INTEGRAL_USER'),
        (string)getenv('MI_CHECKOUT_INTEGRAL_PASSWORD'),
        [\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_EMULATE_PREPARES=>false]);
    if((int)$db->query('SELECT @@port')->fetchColumn()!==18959
        || $db->query('SELECT DATABASE()')->fetchColumn()!=='mi_checkout_integral_v2_test'){
        throw new \RuntimeException('Refusing non-private SQL identity');
    }
    require dirname(__DIR__,2).'/vendor/autoload.php';
    $GLOBALS['qa_db']=$db;
    $GLOBALS['qa_config']=[
        'MP_WEBHOOK_SECRET'=>'qa-only-secret',
        'MP_WEBHOOK_SIGNATURE_SECRET'=>'',
    ];
    function db(): \PDO{return $GLOBALS['qa_db'];}
    function env($name,$default=''){return $GLOBALS['qa_config'][$name]??$default;}
    function log_error(string $x):void{$GLOBALS['qa_logs'][]=$x;}
    function cache_forget(string $x):void{}

    $checks=0;
    function ck(bool $ok,string $label):void {
        global $checks;
        ++$checks;
        if(!$ok)throw new \RuntimeException('FAIL '.$label);
        echo 'PASS '.$label.PHP_EOL;
    }
    function notify(string $pid,string $secret='qa-only-secret'):array {
        $_SERVER['REQUEST_URI']='/webhooks/mercadopago?secret='.rawurlencode($secret).'&data.id='.rawurlencode($pid);
        unset($_SERVER['HTTP_X_SIGNATURE'],$_SERVER['HTTP_X_REQUEST_ID']);
        $_GET=['secret'=>$secret,'data.id'=>$pid,'type'=>'payment'];
        http_response_code(200);
        ob_start();
        (new Hook())->handle();
        $body=ob_get_clean();
        return [http_response_code(),json_decode((string)$body,true),(string)$body];
    }
    try {
        $db->exec('DROP TABLE IF EXISTS payments');
        $db->exec('DROP TABLE IF EXISTS checkout_intents');
        $db->exec("CREATE TABLE checkout_intents(
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NOT NULL,
            address_id BIGINT UNSIGNED NOT NULL,token VARCHAR(64) NOT NULL UNIQUE,
            cart_hash VARCHAR(64) NOT NULL,marketing_campaign_id BIGINT UNSIGNED NULL,
            email_campaign_id BIGINT UNSIGNED NULL,order_id BIGINT UNSIGNED NULL,
            status ENUM('created','used','failed') NOT NULL DEFAULT 'created',
            amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL DEFAULT 'ARS',
            address_json JSON NOT NULL,items_json JSON NOT NULL,totals_json JSON NOT NULL,
            created_at DATETIME NOT NULL,used_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $db->exec("CREATE TABLE payments(
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,provider VARCHAR(32) NOT NULL,
            provider_payment_id VARCHAR(64) NOT NULL,provider_preference_id VARCHAR(64) NULL,
            status VARCHAR(32) NULL,amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL,
            intent_id BIGINT UNSIGNED NULL,order_id BIGINT UNSIGNED NULL,raw_json JSON NULL,
            created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,
            UNIQUE KEY uk_payments_provider_pid(provider,provider_payment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $repo=new IntentRepo($db);
        $seed=['user_id'=>7,'address_id'=>7,'cart_hash'=>str_repeat('c',64),
            'status'=>'created','amount'=>2800,'currency'=>'ARS',
            'address_json'=>'{}','items_json'=>'[]','totals_json'=>'{}'];
        $id=$repo->create($seed+['token'=>'QA-WEBHOOK-INTENT']);
        $approved=['status'=>'approved','external_reference'=>'QA-WEBHOOK-INTENT',
            'transaction_amount'=>2800,'currency_id'=>'ARS','id'=>'qa-web-1'];
        MockMP::$fixtures['qa-web-1']=$approved;

        [$status,$body]=notify('qa-web-1','wrong');
        ck($status===403 && $body['error']==='forbidden','legacy wrong token denied');

        $GLOBALS['qa_config']['MP_WEBHOOK_SECRET']='';
        [$status,$body]=notify('qa-web-1','');
        ck($status===503 && $body['error']==='webhook_auth_not_configured','missing auth config fails closed');
        $GLOBALS['qa_config']['MP_WEBHOOK_SECRET']='qa-only-secret';

        MockMP::$fixtures['qa-web-1']['transaction_amount']=28;
        [$status,$body]=notify('qa-web-1');
        ck($status===202 && !empty($body['manual_reconciliation_required'])
          && $body['code']==='approved_payment_requires_review','amount mismatch quarantined');
        ck(MockOrder::$calls===0,'amount mismatch makes no order');
        $payRepo=new PayRepo($db);
        ck($payRepo->findByProviderPaymentId('mercadopago','qa-web-1')!==null,
          'approved mismatch persisted for reconciliation');
        MockMP::$fixtures['qa-web-1']=$approved;
        [$status,$body]=notify('qa-web-1');
        ck($status===200 && $body['order_id']===901,'valid webhook creates order (mock service)');
        ck(MockOrder::$calls===1,'order service invoked once');

        [$status,$body]=notify('qa-web-1');
        ck($status===200 && $body['order_id']===901,'same payment webhook replay attached');
        ck(MockOrder::$calls===1,'duplicate notification creates no second order');
        ck((int)$db->query("SELECT COUNT(*) FROM payments")->fetchColumn()===1,'replay keeps one payment row');

        MockMP::$fixtures['qa-web-2']=$approved;
        MockMP::$fixtures['qa-web-2']['id']='qa-web-2';
        [$status,$body]=notify('qa-web-2');
        ck($status===202 && $body['code']==='duplicate_payment_for_intent'
            && !empty($body['manual_reconciliation_required']),'second distinct approved payment requires manual review');
        ck(MockOrder::$calls===1,'duplicate payment never creates another order');

        MockMP::$fixtures['qa-web-nointent']=$approved;
        MockMP::$fixtures['qa-web-nointent']['id']='qa-web-nointent';
        MockMP::$fixtures['qa-web-nointent']['external_reference']='QA-MISSING-INTENT';
        [$status,$body]=notify('qa-web-nointent');
        ck($status===202 && $body['code']==='approved_payment_requires_review','missing intent quarantined');

        MockMP::$throw=true;
        [$status,$body]=notify('qa-web-throw');
        ck($status===503 && $body['error']==='webhook_processing_failed','provider timeout responds retryable 503');
        MockMP::$throw=false;

        $GLOBALS['qa_config']['MP_WEBHOOK_SIGNATURE_SECRET']='unit-sig-secret';
        [$status,$body]=notify('qa-web-1');
        ck($status===403 && $body['error']==='invalid_signature','missing HMAC signature denied');
        echo "[checkout-webhook-simulated] OK $checks private MariaDB/controller checks\n";
    } finally {
        $db->exec('DROP TABLE IF EXISTS payments');
        $db->exec('DROP TABLE IF EXISTS checkout_intents');
    }
}
