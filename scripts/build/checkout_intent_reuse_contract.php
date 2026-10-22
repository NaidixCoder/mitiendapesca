<?php
declare(strict_types=1);

/** Contract and executable SQLite proof for NULL/0 campaign matching; no real database touched. */
$repo = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Repositories/Checkout/CheckoutIntentRepository.php');
$checks = [
    'marketing nullable comparison' => str_contains($repo, 'COALESCE(marketing_campaign_id, 0) = :m'),
    'email nullable comparison' => str_contains($repo, 'COALESCE(email_campaign_id, 0) = :e'),
    'no campaign saved as NULL' => str_contains($repo, "'marketing_campaign_id'] ?? 0) ?: null") &&
        str_contains($repo, "'email_campaign_id'] ?? 0) ?: null"),
    'match user/address/hash/status' => str_contains($repo, "user_id=:u AND address_id=:a AND status='created' AND cart_hash=:h"),
    'reusable interval bounded' => str_contains($repo, 'max(1, (int)$maxAgeMinutes)') &&
        str_contains($repo, 'INTERVAL {$maxAgeMinutes} MINUTE'),
];
$count = 0;
foreach ($checks as $title => $value) {
    ++$count;
    if (!$value) { fwrite(STDERR, "[checkout-intent] FAIL $title\n"); exit(1); }
}
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    fwrite(STDERR, "pdo_sqlite required for contract\n"); exit(2);
}
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec("CREATE TABLE intents(user_id INT,address_id INT,cart_hash TEXT,status TEXT,marketing_campaign_id INT NULL,email_campaign_id INT NULL)");
$rows = [
  [3,7,'h','created',null,null],
  [3,7,'h','created',9,null],
  [3,7,'h','created',null,12],
  [3,7,'h','created',9,12],
  [3,7,'h','used',null,null],
  [4,7,'h','created',null,null],
  [3,8,'h','created',null,null],
  [3,7,'other','created',null,null],
  [3,7,'h','created',0,0],
];
$insert = $db->prepare('INSERT INTO intents VALUES (?,?,?,?,?,?)');
foreach ($rows as $r) $insert->execute($r);
$st = $db->prepare("SELECT COUNT(*) FROM intents WHERE user_id=? AND address_id=? AND status='created' AND cart_hash=? AND COALESCE(marketing_campaign_id,0)=? AND COALESCE(email_campaign_id,0)=?");
foreach ([[0,0,2], [9,0,1], [0,12,1], [9,12,1], [9,12,1], [12,9,0], [1,0,0]] as [$m,$e,$expected]) {
    $st->bindValue(1, 3, PDO::PARAM_INT);
    $st->bindValue(2, 7, PDO::PARAM_INT);
    $st->bindValue(3, 'h', PDO::PARAM_STR);
    $st->bindValue(4, $m, PDO::PARAM_INT);
    $st->bindValue(5, $e, PDO::PARAM_INT);
    $st->execute();
    if ((int)$st->fetchColumn() !== $expected) {
        fwrite(STDERR, "[checkout-intent] FAIL campaign pair $m/$e\n"); exit(1);
    }
    ++$count;
}
echo "[checkout-intent] OK $count assertions (static + executable NULL/zero SQL)\n";
