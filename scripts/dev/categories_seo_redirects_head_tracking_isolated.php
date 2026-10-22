<?php
declare(strict_types=1);
/** Read-only smoke; requires the exact disposable DB name. */
$expected=(string)getenv('CATS_HEAD_UTM_FIXTURE_DB');
if ($expected !== 'mi_pesca_cat_w6cb2_20261022') {
    fwrite(STDERR, "[head-tracking-isolated] REFUSED: disposable DB required\n");exit(2);
}
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='127.0.0.1:18163';
$_SERVER['SCRIPT_NAME']='/index.php';
$_SERVER['REQUEST_METHOD']='GET';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') ?: [] as $file)require_once $file;
$db=db();
if((string)$db->query('SELECT DATABASE()')->fetchColumn()!==$expected){
    fwrite(STDERR,"[head-tracking-isolated] REFUSED: wrong DB\n");exit(2);
}
$resolver=new \App\Services\Seo\PublicRedirectTargetResolver($db);
$cases=[
    '/old-cat-visible'=>'/categoria?slug=root-ok',
    '/old-brand'=>'/marca?slug=marca-qa',
    '/old-direct'=>'/producto?slug=good-item',
    '/old-first'=>'/producto?slug=good-item',
];
foreach($cases as $old=>$final){
    $r=$resolver->resolveCleanedRequest($old);
    if(!is_array($r)||$r['target']!==$final||empty($r['historical']))
      throw new \RuntimeException('Unexpected historical redirect '.$old);
}
$legacy=$resolver->resolveCleanedRequest('/productos?categoria=root-ok&q=cana');
if(!is_array($legacy)||($legacy['target']??'')!=='/categoria?slug=root-ok&q=cana')
 throw new \RuntimeException('Legacy filtered canonicalization failed');
foreach(['/old-cycle-a','/old-out','/old-cat-hidden','/old-inactive','/productos?categoria=child-parent-off'] as $path)
 if($resolver->resolveCleanedRequest($path)!==null)
  throw new \RuntimeException('Invalid canonical target '.$path);
echo '[head-tracking-isolated] PASS synthetic DB; 4 historic, filtered legacy, 5 denied\n';
