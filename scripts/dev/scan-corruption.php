<?php
// tools/scan-corruption.php
$root = __DIR__ . '/..';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$bad = [];
foreach ($it as $f) {
  if ($f->getExtension() !== 'php') continue;
  $txt = @file_get_contents($f->getPathname());
  if ($txt === false) continue;
  if (strpos($txt, '...') !== false || strpos($txt, '…') !== false) {
    $bad[] = substr($f->getPathname(), strlen($root)+1);
  }
}
echo "Archivos con '...' o '…' (revisar si son truncados):\n";
foreach ($bad as $p) echo " - $p\n";
echo "\nTotal: " . count($bad) . "\n";
