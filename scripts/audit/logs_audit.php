<?php
declare(strict_types=1);
/** Read-only audit: reports log sizes and potentially sensitive fields, never their values. */
$root=dirname(__DIR__,2);
$dir=$root.'/storage/logs';
if (!is_dir($dir)) { fwrite(STDERR,"[logs] No existe storage/logs\n"); exit(1); }
$patterns=[
 'token'=>'/(?:\\btoken\\b|access_token|refresh_token)\\s*[:=]/i',
 'email'=>'/[A-Z0-9._%+\\-]+@[A-Z0-9.\\-]+\\.[A-Z]{2,}/i',
 'documento'=>'/\\b(?:dni|cuit|document_number|identification)\\b/i',
 'payload_pago'=>'/PaymentController@payment payload=/i',
];
foreach (new DirectoryIterator($dir) as $file) {
 if (!$file->isFile() || strtolower($file->getExtension())!=='log') continue;
 $counts=array_fill_keys(array_keys($patterns),0);
 $lines=0;
 $fh=fopen($file->getPathname(),'rb');
 if(!$fh)continue;
 while(($line=fgets($fh))!==false) {
  ++$lines;
  foreach($patterns as $type=>$pattern) if(preg_match($pattern,$line))++$counts[$type];
 }
 fclose($fh);
 echo '[logs] '.$file->getFilename().' bytes='.$file->getSize().' lines='.$lines.' indicators='.json_encode($counts)."\n";
}
