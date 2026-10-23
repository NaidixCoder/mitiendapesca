<?php
declare(strict_types=1);
// Regression: real CheckoutOrderService with synthetic in-memory DB and isolated stubs.
// No .env, live DB, payments, mail or HTTP calls.
namespace App\Repositories\Account {
    final class AddressRepository {
        public function __construct(private \PDO $db) {}
        public function findForUser(int $addressId, int $userId): ?array {
            return $addressId === 9 && $userId === 7
                ? ['id'=>9,'full_name'=>'QA','street'=>'Test','number'=>'1','city'=>'QA','state'=>'SF','zip'=>'1234'] : null;
        }
    }
}
namespace App\Repositories\Catalog {
    final class InventoryRepository {
        public function __construct(private \PDO $db) {}
        public function lockStockForUpdate(int $id): ?int {
            return (int)$this->db->query('SELECT stock FROM inventory WHERE product_id=1')->fetchColumn();
        }
        public function decrementStock(int $id,int $qty): void {
            $st=$this->db->prepare('UPDATE inventory SET stock=stock-? WHERE product_id=?');
            $st->execute([$qty,$id]);
        }
    }
}
namespace App\Repositories\Orders {
    final class OrderRepository {
        public function __construct(private \PDO $db) {}
        public function findIdByCheckoutToken(string $token): int {
            $st=$this->db->prepare('SELECT id FROM orders WHERE checkout_token=?');
            $st->execute([$token]);return (int)$st->fetchColumn();
        }
        public function create(array $data): int {
            $st=$this->db->prepare('INSERT INTO orders(checkout_token,total) VALUES (?,?)');
            $st->execute([$data['checkout_token'],$data['total']]);
            return (int)$this->db->lastInsertId();
        }
    }
    final class OrderItemRepository {
        public function __construct(private \PDO $db) {}
        public function bulkInsert(int $orderId,array $items): void {
            $st=$this->db->prepare('INSERT INTO order_items(order_id,product_id,qty) VALUES (?,?,?)');
            foreach ($items as $it) $st->execute([$orderId,$it['product_id'],$it['qty']]);
        }
    }
}
namespace App\Repositories\Settings {
    final class AppSettingsRepository {
        public function __construct(private \PDO $db) {}
        public function get(string $key, mixed $default=null): mixed {return $default;}
    }
}
namespace App\Mail\Orders {
    final class TransactionalMail {
        public function __construct(private \PDO $db) {}
        public function sendOrderCreated(...$args): void {}
        public function sendTransferInstructions(...$args): void {}
    }
}
namespace App\Services\Cart {
    final class CartReadService {
        public function __construct(private \PDO $db) {}
        public function getCartView(): array {return ['items'=>[
            ['id'=>1,'product_id'=>1,'qty'=>2,'price'=>1500,'sku'=>'QA','name'=>'Synthetic']
        ],'subtotal'=>3000,'shipping'=>0,'discount'=>0,'total'=>3000];}
    }
    final class CartSelectionService {
        public function apply(array $cart): array {return $cart;}
        public function clear(): void {throw new \RuntimeException('synthetic cart selection cleanup failure');}
    }
    final class CartWriteService {
        public function __construct(private \PDO $db) {}
        public function remove(int $productId,int $variantId): void {
            throw new \RuntimeException('synthetic postcommit cart cleanup failure');
        }
        public function bulkUpdate(array $quantities): void {}
    }
}
namespace App\Services\Audit {
    final class Audit {
        public static function log(...$args): void {
            throw new \RuntimeException('synthetic postcommit audit failure');
        }
    }
}
namespace App\Services\Pricing {
    final class CommercialPriceService {
        public function __construct(private $settings) {}
        public function buildCheckoutCart(array $cart,string $method): array {return $cart;}
    }
}
namespace App\Support\Security {
    final class CheckoutFiscal {public static function getSession(): array {return [];}}
}
namespace App\Support\Format {
    final class Tax {
        public static function splitTotal(int $sum,float $rate): array {
            $net=(int)round($sum/(1+$rate));return ['net'=>$net,'vat'=>$sum-$net];
        }
    }
}
namespace App\Services\Checkout {
    final class CheckoutProductEligibilityGuard {
        public const ERROR='QA eligibility';
        public function __construct(private \PDO $db) {}
        public function checkItems(array $items,bool $lock=false): array {return [true,'',[]];}
    }
}
namespace {
    function log_error(string $message): void { $GLOBALS['qa_logs'][]=$message; }
    function cache_forget(string $key): void {}
    require dirname(__DIR__,2).'/app/Services/Checkout/CheckoutOrderService.php';
    if (!extension_loaded('pdo_sqlite')) {
        fwrite(STDERR,"REFUSED: pdo_sqlite unavailable\n");exit(2);
    }
    $db=new \PDO('sqlite::memory:');
    $db->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE products (id INTEGER PRIMARY KEY, iva_rate REAL DEFAULT 0)');
    $db->exec('CREATE TABLE inventory (product_id INTEGER PRIMARY KEY,stock INTEGER)');
    $db->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT,checkout_token TEXT UNIQUE,total INTEGER)');
    $db->exec('CREATE TABLE order_items (order_id INTEGER,product_id INTEGER,qty INTEGER)');
    $db->exec('INSERT INTO products(id,iva_rate) VALUES(1,0.21)');
    $db->exec('INSERT INTO inventory(product_id,stock) VALUES(1,5)');
    $_SESSION=['checkout_shipping'=>['cost'=>0,'mode'=>'free']];
    $service=new \App\Services\Checkout\CheckoutOrderService($db);
    [$ok,$msg,$orderId]=$service->confirm(7,9,'transferencia');
    $count=(int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $items=(int)$db->query('SELECT COUNT(*) FROM order_items')->fetchColumn();
    $stock=(int)$db->query('SELECT stock FROM inventory')->fetchColumn();
    if (!$ok || $orderId!==1 || $count!==1 || $items!==1 || $stock!==3) {
        throw new \RuntimeException("FAIL postcommit: ".json_encode([$ok,$msg,$orderId,$count,$items,$stock]));
    }
    echo "PASS postcommit exceptions do not falsely fail committed order\n";
    if (count($GLOBALS['qa_logs']??[]) !== 2) throw new \RuntimeException('FAIL diagnostic logs');
    echo "PASS audit and cart cleanup errors logged\n";
    [$ok2,$msg2,$orderId2]=$service->confirm(7,9,'transferencia');
    $count2=(int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    $stock2=(int)$db->query('SELECT stock FROM inventory')->fetchColumn();
    if (!$ok2 || $orderId2!==$orderId || $count2!==1 || $stock2!==3)
        throw new \RuntimeException('FAIL idempotent transfer replay');
    echo "PASS transfer replay returns same order, no second stock decrement\n";
    echo "[checkout-postcommit-isolated] OK 3 assertions\n";
}
