<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_pesca_qa_local')throw new RuntimeException('REFUSED');
$repo=new \App\Repositories\Payments\PaymentRepository($db);
$db->beginTransaction();
try{
 $providerId='qa-main-test-'.bin2hex(random_bytes(6));
 $data=['provider'=>'qa_simulated','provider_payment_id'=>$providerId,'provider_preference_id'=>'qa_no_external','status'=>'pending','amount'=>2300,'currency'=>'ARS','intent_id'=>0,'order_id'=>null,'raw_json'=>'{"qa":true}'];
 $first=$repo->createOrUpdateByProviderPaymentId($data);
 if($first<=0)throw new RuntimeException('FAIL create pending');
 echo "PASS PAYMENT_SQL pending\n";
 $second=$repo->createOrUpdateByProviderPaymentId($data);
 if($first!==$second)throw new RuntimeException('FAIL duplicate idempotence');
 echo "PASS PAYMENT_SQL duplicate same id\n";
 $data['status']='approved';$third=$repo->createOrUpdateByProviderPaymentId($data);
 if($third!==$first || ($repo->findByProviderPaymentId('qa_simulated',$providerId)['status']??'')!=='approved')throw new RuntimeException('FAIL state approval');
 echo "PASS PAYMENT_SQL pending -> approved\n";
 if($repo->findAttachedOrderIdByProviderPaymentId('qa_simulated',$providerId)!==0)throw new RuntimeException('FAIL orphan');
 echo "PASS PAYMENT_SQL orphan not falsely attached\n";
 $row=$repo->findByProviderPaymentId('qa_simulated',$providerId);
 if((int)$row['amount']!==2300 || $row['currency']!=='ARS')throw new RuntimeException('FAIL amounts');
 echo "PASS PAYMENT_SQL amount currency\n";
 $db->rollBack();
 if($repo->findByProviderPaymentId('qa_simulated',$providerId)!==null)throw new RuntimeException('FAIL rollback');
 echo "PASS PAYMENT_SQL rollback\n";
 echo "QA_PAYMENT_SQL_OK 6 checks no external services\n";
} finally {if($db->inTransaction())$db->rollBack();}
