<?php
declare(strict_types=1);
/** Contrato de política I05 + diagnóstico de UI; complementa MySQL/HTTP privados. */
$root=dirname(__DIR__,2);
$svc=(string)file_get_contents($root.'/app/Services/Import/ImportService.php');
$ctrl=(string)file_get_contents($root.'/app/Controllers/Admin/ImportsController.php');
$present=(string)file_get_contents($root.'/app/Services/Admin/Imports/ImportsViewPresenterService.php');
$vm=(string)file_get_contents($root.'/app/Services/Admin/Imports/ViewModels/ImportsViewViewModel.php');
$summary=(string)file_get_contents($root.'/app/Views/partials/admin/imports/view/summary.php');
$applied=(string)file_get_contents($root.'/app/Views/partials/admin/imports/view/applied_changes.php');
$rows=(string)file_get_contents($root.'/app/Views/partials/admin/imports/view/rows.php');
$n=0;
function guardI05(bool $ok,string $message):void {
 global $n;$n++;
 if(!$ok)throw new RuntimeException('FAIL '.$message);
}
$offsetErrors=strpos($svc,'if ($inTx && $res->errors > 0)');
$offsetSnapshot=strpos($svc,'$snapshotWriter->prepare(');
$offsetCommit=strpos($svc,'$this->db->commit()');
guardI05($offsetErrors!==false&&$offsetSnapshot!==false&&$offsetCommit!==false
    &&$offsetErrors<$offsetSnapshot&&$offsetErrors<$offsetCommit,
    'Errors abort before snapshot publication and COMMIT');
guardI05(str_contains($svc,'$atomicityRejected = true;'),'Explicit I05 rejection flag');
guardI05(str_contains($svc,'throw new \\RuntimeException(\'I05: se cancela el CSV completo'),
    'I05 direct service fails closed');
guardI05(str_contains($svc,'if (!$atomicityRejected)'),'No artificial duplicate error row for I05');
guardI05(str_contains($svc,'$rollbackConfirmed = $this->db->rollBack()'),
    'SQL rollback actually executed');
guardI05(str_contains($svc,'ImportResult::APPLY_ROLLED_BACK'),'Confirmed rollback typed outcome');
guardI05(str_contains($svc,'ImportResult::APPLY_COMMIT_UNCERTAIN'),'Commit uncertain not flattened');
guardI05(str_contains($ctrl,'import_apply_failed'),'Failed outcome audited');
guardI05(str_contains($ctrl,'import_dry_failed'),'Dry failed outcome audited');
guardI05(str_contains($ctrl,"if ((int)".'$real->errors > 0)'),
    'No finished on inconsistent committed outcome');
guardI05(str_contains($ctrl,'markReviewRequired'),'Review-required remains fail-closed');
guardI05(str_contains($ctrl,"markFinished("),'Successful jobs persist status');
guardI05(str_contains($ctrl,'_errors.json'),'Persist detailed diagnostics');
guardI05(str_contains($ctrl,"'import_dry_failed'"),'Actor lookup for failed preview');
guardI05(str_contains($vm,"'has_diagnostics'"),'ViewModel distinguishes missing diagnostic');
guardI05(str_contains($present,"'hasDiagnostics'"),'Presenter validates diagnostic presence');
guardI05(str_contains($summary,'Productos y variantes'),'Job type human-readable');
guardI05(str_contains($summary,'Sin operador registrado en auditoría'),'Unknown actor not invented');
guardI05(str_contains($applied,'El resumen por fila no está disponible'),
    'No false absence of historical changes');
guardI05(str_contains($rows,'Diagnóstico por fila no disponible'),
    'No false zero detailed rows on missing diagnostic');
guardI05(str_contains($present,'Válida · no aplicada'),
    'Cancelled row does not pretend to be applied');
echo "OK: {$n} comprobaciones de atomicidad I05 y detalle histórico.".PHP_EOL;
