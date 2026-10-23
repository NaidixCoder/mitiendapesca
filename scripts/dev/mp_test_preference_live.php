<?php
declare(strict_types=1);
// Nonfinancial provider integration test: create one TEST checkout preference, never charge.
$envFile=dirname(__DIR__,2).'/.env';if(!is_file($envFile))exit(2);
$vars=[];foreach(file($envFile,FILE_IGNORE_NEW_LINES) as $line){if(preg_match('/^\s*(MP_ACCESS_TOKEN|MP_SANDBOX)\s*=\s*(.*)$/',$line,$m))$vars[$m[1]]=trim(trim($m[2])," \"'");}
$token=(string)($vars['MP_ACCESS_TOKEN']??'');$sandbox=strtolower((string)($vars['MP_SANDBOX']??''));
if(!str_starts_with($token,'TEST-')||!in_array($sandbox,['1','true','yes','on'],true)){echo "REFUSED_NON_TEST_SETTINGS\n";exit(2);}
$payload=['items'=>[['title'=>'Validacion sintetica MiTiendaPesca QA','quantity'=>1,'unit_price'=>100,'currency_id'=>'ARS']],'external_reference'=>'qa-connectivity-'.bin2hex(random_bytes(12)),'back_urls'=>['success'=>'https://example.org/test-success','failure'=>'https://example.org/test-failure','pending'=>'https://example.org/test-pending']];
$ch=curl_init('https://api.mercadopago.com/checkout/preferences');
curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Content-Type: application/json','Accept: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>20]);
$body=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
$decoded=is_string($body)?json_decode($body,true):null;
echo 'MP_TEST_PREFERENCE_STATUS='.$status.PHP_EOL;
echo 'MP_TEST_PREFERENCE_HAS_ID='.(is_array($decoded)&&!empty($decoded['id'])?'YES':'NO').PHP_EOL;
echo 'MP_TEST_PREFERENCE_RESULT='.($status>=200&&$status<300&&is_array($decoded)&&!empty($decoded['id'])?'PASS':'FAIL').PHP_EOL;
exit($status>=200&&$status<300&&is_array($decoded)&&!empty($decoded['id'])?0:1);
