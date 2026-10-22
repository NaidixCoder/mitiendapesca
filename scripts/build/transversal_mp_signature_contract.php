<?php
declare(strict_types=1);
/**
 * Cross-module MP webhook HMAC contract. Pure synthetic values; no HTTP, DB
 * credentials, provider connection, external payments or boot/.env usage.
 */
require_once dirname(__DIR__,2).'/app/Controllers/Webhooks/MercadoPagoWebhookController.php';

function env(string $key, string $default=''):string {
    return $key === 'MP_WEBHOOK_SIGNATURE_SECRET' ? 'synthetic-test-only-secret' : $default;
}
$hook=new \App\Controllers\Webhooks\MercadoPagoWebhookController();
$validate=(new ReflectionMethod($hook,'isSignatureValid'));
$method=(new ReflectionMethod($hook,'resolveNotificationDataId'));
$n=0;
$check=static function(bool $yes,string $msg) use (&$n):void {
    $n++;
    if(!$yes){fwrite(STDERR,"[mp-webhook-hmac] FAIL $n: $msg\n");exit(1);}
    echo "PASS $n: $msg\n";
};
$id='QA123ABC';
$requestId='synthetic-request-321';
$ts='1782158971000';
$_SERVER=['REQUEST_URI'=>'/webhooks/mercadopago?data.id='.$id.'&type=payment',
    'HTTP_X_REQUEST_ID'=>$requestId];
$payload=['data'=>['id'=>'not-the-query-id'],'type'=>'payment'];
$normalized='qa123abc';
$manifest="id:$normalized;request-id:$requestId;ts:$ts;";
$digest=hash_hmac('sha256',$manifest,'synthetic-test-only-secret');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts,v1=$digest";

$check($method->invoke($hook,$payload)===$normalized,'query data.id canonicalizes uppercase letters');
$check($validate->invoke($hook,$payload)===true,'valid signed payment event accepted');
$check($validate->invoke($hook,['data'=>['id'=>'other']])===true,'signed query ID wins over divergent body ID');
$_SERVER['HTTP_X_SIGNATURE']="v1=$digest, ts=$ts";
$check($validate->invoke($hook,$payload)===true,'header accepts signature fields in either order');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts,v1=".strtoupper($digest);
$check($validate->invoke($hook,$payload)===true,'hex digest casing normalized');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts,v1=".str_repeat('0',64);
$check($validate->invoke($hook,$payload)===false,'forged HMAC denied');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts,v1=$digest";
$_SERVER['HTTP_X_REQUEST_ID']='wrong-request-id';
$check($validate->invoke($hook,$payload)===false,'tampered request ID denied');
$_SERVER['HTTP_X_REQUEST_ID']=$requestId;
$_SERVER['REQUEST_URI']='/webhooks/mercadopago?data.id=OTHER777&type=payment';
$check($validate->invoke($hook,$payload)===false,'tampered payment ID denied');
$_SERVER['REQUEST_URI']='/webhooks/mercadopago?data.id='.$id.'&type=payment';
unset($_SERVER['HTTP_X_SIGNATURE']);
$check($validate->invoke($hook,$payload)===false,'missing signature denied');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts,v1=$digest";
unset($_SERVER['HTTP_X_REQUEST_ID']);
$check($validate->invoke($hook,$payload)===false,'missing request ID denied');
$_SERVER['HTTP_X_REQUEST_ID']=$requestId;
$_SERVER['HTTP_X_SIGNATURE']="v1=$digest";
$check($validate->invoke($hook,$payload)===false,'missing timestamp denied');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts";
$check($validate->invoke($hook,$payload)===false,'missing digest denied');

$_SERVER['REQUEST_URI']='/webhooks/mercadopago?type=payment';
$body=['data'=>['id'=>'55']];
$numericDigest=hash_hmac('sha256',"id:55;request-id:$requestId;ts:$ts;", 'synthetic-test-only-secret');
$_SERVER['HTTP_X_SIGNATURE']="ts=$ts,v1=$numericDigest";
$check($method->invoke($hook,$body)==='55','body fallback resolves numeric payment ID');
$check($validate->invoke($hook,$body)===true,'signed body fallback accepted');
$_SERVER['REQUEST_URI']='/webhooks/mercadopago?data.id=OTHER777&type=payment';
$check($validate->invoke($hook,$body)===false,'body signature cannot authorize different query payment ID');

echo "[mp-webhook-hmac] OK $n signed-webhook assertions (no network)\n";
