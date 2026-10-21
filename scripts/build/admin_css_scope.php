<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$path='css/admin-v3/components/admin-components.css';
$css=@file_get_contents($root.'/public_html/assets/'.$path);
$head=@file_get_contents($root.'/app/Views/partials/head.php');
if ($css===false || $head===false) throw new RuntimeException('CSS o cargador ausente');
$a=strpos($head,'$adminStyles = [');
$b=strpos($head,'$publicStyles = [');
if($a===false||$b===false||$b<=$a) throw new RuntimeException('Bloques de estilos no encontrados');
$admin=substr($head,$a,$b-$a);
$public=substr($head,$b);
if(substr_count($admin,"'".$path."'")!==1) throw new RuntimeException('Componente administrativo ausente o duplicado');
if(str_contains($public,"'".$path."'")) throw new RuntimeException('CSS administrativo incluido en publico');
if(!str_contains($css,'body[data-page^="admin"]')) throw new RuntimeException('Falta aislamiento base de administración');
echo "[admin-css] OK: hoja exclusiva de admin y cargada una sola vez\n";
