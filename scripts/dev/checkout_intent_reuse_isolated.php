<?php
declare(strict_types=1);

/** Synthetic MariaDB checkout-intent regression. Never accepts a commercial DSN. */
require_once dirname(__DIR__, 2) . '/app/Repositories/Checkout/CheckoutIntentRepository.php';

use App\Repositories\Checkout\CheckoutIntentRepository;

$dsn = (string)(getenv('MI_CHECKOUT_QA_DSN') ?: '');
if (!preg_match('/^mysql:host=127\.0\.0\.1;port=18949;dbname=mi_checkout_owner_test_20261022(?:;charset=utf8mb4)?$/', $dsn)) {
    fwrite(STDERR, "REFUSED: QA DSN must be isolated 127.0.0.1:18949/mi_checkout_owner_test_20261022\n");
    exit(2);
}

$db = new PDO($dsn, (string)(getenv('MI_CHECKOUT_QA_USER') ?: 'root'),
    (string)(getenv('MI_CHECKOUT_QA_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);

if ((string)$db->query('SELECT DATABASE()')->fetchColumn() !== 'mi_checkout_owner_test_20261022') {
    throw new RuntimeException('Private database identity mismatch');
}

$checkCount = 0;
$assert = static function (bool $ok, string $title) use (&$checkCount): void {
    ++$checkCount;
    if (!$ok) throw new RuntimeException('FAIL: ' . $title);
    echo 'PASS ', $title, PHP_EOL;
};

try {
    $db->exec('DROP TABLE IF EXISTS checkout_intents');
    $db->exec(<<<'SQL'
CREATE TABLE checkout_intents (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 address_id BIGINT UNSIGNED NOT NULL,
 token VARCHAR(64) NOT NULL UNIQUE,
 cart_hash VARCHAR(64) NOT NULL,
 marketing_campaign_id BIGINT UNSIGNED NULL,
 email_campaign_id BIGINT UNSIGNED NULL,
 order_id BIGINT UNSIGNED NULL,
 status ENUM('created','used','failed') NOT NULL DEFAULT 'created',
 amount INT UNSIGNED NOT NULL,
 currency CHAR(3) NOT NULL DEFAULT 'ARS',
 address_json JSON NOT NULL,
 items_json JSON NOT NULL,
 totals_json JSON NOT NULL,
 created_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 INDEX idx_checkout_intents_reuse(user_id,address_id,status,cart_hash,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL);

    $repo = new CheckoutIntentRepository($db);
    $baseline = [
        'user_id' => 7, 'address_id' => 11, 'cart_hash' => str_repeat('b', 64),
        'amount' => 12500, 'currency' => 'ARS', 'address_json' => '{}',
        'items_json' => '[]', 'totals_json' => '{}',
        'marketing_campaign_id' => 0, 'email_campaign_id' => 0,
    ];
    $add = static function (string $tag, array $override = []) use ($repo, $baseline): int {
        return $repo->create(array_merge($baseline, ['token' => 'qa_' . $tag], $override));
    };
    $find = static function (?int $m = null, ?int $e = null, int $u = 7, int $a = 11, ?string $h = null, int $age = 30)
        use ($repo, $baseline): ?array {
        return $repo->findReusable($u, $a, $h ?? $baseline['cart_hash'], $age, $m, $e);
    };

    $none = $add('none');
    $raw = $db->query("SELECT marketing_campaign_id,email_campaign_id FROM checkout_intents WHERE id=$none")->fetch(PDO::FETCH_ASSOC);
    $assert($raw['marketing_campaign_id'] === null && $raw['email_campaign_id'] === null, 'create stores no-campaign as NULL');
    $assert((int)$find()['id'] === $none, 'no-campaign NULL reused');
    $assert((int)$find(0, 0)['id'] === $none, 'explicit zero treated as no-campaign');
    $assert($find(6, null) === null && $find(null, 6) === null, 'positive campaign cannot reuse NULL');

    $market = $add('market', ['marketing_campaign_id' => 9]);
    $assert((int)$find(9, null)['id'] === $market, 'marketing campaign matched');
    $assert((int)$find()['id'] === $none, 'marketing campaign never crosses none');
    $email = $add('email', ['email_campaign_id' => 12]);
    $assert((int)$find(null, 12)['id'] === $email, 'email campaign matched');
    $both = $add('both', ['marketing_campaign_id' => 9, 'email_campaign_id' => 12]);
    $assert((int)$find(9, 12)['id'] === $both, 'dual campaign matched');
    $assert((int)$find(9, null)['id'] === $market && (int)$find(null, 12)['id'] === $email, 'dual campaign does not leak into single campaign');

    $db->exec("UPDATE checkout_intents SET marketing_campaign_id=0,email_campaign_id=0 WHERE id=$none");
    $assert((int)$find()['id'] === $none, 'legacy zero rows are compatible');
    $db->exec("UPDATE checkout_intents SET status='used' WHERE id=$none");
    $assert($find() === null, 'used intent never reused');
    $db->exec("UPDATE checkout_intents SET status='failed' WHERE id=$market");
    $assert($find(9, null) === null, 'failed intent never reused');
    $db->exec("UPDATE checkout_intents SET created_at=DATE_SUB(NOW(), INTERVAL 80 MINUTE) WHERE id=$both");
    $assert($find(9, 12) === null, 'old intent expired');
    $assert((int)$find(9, 12, 7, 11, null, 90)['id'] === $both, 'custom age window honored');
    $assert($find(null, 12, 8) === null, 'user isolation');
    $assert($find(null, 12, 7, 15) === null, 'address isolation');
    $assert($find(null, 12, 7, 11, str_repeat('z', 64)) === null, 'cart hash isolation');
    $assert((int)$find(null, 12)['id'] === $email, 'unrelated updates preserve valid intent');
    echo "[checkout-intent-reuse] OK $checkCount MariaDB checks", PHP_EOL;
} finally {
    $db->exec('DROP TABLE IF EXISTS checkout_intents');
}
