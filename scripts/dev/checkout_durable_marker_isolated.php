<?php
declare(strict_types=1);
/** Test of durable pre-charge marker. Strict private MariaDB identity. */
$dsn=(string)getenv('MI_CHECKOUT_INTEGRAL_DSN');
if ($dsn!=='mysql:host=127.0.0.1;port=18959;dbname=mi_checkout_integral_v2_test;charset=utf8mb4'){
    fwrite(STDERR,"REFUSED DSN\n");exit(2);
}
$db=new PDO($dsn,(string)getenv('MI_CHECKOUT_INTEGRAL_USER'),
    (string)getenv('MI_CHECKOUT_INTEGRAL_PASSWORD'),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
if((int)$db->query('SELECT @@port')->fetchColumn()!==18959 ||
    $db->query('SELECT DATABASE()')->fetchColumn()!=='mi_checkout_integral_v2_test'){
    throw new RuntimeException('Not the private QA database');
}
require dirname(__DIR__,2).'/app/Repositories/Checkout/CheckoutIntentRepository.php';
$repo=new App\Repositories\Checkout\CheckoutIntentRepository($db);
$cartHash=str_repeat('c',64);
$token='qa-durable-payment-990';
$id=0;
$n=0;
$ok=static function(bool $test,string $label) use (&$n):void{
   if(!$test)throw new RuntimeException('FAIL '.$label);
   ++$n;echo 'PASS '.$label.PHP_EOL;
};
try {
    $statement=$db->prepare(
        "INSERT INTO checkout_intents (user_id,address_id,token,cart_hash,status,
           amount,currency,address_json,items_json,totals_json,created_at)
         VALUES (990,991,:token,:hash,'created',1500,'ARS','{}','[]','{}',NOW())"
    );
    $statement->execute([':token'=>$token,':hash'=>$cartHash]);
    $id=(int)$db->lastInsertId();
    $ok($id>0,'created synthetic intent');
    $ok($repo->markPaymentInFlight($id),'durable marker set before MP call');
    $ok($repo->findInFlightForCart(990,991,$cartHash)!==null,'reused checkout blocked while payment unresolved');
    $ok(!$repo->markPaymentInFlight($id),'cannot mark identical unresolved intent twice');
    $ok($repo->findInFlightForCart(990,991,str_repeat('d',64))===null,'other cart not blocked');
    $ok($repo->findInFlightForCart(991,991,$cartHash)===null,'other user not blocked');
    $db->exec("UPDATE checkout_intents SET status='used' WHERE id=".$id);
    $ok($repo->findInFlightForCart(990,991,$cartHash)===null,'fulfilled used intent no longer blocks');
    $db->exec("UPDATE checkout_intents SET status='created' WHERE id=".$id);
    $repo->clearPaymentInFlight($id);
    $ok($repo->findInFlightForCart(990,991,$cartHash)===null,'marker cleared after provider record persisted');
    $ok($repo->markPaymentInFlight($id),'a later authorized attempt can set a fresh marker');
    $repo->clearPaymentInFlight($id);
    $ok($repo->findInFlightForCart(990,991,$cartHash)===null,'final cleanup leaves no active marker');
    echo "[checkout-durable-marker] OK $n private MariaDB assertions\n";
} finally {
    if($id>0)$db->prepare('DELETE FROM checkout_intents WHERE id=?')->execute([$id]);
}
