<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$viewBase=$root.'/app/Views';
$component=$viewBase.'/partials/admin/page_header.php';
$css=$root.'/public_html/assets/css/admin-v3/components/page-header.css';
$head=(string)file_get_contents($viewBase.'/partials/head.php');
if(!is_file($component)||!is_file($css)||substr_count($head,"'css/admin-v3/components/page-header.css'")!==1){
    throw new RuntimeException('Contrato de encabezado unificado ausente o duplicado');
}
$componentSource=(string)file_get_contents($component);
if(!str_contains($componentSource,'aria-label="Ruta de navegación"')||!str_contains($componentSource,'aria-current="page"')){
    throw new RuntimeException('Breadcrumb accesible ausente');
}
foreach(['partials/admin/breadcrumbs.php','partials/admin/components/page_header.php'] as $old){
    if(is_file($viewBase.'/'.$old))throw new RuntimeException('Parcial de encabezado legado no eliminado: '.$old);
}
$adminViews=$viewBase.'/pages/admin';
$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($adminViews,FilesystemIterator::SKIP_DOTS));
$pages=[];
foreach($files as $file){
    if(!$file->isFile()||$file->getExtension()!=='php')continue;
    $pages[]=$file->getPathname();
}
$excluded=[
  'attributes/value_set_edit.php',
  'attributes/value_set_items.php',
];
$cache=[];
$traverse=function(string $file,int $depth,array $stack=[]) use (&$traverse,&$cache,$root,$viewBase):bool{
    $file=str_replace('\\','/',$file);
    if(isset($cache[$file]))return $cache[$file];
    if(in_array($file,$stack,true)||$depth>12||!is_file($file))return false;
    $source=(string)file_get_contents($file);
    if(str_contains($source,"partials/admin/page_header.php"))return $cache[$file]=true;
    $stack[]=$file;
    $children=[];
    preg_match_all("~(?:require|include)(?:_once)?\s+BASE_PATH\s*\.\s*['\"](/app/Views/partials/[^'\"]+\.php)['\"]~",$source,$a);
    foreach($a[1] as $name)$children[]=$root.$name;
    preg_match_all("~render_partial\s*\(\s*['\"](admin/[^'\"]+)['\"]~",$source,$b);
    foreach($b[1] as $name)$children[]=$viewBase.'/partials/'.$name.'.php';
    preg_match_all("~section\s*\(\s*['\"](partials/admin/[^'\"]+)['\"]~",$source,$c);
    foreach($c[1] as $name)$children[]=$viewBase.'/'.$name.'.php';
    preg_match_all("~(?:require|include)(?:_once)?\s+__DIR__\s*\.\s*['\"](/[^'\"]+\.php)['\"]~",$source,$d);
    foreach($d[1] as $name)$children[]=dirname($file).$name;
    foreach(array_unique($children) as $child){
      if($traverse($child,$depth+1,$stack))return $cache[$file]=true;
    }
    return $cache[$file]=false;
};
$covered=0;$missing=[];
foreach($pages as $file){
    $relative=str_replace('\\','/',substr($file,strlen($adminViews)+1));
    $source=(string)file_get_contents($file);
    if(str_contains($source,'admin/breadcrumbs')||str_contains($source,'partials/admin/breadcrumbs.php')){
       throw new RuntimeException('Breadcrumb antiguo reintroducido en '.$relative);
    }
    if(in_array($relative,$excluded,true))continue;
    if(!$traverse($file,0))$missing[]=$relative;
    else $covered++;
}
if($missing)throw new RuntimeException('Páginas sin encabezado compartido: '.implode(', ',$missing));
$legacy=(string)file_get_contents($viewBase.'/partials/admin/page_header.php');
if(str_contains($legacy,'admin-v3-page-header'))throw new RuntimeException('Quedó el encabezado de compatibilidad');
echo "[admin-header] OK: {$covered} vistas completas con componente común; ".count($excluded)." fragmentos internos exceptuados; sin breadcrumbs legacy\n";
