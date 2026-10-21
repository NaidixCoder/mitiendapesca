from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\app\Views\pages\admin\dashboard\index.php')
s=p.read_text(encoding='utf8')
s=s.replace("$metricKeys = ['facturacion hoy', 'pedidos pendientes', 'productos activos', 'bajo stock'];","$metricKeys = ['facturacion hoy', 'pedidos pendientes', 'productos activos', 'bajo stock'];")
s=s.replace("$ordersUrl = (string)($panel['orders_base'] ?? url('/admin/orders'));","$ordersUrl = url('/admin/orders');")
s=s.replace("$attrs = is_array($panel['attrs'] ?? null) ? $panel['attrs'] : [];","""$attrs = is_array($panel['attrs'] ?? null) ? $panel['attrs'] : [];
$todos = is_array($todosCards ?? null) ? $todosCards : [];
$extraMetrics = [];
foreach ($metrics as $metric) {
  $included = false;
  foreach ($metricSet as $mainMetric) { if (($metric['label'] ?? '') === ($mainMetric['label'] ?? '')) { $included = true; break; } }
  if (!$included) $extraMetrics[] = $metric;
}
""")
s=s.replace('<header class="mt-dashboard__heading"><h1>Resumen general</h1></header>','''<header class="mt-dashboard__heading">
    <div><p class="mt-dashboard__eyebrow">MI PESCA / INICIO</p><h1>Resumen general</h1></div>
    <span class="mt-dashboard__context">Estado de la tienda</span>
  </header>''')
s=s.replace('''  <div class="mt-work">''','''  <div class="mt-work">''')
s=s.replace('''    </section>
  </div>

  <section class="mt-performance"''','''    </section>
  </div>

  <?php if ($extraMetrics): ?>
  <div class="mt-extras" aria-label="Información adicional de la tienda">
    <?php foreach ($extraMetrics as $metric): ?><a href="<?= e((string)($metric['href'] ?? '#')) ?>"><span><?= e((string)($metric['label'] ?? '')) ?></span><strong><?= e((string)($metric['value'] ?? '')) ?></strong></a><?php endforeach; ?>
  </div>
  <?php endif; ?>

  <section class="mt-performance"''',1)
s=s.replace('''    </section>
  </div>

  <?php if ($extraMetrics): ?>''','''    </section>
  </div>

  <?php if ($extraMetrics): ?>''')
# insert tasks as small band in attention card, replacing end of ul
anchor='''      </ul>
    </section>
  </div>'''
replace='''      </ul>
      <?php if ($todos): ?>
      <div class="mt-todo-strip">
        <?php foreach (array_slice($todos, 0, 4) as $todo): ?>
          <a href="<?= e((string)($todo['href'] ?? '#')) ?>"><strong><?= (int)($todo['value'] ?? 0) ?></strong><span><?= e((string)($todo['label'] ?? '')) ?></span></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>'''
s=s.replace(anchor,replace)
p.write_text(s,encoding='utf8')
print('View updated:',len(s))
