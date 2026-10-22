<?php
declare(strict_types=1);

$source=(string)file_get_contents(dirname(__DIR__,2).'/public_html/assets/js/checkout/mp_payment_brick.js');
$falseAt=strpos($source,'if (res && res.ok === false)');
$approvedAt=strpos($source,"if (status === 'approved')");
$caseAt=strpos($source,"state: 'manual_reconciliation'");
$lastAt=strpos($source,'const res = await postJson(paymentUrl, payloadJson, csrf);');
$tests=[
    '202 ok=false checked after HTTP response' => $lastAt!==false && $falseAt>$lastAt,
    '202 review checked before approved branch' => $falseAt!==false && $approvedAt!==false && $falseAt<$approvedAt,
    'review state rendered' => $caseAt!==false && $caseAt>$falseAt && $caseAt<$approvedAt,
    'API review flag respected' => str_contains($source,'res.manual_reconciliation_required === true'),
    'orphan code recognized' => str_contains($source,"'approved_without_order'"),
    'mismatch code recognized' => str_contains($source,"'approved_payment_requires_review'"),
    'distinct duplicate code recognized' => str_contains($source,"'duplicate_payment_for_intent'"),
    'existing pending payment recognized' => str_contains($source,"code === 'existing_payment_for_intent'"),
    'checkout cannot clear cart while in review' => str_contains($source,'clear_cart: false'),
    'checkout cannot retry while in review' => str_contains($source,'allow_retry: false'),
    'review resolution never declares success' => str_contains($source,'resolve({ ok: false, code,'),
    'support destination provided' => str_contains($source,'ayuda#contacto'),
];
foreach($tests as $name=>$pass){
    if(!$pass){fwrite(STDERR,"[checkout-ui] FAIL $name\n");exit(1);}
}
echo "[checkout-ui] OK ".count($tests)." checks\n";
