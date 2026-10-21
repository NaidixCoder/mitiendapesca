<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$dirs=['app/Views/pages/admin','app/Views/partials/admin','app/Controllers/Admin','app/Services/Admin','public_html/assets/js/admin'];
$results=[];
foreach($dirs as $dir){
 $base=$root.'/'.$dir;if(!is_dir($base))continue;
 foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS)) as $f){
  if(!$f->isFile())continue;
  $src=file_get_contents($f->getPathname());if($src===false)continue;
  $n=0;foreach(preg_split('/\r\n|\n|\r/',$src) as $line){
   if(preg_match('/(?:Ã.|Â.|â€|�|\x{FFFD})/u',$line))$n++;
  }
  if($n)$results[]=[$n,str_replace('\\','/',substr($f->getPathname(),strlen($root)+1))];
 }
}
usort($results,fn($a,$b)=>$b[0]<=>$a[0]);
echo 'AFFECTED_FILES='.count($results)."\n";
foreach($results as [$n,$path])echo "$n $path\n";
