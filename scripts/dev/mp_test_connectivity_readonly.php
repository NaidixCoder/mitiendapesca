<?php
declare(strict_types=1);
// Read-only connectivity probe. Does not submit a payment or expose a credential.
$envFile=dirname(__DIR__,2).'/.env';
if(!is_file($envFile)){fwrite(STDERR,"ENV_MISSING\n");exit(2);}
$token='';
foreach(file($envFile, FILE_IGNORE_NEW_LINES) as $line){
 if(preg_match('/^\s*MP_ACCESS_TOKEN\s*=\s*(.*)$/',$line,$m))$token=trim(trim($m[1])," \"'");
}
if(!str_starts_with($token,'TEST-')){fwrite(STDERR,"REFUSED_NON_TEST_TOKEN\n");exit(2);}
$ch=curl_init('https://api.mercadopago.com/v1/payment_methods');
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$token,'Accept: application/json'],CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>8]);
$response=curl_exec($ch);
$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);
$error=curl_error($ch);curl_close($ch);
echo 'MP_READ_ONLY_HTTP_STATUS='.$status.PHP_EOL;
if($response===false){echo 'MP_NETWORK_FAILURE='.($error!==''?'YES':'UNKNOWN').PHP_EOL;exit(1);}
$decoded=json_decode($response,true);
echo 'MP_RESPONSE_JSON='.(is_array($decoded)?'YES':'NO').PHP_EOL;
echo 'MP_TEST_CONNECTIVITY='.($status===200&&is_array($decoded)?'PASS':'NOT_CONFIRMED').PHP_EOL;
exit($status===200&&is_array($decoded)?0:1);
