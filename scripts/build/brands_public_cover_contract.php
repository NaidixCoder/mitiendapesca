<?php
declare(strict_types=1);
/**
 * Contract executes the real public brands view with synthetic data.
 * No DB, network, sessions or real files.
 */
$GLOBALS['qaBrandCovers'] = [];
function public_url(string $path): string {
    $GLOBALS['qaBrandCovers'][] = $path;
    return 'https://store.local/'.ltrim($path, '/');
}
function url(string $path = ''): string {
    return 'https://store.local/'.ltrim($path, '/');
}
function section(string $name, array $data): void {
    if ($name !== 'partials/directory/page') throw new RuntimeException('Wrong directory component');
    $GLOBALS['brandViewPayload'] = $data;
}
$brands = [
    ['slug'=>'remote','name'=>'Portada remota','product_count'=>3,
        'cover_image'=>'https://cdn.example.test/media/marca.png?version=3'],
    ['slug'=>'remote-http','name'=>'Portada HTTP','product_count'=>2,
        'cover_image'=>'http://cdn.example.test/media/marca.jpg'],
    ['slug'=>'local','name'=>'Portada local','product_count'=>5,
        'cover_image'=>'/uploads/brands/fixture.webp'],
    ['slug'=>'legacy','name'=>'Portada relativa','product_count'=>1,
        'cover_image'=>'uploads/brands/legacy.png'],
];
require dirname(__DIR__,2).'/app/Views/pages/catalogo/marcas.php';
$items = $GLOBALS['brandViewPayload']['items'] ?? [];
$checks = [
    'four brands retained' => count($items)===4,
    'HTTPS remote image not prefixed' => ($items[0]['image_url']??null)===$brands[0]['cover_image'],
    'HTTP remote image not prefixed' => ($items[1]['image_url']??null)===$brands[1]['cover_image'],
    'local absolute path resolved once' => ($items[2]['image_url']??null)==='https://store.local/uploads/brands/fixture.webp',
    'legacy relative path resolved once' => ($items[3]['image_url']??null)==='https://store.local/uploads/brands/legacy.png',
    'no remote URL passed to public_url' => $GLOBALS['qaBrandCovers']===[
        '/uploads/brands/fixture.webp','uploads/brands/legacy.png'
    ],
    'local brand href preserved' => ($items[2]['href']??null)==='https://store.local/marca?slug=local',
    'original counts preserved' => ($items[0]['count']??null)===3 && ($items[2]['count']??null)===5,
];
foreach($checks as $title=>$ok) {
    if(!$ok){fwrite(STDERR, "[brands-public-cover] FAIL: $title\n");exit(1);}
}
echo '[brands-public-cover] OK '.count($checks).' executable assertions'.PHP_EOL;
