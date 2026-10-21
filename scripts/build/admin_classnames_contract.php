<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$map=json_decode((string)file_get_contents($root.'/docs/MIGRACION_CLASES_ADMIN_V3.json'),true);
if(!is_array($map)||count($map)<50)throw new RuntimeException('Inventario de clases admin-v3 ausente');
$roots=['app/Views/partials/admin','app/Views/pages/admin','public_html/assets/js/admin','public_html/assets/css/admin-v3'];
$checked=0;
foreach($roots as $dir){
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$dir,FilesystemIterator::SKIP_DOTS));
 foreach($it as $item){
  if(!$item->isFile()||!in_array(strtolower($item->getExtension()),['css','php','js'],true))continue;
  $src=(string)file_get_contents($item->getPathname());$checked++;
  foreach(array_keys($map) as $legacy){
   if(preg_match('/(?<![\\w-])'.preg_quote($legacy,'/').'(?![\\w-])/',$src)){
    throw new RuntimeException('Clase legacy reintroducida: '.$legacy.' en '.$item->getPathname());
   }
  }
 }
}
echo "[admin-v3] OK: $checked archivos sin clases legacy migradas\n";
