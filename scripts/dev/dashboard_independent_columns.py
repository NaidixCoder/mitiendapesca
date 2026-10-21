from pathlib import Path
from datetime import datetime
import shutil
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'app/Views/pages/admin/dashboard/index.php'
css=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
backup=Path(r'C:\xampp\mi-pesca-backups')/('dashboard_columns_'+datetime.now().strftime('%Y%m%d_%H%M%S'))
for f in (p,css):
    tgt=backup/f.relative_to(R)
    tgt.parent.mkdir(parents=True,exist_ok=True)
    shutil.copy2(f,tgt)
s=p.read_text(encoding='utf8')
work=s.index('  <div class="av3-work">')
orders=s.index('    <section id="pending-orders"',work)
alerts=s.index('    <section class="av3-panel av3-card av3-alerts">',orders)
bottom=s.index('  <div class="av3-bottom">',alerts)
stock=s.index('    <section class="av3-panel av3-card av3-stock">',bottom)
events=s.index('    <section class="av3-panel av3-card av3-events">',stock)
perf=s.index('  <section class="av3-performance"',events)
assert work<orders<alerts<bottom<stock<events<perf
orders_html=s[orders:alerts]
alerts_html=s[alerts:bottom]
assert alerts_html.rstrip().endswith('</div>'), repr(alerts_html[-100:])
alerts_html=alerts_html[:alerts_html.rfind('  </div>')]
stock_html=s[stock:events]
events_html=s[events:perf]
assert events_html.rstrip().endswith('</div>'),repr(events_html[-100:])
events_html=events_html[:events_html.rfind('  </div>')]
# Keep each actual PHP section exactly once in a vertical independent column.
new_layout='''  <div class="av3-dashboard__columns">
    <div class="av3-dashboard__column av3-dashboard__column--primary">
'''+orders_html+stock_html+'''    </div>
    <div class="av3-dashboard__column av3-dashboard__column--secondary">
'''+alerts_html+events_html+'''    </div>
  </div>

'''
s=s[:work]+new_layout+s[perf:]
# Recompose mobile items to three deliberate rows: pedido/total, customer/date, statuses.
mobile_begin=s.index('          <a class="av3-order-mobile__item"')
mobile_end=s.index('          </a>',mobile_begin)+len('          </a>')
block='''          <a class="av3-order-mobile__item" href="<?= e((string)($o['href'] ?? '#')) ?>">
            <div class="av3-order-mobile__top">
              <strong>Pedido <?= e((string)($o['id_label'] ?? '')) ?></strong>
              <strong class="av3-number"><?= e((string)($o['total_label'] ?? '$ 0')) ?></strong>
            </div>
            <div class="av3-order-mobile__middle">
              <span class="av3-order-mobile__customer"><?= e((string)($o['customer_label'] ?? '-')) ?></span>
              <time class="av3-order-mobile__date"><?= e((string)($o['created_label'] ?? '')) ?></time>
            </div>
            <div class="av3-order-mobile__bottom">
              <span class="av3-state"><?= e((string)($o['status_label'] ?? '')) ?></span>
              <?php if (!empty($o['is_urgent'])): ?><span class="av3-state av3-state--urgent">Urgente</span><?php endif; ?>
            </div>
          </a>'''
s=s[:mobile_begin]+block+s[mobile_end:]
assert s.count('class="av3-dashboard__columns"')==1
assert s.count('class="av3-order-mobile__middle"')==1
assert s.count('<section id="pending-orders"')==1
assert s.count('class="av3-panel av3-card av3-stock"')==1
assert s.count('class="av3-panel av3-card av3-alerts"')==1
assert s.count('class="av3-panel av3-card av3-events"')==1
p.write_text(s,encoding='utf8')
print('MIGRATED_TWO_INDEPENDENT_COLUMNS')
print('PRESERVED_ALL_4_MODULES_ONCE')
print('BACKUP',backup)
