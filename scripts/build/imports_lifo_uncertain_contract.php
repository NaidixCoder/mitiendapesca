<?php
declare(strict_types=1);
/** V1D-C source-level guards; SQL/HTTP behavior covered by isolated suite. */
$root=dirname(__DIR__,2);
$repo=file_get_contents($root.'/app/Repositories/Imports/ImportRevertRepository.php');
$svc=file_get_contents($root.'/app/Services/Import/ImportRevertService.php');
$count=0;
$check=static function(bool $ok,string $label)use(&$count):void{
    $count++;
    if(!$ok)throw new RuntimeException('FAIL '.$label);
};
$check(str_contains($repo,'function lockPendingReconciliationJobIds()'),
    'Dedicated global reconciliation query');
$check(str_contains($repo,'$this->db->inTransaction()'),
    'No unprotected lock outside transaction');
$check(str_contains($repo,'status') && str_contains($repo,"'review_required'"),
    'Explicit review_required status');
$check(str_contains($repo,'FROM import_jobs FORCE INDEX(PRIMARY) ORDER BY id DESC FOR UPDATE'),
    'Full primary-index scan with row and gap locks');
$check(!str_contains($repo,'WHERE status=\'review_required\''),
    'No partial status-range phantom gap');
$check(str_contains($svc,'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ'),
    'Isolation explicitly enforced');
$check(strpos($svc,'$this->db->beginTransaction();') <
    strpos($svc,'lockPendingReconciliationJobIds()'),'Guard occurs within transaction');
$check(strpos($svc,'lockPendingReconciliationJobIds()') <
    strpos($svc,'lockLatestPendingJobId()'),'Global gate before LIFO');
$check(strpos($svc,'lockLatestPendingJobId()') <
    strpos($svc,'lockJob($jobId)'),'Consistent lock order');
$check(strpos($svc,'lockJob($jobId)') <
    strpos($svc,'$lockedStatus = '),'Target status rechecked under lock');
$check(strpos($svc,"review_required_pending") <
    strpos($svc,'$snapshotSafe)'),'Global review takes precedence over snapshot validation');
$check(str_contains($svc,"review_required_pending"),
    'Specific blocked reason');
$check(str_contains($svc,'Reversión suspendida: existen importaciones'),
    'Clear public explanation');
$check(str_contains($svc,"'review_required_pending', 'job_not_eligible'"),
    'Global block does not mutate revert_status');
$check(strpos($svc,"review_required_pending") <
    strpos($svc,'->restore('),'Review guard before commercial restoration');
$check(strpos($svc,"review_required_pending") <
    strpos($svc,'->deleteResult('),'Review guard before commercial deletion');
$check(str_contains($svc,"'import_revert_blocked'"),
    'Blocked attempts audited');
$check(str_contains($svc,'already_reverted') &&
    str_contains($svc,"not_last"),
    'Existing idempotency and LIFO retained');
$check(str_contains($svc,'isSnapshotSafe(') &&
    str_contains($svc,'fingerprint($ent, $id, true)'),
    'V3 verification unchanged');
echo "OK: $count verificaciones de LIFO global con conciliación incierta.".PHP_EOL;
