<?php
/**
 * Espera: $navigationAnalytics preparado en backend.
 */
$na = $navigationAnalytics;
?>

<section >
  <header >
    <div>
      <h3 >Analiticas de navegacion</h3>
      <div >Ultimos <?= (int)$na['days'] ?> dias</div>
    </div>
    <div >Beta</div>
  </header>

  <div >
    <?php foreach ($na['funnel_cards'] as $card): ?>
      <div >
        <div ><?= e((string)$card['label']) ?></div>
        <div ><?= e(is_int($card['value']) ? number_format((int)$card['value'], 0, ',', '.') : (string)$card['value']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <div >
    <div >
      <div >
        <div >Paginas mas visitadas</div>
        <div >por page_view</div>
      </div>
      <div >
        <table >
          <thead ><tr><th >Pagina</th><th >Vistas</th></tr></thead>
          <tbody >
            <?php if (empty($na['top_pages'])): ?>
              <tr><td  colspan="2">Sin datos todavia.</td></tr>
            <?php else: foreach ($na['top_pages'] as $r): ?>
              <tr>
                <td ><?php if (!empty($r['href'])): ?><a  href="<?= e((string)$r['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$r['label']) ?></a><?php else: ?><span ><?= e((string)$r['label']) ?></span><?php endif; ?></td>
                <td ><?= number_format((int)$r['metric'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div >
      <div >
        <div >Productos mas visitados</div>
        <div >por product_view</div>
      </div>
      <div >
        <table >
          <thead ><tr><th >Producto</th><th >Vistas</th></tr></thead>
          <tbody >
            <?php if (empty($na['top_products'])): ?>
              <tr><td  colspan="2">Sin datos todavia.</td></tr>
            <?php else: foreach ($na['top_products'] as $r): ?>
              <tr>
                <td ><?php if (!empty($r['href'])): ?><a  href="<?= e((string)$r['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$r['label']) ?></a><?php else: ?><span ><?= e((string)$r['label']) ?></span><?php endif; ?></td>
                <td ><?= number_format((int)$r['metric'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div >
    <div >
      <div >
        <div >Landing pages</div>
        <div >por sesion (page_view inicial)</div>
      </div>
      <div >
        <table >
          <thead ><tr><th >Pagina</th><th >Sesiones</th></tr></thead>
          <tbody >
            <?php if (empty($na['landing_pages'])): ?>
              <tr><td  colspan="2">Sin datos todavia.</td></tr>
            <?php else: foreach ($na['landing_pages'] as $r): ?>
              <tr>
                <td ><?php if (!empty($r['href'])): ?><a  href="<?= e((string)$r['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$r['label']) ?></a><?php else: ?><span ><?= e((string)$r['label']) ?></span><?php endif; ?></td>
                <td ><?= number_format((int)$r['metric'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div >
      <div >
        <div >Fuentes (referrer/UTM)</div>
        <div >por sesion (landing)</div>
      </div>
      <div >
        <table >
          <thead ><tr><th >Fuente</th><th >Campana</th><th >Sesiones</th></tr></thead>
          <tbody >
            <?php if (empty($na['top_sources'])): ?>
              <tr><td  colspan="3">Sin datos todavia.</td></tr>
            <?php else: foreach ($na['top_sources'] as $r): ?>
              <tr>
                <td ><div ><?= e((string)$r['source']) ?></div><?php if (!empty($r['medium'])): ?><div ><?= e((string)$r['medium']) ?></div><?php endif; ?></td>
                <td ><?php if (!empty($r['campaign'])): ?><span ><?= e((string)$r['campaign']) ?></span><?php else: ?><span >-</span><?php endif; ?></td>
                <td ><?= number_format((int)$r['sessions'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div >
    <div >
      <div >
        <div >Mas agregados al carrito</div>
        <div >por add_to_cart</div>
      </div>
      <div >
        <table >
          <thead ><tr><th >Producto</th><th >Variante</th><th >Agregados</th></tr></thead>
          <tbody >
            <?php if (empty($na['top_add_to_cart'])): ?>
              <tr><td  colspan="3">Sin datos todavia.</td></tr>
            <?php else: foreach ($na['top_add_to_cart'] as $r): ?>
              <tr>
                <td ><?php if (!empty($r['href'])): ?><a  href="<?= e((string)$r['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$r['label']) ?></a><?php else: ?><span ><?= e((string)$r['label']) ?></span><?php endif; ?></td>
                <td ><?php if (!empty($r['variant_title'])): ?><span ><?= e((string)$r['variant_title']) ?></span><?php else: ?><span >-</span><?php endif; ?></td>
                <td ><?= number_format((int)$r['metric'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div >
      <div >
        <div >Vista -> carrito (por variante)</div>
        <div >product_view vs add_to_cart</div>
      </div>
      <div >
        <table >
          <thead ><tr><th >Producto</th><th >Variante</th><th >Vistas</th><th >Agregados</th><th >Conv.</th></tr></thead>
          <tbody >
            <?php if (empty($na['top_view_to_cart'])): ?>
              <tr><td  colspan="5">Sin datos todavia.</td></tr>
            <?php else: foreach ($na['top_view_to_cart'] as $r): ?>
              <tr>
                <td ><?php if (!empty($r['href'])): ?><a  href="<?= e((string)$r['href']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string)$r['label']) ?></a><?php else: ?><span ><?= e((string)$r['label']) ?></span><?php endif; ?></td>
                <td ><?php if (!empty($r['variant_title'])): ?><span ><?= e((string)$r['variant_title']) ?></span><?php else: ?><span >-</span><?php endif; ?></td>
                <td ><?= number_format((int)$r['views'], 0, ',', '.') ?></td>
                <td ><?= number_format((int)$r['adds'], 0, ',', '.') ?></td>
                <td ><?= e((string)$r['conversion']) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div >Nota: esto registra eventos propios (sin Google Analytics) y evita PII en claro.</div>
</section>
