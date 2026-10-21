<?php
if (defined('_FACTURACION_NAV_RENDERED')) { return; }
define('_FACTURACION_NAV_RENDERED', true);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '/';
$basePrefix = rtrim(parse_url(url('/'), PHP_URL_PATH) ?: '', '/');

$norm = function (string $href) use ($basePrefix): string {
  $href = '/' . ltrim($href, '/');
  return ($basePrefix ? $basePrefix : '') . $href;
};

$isActive = function (string $href) use ($path, $norm): bool {
  $full = $norm($href);
  return $path === $full || str_starts_with($path, $full . '/');
};

$regime = strtolower(trim((string)app_setting('fiscal_seller_tax_regime', 'monotributo')));
if ($regime === '') $regime = 'monotributo';
$isRI = ($regime === 'responsable_inscripto');

$inControl = (
  $isActive('/admin/facturacion') ||
  $isActive('/admin/facturacion/control') ||
  $isActive('/admin/facturacion/reportes') ||
  $isActive('/admin/facturacion/libro-iva') ||
  $isActive('/admin/facturacion/libro-iva-compras') ||
  $isActive('/admin/facturacion/cierres-mensuales') ||
  $isActive('/admin/facturacion/alertas')
);

$operationLinks = [
  ['href' => '/admin/facturacion/comprobantes', 'label' => 'Ventas (comprobantes)'],
  ['href' => '/admin/facturacion/compras', 'label' => 'Compras'],
];

$controlLinks = [
  ['href' => '/admin/facturacion/control', 'label' => 'Dashboard'],
  ['href' => '/admin/facturacion/alertas', 'label' => 'Alertas'],
  ['href' => '/admin/facturacion/cierres-mensuales', 'label' => $isRI ? 'Cierres IVA' : 'Cierres mensuales'],
  ['href' => '/admin/facturacion/reportes', 'label' => 'Reportes'],
];
if ($isRI) {
  $controlLinks[] = ['href' => '/admin/facturacion/libro-iva', 'label' => 'Libro IVA Ventas'];
  $controlLinks[] = ['href' => '/admin/facturacion/libro-iva-compras', 'label' => 'Libro IVA Compras'];
}
?>

<div class="fisc-nav-shell">
  <nav class="fisc-nav" aria-label="Navegacion de facturacion">
    <div class="fisc-nav-groups">
      <section class="fisc-nav-group">
        <span class="fisc-nav-label">Operacion</span>
        <div class="fisc-nav-links">
          <?php foreach ($operationLinks as $item): ?>
            <?php $active = $isActive($item['href']); ?>
            <a
              class="fisc-nav-link<?= $active ?' is-active' : '' ?>"
              href="<?= e(base_url(ltrim($item['href'], '/'))) ?>"
              <?= $active ? 'aria-current="page"' : '' ?>
            ><?= e($item['label']) ?></a>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="fisc-nav-group">
        <span class="fisc-nav-label">Control fiscal</span>
        <div class="fisc-nav-links">
          <?php foreach ($controlLinks as $item): ?>
            <?php $active = $isActive($item['href']); ?>
            <a
              class="fisc-nav-link<?= $active ?' is-active' : '' ?>"
              href="<?= e(base_url(ltrim($item['href'], '/'))) ?>"
              <?= $active ? 'aria-current="page"' : '' ?>
            ><?= e($item['label']) ?></a>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <p class="fisc-nav-hint">
      <?php if (!$inControl): ?>
        Estas en <b>operacion</b>. Aca creas/corregis datos (ventas/compras). Para analizar y cerrar periodos, usa <b>Control fiscal</b>.
      <?php else: ?>
        Estas en <b>control fiscal</b>. Aca revisas, exportas y cerras periodos. La operacion diaria esta en <b>Operacion</b>.
      <?php endif; ?>
    </p>
  </nav>
</div>
