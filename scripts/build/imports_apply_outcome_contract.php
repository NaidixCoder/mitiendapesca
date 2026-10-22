<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
use App\Services\Import\ImportResult;
$count=0;
$check=static function(bool $pass,string $message)use(&$count):void{
    $count++;
    if(!$pass)throw new RuntimeException('FAIL '.$message);
};
$r=new ImportResult();
$check($r->applyOutcome===ImportResult::APPLY_NOT_STARTED,'default fail closed');
$check($r->toArray()['apply_outcome']===ImportResult::APPLY_NOT_STARTED,'default serialized');
foreach([ImportResult::APPLY_COMMITTED,ImportResult::APPLY_ROLLED_BACK,
    ImportResult::APPLY_COMMIT_UNCERTAIN,ImportResult::APPLY_NOT_STARTED] as $status) {
    $r->setApplyOutcome($status);
    $check($r->applyOutcome===$status,'typed outcome '.$status);
    $check($r->toArray()['apply_outcome']===$status,'serializes '.$status);
}
try {$r->setApplyOutcome('finished'); throw new RuntimeException('invalid accepted');}
catch(InvalidArgumentException $e) {$check(true,'invalid status rejected');}
$r->addError(7,'Error de prueba por fila');
$r->setApplyOutcome(ImportResult::APPLY_COMMITTED);
$check($r->errors===1 && $r->applyOutcome===ImportResult::APPLY_COMMITTED,
    'row errors independent of SQL transaction outcome');
$code=file_get_contents($root.'/app/Services/Import/ImportService.php');
$check(str_contains($code,'APPLY_COMMITTED'),'SQL commit mapped explicitly');
$check(str_contains($code,'APPLY_ROLLED_BACK'),'SQL rollback mapped explicitly');
$check(str_contains($code,'APPLY_COMMIT_UNCERTAIN'),'ACK lost mapped explicitly');
$check(strpos($code,'$commitConfirmed = true;') < strpos($code,'setApplyOutcome(ImportResult::APPLY_COMMITTED)'),
    'commit outcome assigned after confirmation');
$controller=file_get_contents($root.'/app/Controllers/Admin/ImportsController.php');
$start=strpos($controller,'$real = $service->run($type, (string)$realPath, false, $opt);');
$stop=strpos($controller,'    // Compatibilidad: algunos enlaces',$start);
$segment=substr($controller,$start,$stop-$start);
$check(str_contains($segment,'$real->applyOutcome'),'controller reads explicit result');
$check(str_contains($segment,'APPLY_ROLLED_BACK'),'controller rollback branch');
$check(str_contains($segment,'APPLY_NOT_STARTED'),'controller no transaction branch');
$check(str_contains($segment,'markFailed('),'failed job persisted');
$check(str_contains($segment,'markReviewRequired('),'uncertain job persisted');
$check(str_contains($segment,"'import_commit_uncertain'"),'uncertain audit');
$check(str_contains($segment,"'import_apply_failed'"),'rollback audit');
$check(str_contains($segment,"'import_finished'"),'committed audit preserved');
$check(substr_count($segment,'markFinished(')===1,'finish only in committed branch');
$check(strpos($segment,'markFinished(')>strpos($segment,'APPLY_COMMITTED'),
    'finish guarded after committed branch');
$check(str_contains($segment,"'apply_outcome'"),'diagnostic payload preserves type');
$repo=file_get_contents($root.'/app/Repositories/Imports/ImportJobRepository.php');
$check(str_contains($repo,"SET status='review_required'") && str_contains($repo,'markReviewRequired'),
    'review_required stored within existing VARCHAR schema');
$present=file_get_contents($root.'/app/Services/Admin/Imports/ImportsViewPresenterService.php');
$check(str_contains($present,"$".'status === \'review_required\''),'history label');
$notice=file_get_contents($root.'/app/Views/partials/admin/imports/view/notices.php');
$check(str_contains($notice,'Revisión manual obligatoria'),'detail uncertain warning');
echo 'OK: '.$count.' verificaciones de ImportResult, controlador, estados y avisos.'.PHP_EOL;
