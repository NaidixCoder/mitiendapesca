from pathlib import Path
import re,datetime,shutil
R=Path(r'C:\xampp\htdocs\mi-pesca')
v=R/'app/Views/pages/admin/dashboard/index.php'
c=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
bk=Path(r'C:\xampp\mi-pesca-backups')/('dashboard_mobile_first_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for p in (v,c):
 target=bk/p.relative_to(R);target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,target)
s=v.read_text(encoding='utf8')
a=s.index('  <?php if ($extraMetrics): ?>');b=s.index('  <section class="av3-performance"',a)
s=s[:a]+s[b:]
# remove duplicate todo bar from activity; keep alert todo only
a=s.index('      <?php if ($todos): ?>',s.index('class="av3-event-list"'))
b=s.index('    </section>',a)
s=s[:a]+s[b:]
# mobile cards use same data, presentation only
needle='''      </div>
    </section>
    <section class="av3-panel av3-card av3-alerts">'''
assert needle in s
cards='''      </div>
      <div class="av3-order-mobile" aria-label="Pedidos por gestionar">
        <?php if (!$orders): ?><p class="av3-empty">No hay pedidos pendientes.</p><?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <a class="av3-order-mobile__item" href="<?= e((string)($o['href'] ?? '#')) ?>">
            <div class="av3-order-mobile__top">
              <strong><?= e((string)($o['id_label'] ?? '')) ?></strong>
              <strong class="av3-number"><?= e((string)($o['total_label'] ?? '$ 0')) ?></strong>
            </div>
            <span class="av3-order-mobile__customer"><?= e((string)($o['customer_label'] ?? '-')) ?></span>
            <div class="av3-order-mobile__bottom">
              <span class="av3-state"><?= e((string)($o['status_label'] ?? '')) ?></span>
              <?php if (!empty($o['is_urgent'])): ?><span class="av3-state av3-state--urgent">Urgente</span><?php endif; ?>
              <small><?= e((string)($o['created_label'] ?? '')) ?></small>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="av3-panel av3-card av3-alerts">'''
s=s.replace(needle,cards,1)
needle='''      </tbody></table></div>
    </section>
    <section class="av3-panel av3-card av3-events">'''
assert needle in s
replacement='''      </tbody></table></div>
      <div class="av3-stock-mobile">
        <?php if (!$stocks): ?><p class="av3-empty">No hay productos con stock bajo.</p><?php endif; ?>
        <?php foreach ($stocks as $stock): ?>
          <a class="av3-stock-mobile__item" href="<?= e((string)($stock['edit_href'] ?? '#')) ?>">
            <div><strong><?= e((string)($stock['name'] ?? '')) ?></strong><small><?= e((string)($stock['sku'] ?? '')) ?><?= !empty($stock['variant_title']) ? ' · '.e((string)$stock['variant_title']) : '' ?></small></div>
            <div class="av3-stock-mobile__counts"><span>Stock <strong><?= (int)($stock['stock'] ?? 0) ?></strong></span><span>Mín. <strong><?= (int)($stock['thr'] ?? 0) ?></strong></span></div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="av3-panel av3-card av3-events">'''
s=s.replace(needle,replacement,1)
v.write_text(s,encoding='utf8')
print('VIEW_REFRESHED','BACKUP',bk)
