<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $f) require_once $f;
require_once BASE_PATH.'/app/Views/helpers.php';

$db=db();
$repo=new App\Repositories\Marketing\EmailCampaignRepository($db);
$queue=new App\Repositories\Marketing\EmailQueueRepository($db);
$before=(int)$db->query("SELECT COUNT(*) FROM email_campaigns")->fetchColumn();
if($db->inTransaction())throw new RuntimeException('Transacción previa inesperada');
$db->beginTransaction();
try{
    $sample=[
        'name'=>'[TEST AISLADO] No enviar',
        'subject'=>'Prueba de transición sin envío',
        'preheader'=>'Transacción revertida',
        'body_html'=>'<p>Contenido controlado</p>',
        'cta_label'=>'',
        'cta_url'=>'',
        'audience'=>'all',
        'audience_params_json'=>null,
        'status'=>'draft',
        'scheduled_at'=>null,
    ];
    $id=$repo->create($sample);
    if($id<=0 || !$repo->canEdit($id))throw new RuntimeException('Fallo alta de borrador');
    $sample['subject']='Prueba editada';
    if(!$repo->update($id,$sample))throw new RuntimeException('Fallo edición de borrador');
    if(($repo->find($id)['subject']??'')!=='Prueba editada')throw new RuntimeException('Cambio no persistió dentro de transacción');
    if(!$repo->update($id,$sample))throw new RuntimeException('UPDATE sin cambios debe ser válido');
    if(!$repo->scheduleNowIfDraft($id))throw new RuntimeException('Fallo transición borrador -> programada');
    if($repo->scheduleNowIfDraft($id))throw new RuntimeException('No debe poder programar dos veces');
    if(!$repo->canEdit($id))throw new RuntimeException('Programada sin destinatarios puede editarse');
    // Simular un destinatario únicamente dentro de la transacción: nunca despachar.
    $queued=$queue->enqueue([
        'dedupe_key'=>hash('sha256','smoke-marketing-email-'.$id),
        'to_email'=>'no-send@example.invalid',
        'subject'=>'Solo simulación',
        'template'=>'marketing/broadcast_campaign',
        'payload_json'=>'{}',
        'entity_type'=>'email_campaign',
        'entity_id'=>$id,
        'scheduled_at'=>'2099-01-01 00:00:00',
    ]);
    if(!$queued)throw new RuntimeException('No se pudo crear destinatario sintético');
    if($repo->canEdit($id))throw new RuntimeException('Campaña con destinatarios no debe editarse');
    $sample['subject']='CAMBIO PROHIBIDO';
    if($repo->update($id,$sample))throw new RuntimeException('Se editó una campaña con cola');
    if(!$repo->cancelIfActive($id))throw new RuntimeException('No pudo cancelarse campaña programada');
    $cancelled=$queue->cancelCampaignQueued($id);
    if($cancelled!==1)throw new RuntimeException('No se canceló su correo en cola');
    if($repo->canEdit($id) || $repo->scheduleNowIfDraft($id) || $repo->cancelIfActive($id))
        throw new RuntimeException('Campaña cancelada admite cambios prohibidos');
    $db->rollBack();
    $after=(int)$db->query('SELECT COUNT(*) FROM email_campaigns')->fetchColumn();
    if($before!==$after)throw new RuntimeException('La transacción alteró recuentos');
    echo "[campaign-lifecycle] OK: crear/editar borrador, transición atómica, cola sintética, bloqueo, cancelación y ROLLBACK (0 envíos)\n";
}catch(Throwable $e){
    if($db->inTransaction())$db->rollBack();
    fwrite(STDERR,"[campaign-lifecycle] ERROR: ".$e->getMessage()."\n");
    exit(1);
}
