<?php
// JSON-LD BreadcrumbList (sin $canonical)
if (!empty($trail)) {
  $items = [];
  foreach ($trail as $i => $t) {
    $item = [
      '@type'    => 'ListItem',
      'position' => $i + 1,
      'name'     => $t['label'],
    ];
    // solo agregamos 'item' si hay URL
    if (!empty($t['url'])) {
      $item['item'] = url(ltrim($t['url'], '/'));
    }
    $items[] = $item;
  }

  $extraHead = ($extraHead ?? '') .
    '<script type="application/ld+json">' .
    json_encode([
      '@context' => 'https://schema.org',
      '@type'    => 'BreadcrumbList',
      'itemListElement' => $items
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) .
    '</script>';
}
?>
