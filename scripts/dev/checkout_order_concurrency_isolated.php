<?php
declare(strict_types=1);

namespace App\Services\Checkout {
    final class CheckoutProductEligibilityGuard {
        public function __construct(private \PDO $db) {}
        public function checkItems(array $items,bool $lock=false):array {
            if ($lock) usleep(750000);
            return [count($items)>0,'QA eligibility stub',[]];
        }
    }
}
namespace App\Services\Catalog {
    final class ProductPublicationSyncService {
        public function __construct(private \PDO $db){}
        public function syncProduct(int $id):void {}
        public function syncByVariant(int $id):void {}
    }
}
namespace App\Services\Loyalty {
    final class LoyaltyOrderEventsService {
        public function __construct(private \PDO $db){}
        public function onOrderStatusTransition(...$args):void {}
    }
}
namespace App\Mail\Orders {
    final class TransactionalMail {
        public function __construct(private \PDO $db){}
        public function sendPaymentConfirmed(...$args):void {}
    }
}
namespace {
    require dirname(__DIR__,2).'/vendor/autoload.php';
    $dsn=(string)getenv('MI_CHECKOUT_INTEGRAL_DSN');
    if($dsn!=='mysql:host=127.0.0.1;port=18959;dbname=mi_checkout_integral_v2_test;charset=utf8mb4'){
        fwrite(STDERR,"REFUSED private QA database\n");exit(2);
    }
    $db=new \PDO($dsn,(string)getenv('MI_CHECKOUT_INTEGRAL_USER'),
       (string)getenv('MI_CHECKOUT_INTEGRAL_PASSWORD'),
       [\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION,\PDO::ATTR_EMULATE_PREPARES=>false]);
    if((string)$db->query('SELECT DATABASE()')->fetchColumn()!=='mi_checkout_integral_v2_test'
        ||(int)$db->query('SELECT @@port')->fetchColumn()!==18959){
        throw new \RuntimeException('Refused non QA DB identity');
    }
    $mode=$argv[1]??'';
    if(!in_array($mode,['seed','attempt','verify'],true)){throw new \RuntimeException('Invalid mode');}
    function qa(bool $ok,string $msg):void {
        if(!$ok)throw new \RuntimeException('FAIL '.$msg);
        echo 'PASS '.$msg.PHP_EOL;
    }
    if($mode==='seed'){
        foreach(['order_items','orders','inventory','products','checkout_intents'] as $table)
            $db->exec('DROP TABLE IF EXISTS '.$table);
        $db->exec("CREATE TABLE checkout_intents(
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id BIGINT UNSIGNED NOT NULL,address_id BIGINT UNSIGNED NOT NULL,
          token VARCHAR(64) NOT NULL UNIQUE,cart_hash VARCHAR(64) NOT NULL,
          order_id BIGINT UNSIGNED NULL,marketing_campaign_id BIGINT UNSIGNED NULL,
          email_campaign_id BIGINT UNSIGNED NULL,
          status ENUM('created','used','failed') NOT NULL DEFAULT 'created',
          fail_reason VARCHAR(255) NULL,
          amount INT UNSIGNED NOT NULL,currency CHAR(3) NOT NULL DEFAULT 'ARS',
          address_json JSON NOT NULL,items_json JSON NOT NULL,totals_json JSON NOT NULL,
          used_at DATETIME NULL,created_at DATETIME NOT NULL
        ) ENGINE=InnoDB");
        $db->exec("CREATE TABLE products (
          id BIGINT UNSIGNED PRIMARY KEY,is_active TINYINT NOT NULL DEFAULT 1
        ) ENGINE=InnoDB");
        $db->exec("CREATE TABLE inventory (
          product_id BIGINT UNSIGNED PRIMARY KEY,
          stock INT NOT NULL,low_stock_threshold INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB");
        $db->exec("CREATE TABLE orders (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          user_id BIGINT UNSIGNED NULL,status VARCHAR(40) NOT NULL,
          payment_method VARCHAR(32) NULL,checkout_token VARCHAR(64) NULL,
          subtotal INT UNSIGNED NOT NULL,shipping INT UNSIGNED NOT NULL,
          discount INT UNSIGNED NOT NULL,total INT UNSIGNED NOT NULL,
          address_id BIGINT UNSIGNED NULL,shipping_full_name VARCHAR(120) NULL,
          shipping_phone VARCHAR(40) NULL,shipping_street VARCHAR(160) NULL,
          shipping_number VARCHAR(20) NULL,shipping_floor VARCHAR(20) NULL,
          shipping_apartment VARCHAR(20) NULL,shipping_city VARCHAR(80) NULL,
          shipping_state VARCHAR(80) NULL,shipping_zip VARCHAR(20) NULL,
          shipping_notes VARCHAR(255) NULL,shipping_address_json JSON NULL,
          created_at DATETIME NOT NULL,
          UNIQUE KEY uk_orders_checkout_token(checkout_token)
        ) ENGINE=InnoDB");
        $db->exec("CREATE TABLE order_items (
          id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          order_id BIGINT UNSIGNED NOT NULL,product_id BIGINT UNSIGNED NOT NULL,
          variant_id BIGINT UNSIGNED NULL,qty INT UNSIGNED NOT NULL,
          price INT UNSIGNED NULL,unit_list_price INT UNSIGNED NULL,
          unit_cost INT UNSIGNED NULL,product_sku VARCHAR(80) NULL,
          product_name VARCHAR(200) NULL,variant_sku VARCHAR(80) NULL,
          variant_title VARCHAR(200) NULL,variant_attributes_json JSON NULL,
          created_at DATETIME NOT NULL
        ) ENGINE=InnoDB");
        $db->exec("INSERT INTO products(id,is_active) VALUES(1,1)");
        $db->exec("INSERT INTO inventory(product_id,stock) VALUES(1,5)");
        $payload=['user_id'=>7,'address_id'=>9,'cart_hash'=>str_repeat('5',64),
          'token'=>'qa-one-order','status'=>'created','amount'=>3000,'currency'=>'ARS',
          'address_json'=>'{}','items_json'=>json_encode([[
              'id'=>1,'qty'=>2,'price'=>1500,'name'=>'Producto sintetico',
              'sku'=>'qa-p-1'
          ]]),'totals_json'=>json_encode([
             'total'=>3000,'subtotal'=>3000,'shipping'=>0,'discount'=>0,
             'payment_method'=>'tarjeta_credito'
          ])];
        $repo=new \App\Repositories\Checkout\CheckoutIntentRepository($db);
        $id=$repo->create($payload);
        qa($id>0,'seed private checkout intent created');
        echo "READY order concurrency test\n";exit(0);
    }
    $svc=new \App\Services\Orders\OrderFromIntentService($db);
    if($mode==='attempt'){
        [$ok,$msg,$id]=$svc->createOrderFromIntent('qa-one-order');
        echo json_encode(['ok'=>$ok,'msg'=>$msg,'order_id'=>$id],JSON_UNESCAPED_UNICODE).PHP_EOL;
        exit(($ok && $id>0)?0:1);
    }
    if($mode==='verify'){
        $orders=(int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $items=(int)$db->query('SELECT COUNT(*) FROM order_items')->fetchColumn();
        $stock=(int)$db->query('SELECT stock FROM inventory WHERE product_id=1')->fetchColumn();
        $intent=$db->query("SELECT status,order_id FROM checkout_intents WHERE token='qa-one-order'")->fetch(\PDO::FETCH_ASSOC);
        qa($orders===1,'exactly one order across 2 simultaneous callbacks');
        qa($items===1,'exactly one set of order items');
        qa($stock===3,'stock decremented only once');
        qa(($intent['status']??'')==='used'&&(int)($intent['order_id']??0)>0,'intent linked and consumed');
        [$ok,$msg,$id]=$svc->createOrderFromIntent('qa-one-order');
        qa($ok&&(int)$id===(int)$intent['order_id'],'sequential callback idempotent');
        qa((int)$db->query('SELECT stock FROM inventory WHERE product_id=1')->fetchColumn()===3,'later callback never decrements stock');
        echo "[checkout-order-concurrency] OK 6 real OrderFromIntent assertions\n";exit(0);
    }
}
