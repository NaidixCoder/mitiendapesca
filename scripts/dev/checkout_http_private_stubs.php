<?php
// Test-only stubs in an isolated SQLite HTTP harness; never loaded by production.
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
            ['id'=>1,'product_id'=>1,'qty'=>2,'price'=>1500,'sku'=>'QA','name'=>'Synthetic','is_publicly_available'=>empty($_SESSION['qa_hidden']) ? 1 : 0]
        ],'subtotal'=>3000,'shipping'=>0,'discount'=>0,'total'=>3000];}
    }
    final class CartSelectionService {
        public function apply(array $cart): array {return $cart;}
        public function clear(): void {}
    }
    final class CartWriteService {
        public function __construct(private \PDO $db) {}
        public function remove(int $productId,int $variantId): void {
            return;
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
namespace App\Repositories\Users {
    final class UserRepository {
        public function __construct(private \PDO $db) {}
        public function findById(int $id): array {return ['id'=>$id,'email_verified'=>1];}
    }
}
