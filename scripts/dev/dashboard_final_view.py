from pathlib import Path
import shutil, datetime, re
R=Path(r'C:\xampp\htdocs\mi-pesca')
v=R/'app/Views/pages/admin/dashboard/index.php'
css=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
bk=Path(r'C:\xampp\mi-pesca-backups')/('dashboard_final_design_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for p in [v,css]:
 t=bk/p.relative_to(R); t.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,t)
s=v.read_text(encoding='utf8')
# Drop dead extra metrics calculation, not used by this view
a=s.index('$extraMetrics = [];')
b=s.index('?>\n<div class="admin-surface">',a)
s=s[:a]+s[b:]
# Remove the duplicate todo counter strip from attention panel.
a=s.index('      <?php if ($todos): ?>',s.index('<section class="av3-panel av3-card av3-alerts">'))
b=s.index('    </section>',a)
s=s[:a]+s[b:]
# Move the existing performance and conditional ranking block after stock/activity.
a=s.index('  <section class="av3-performance"')
b=s.index('  <div class="av3-bottom">',a)
performance=s[a:b]
s=s[:a]+s[b:]
end=s.index('  </div>\n</main>',s.index('  <div class="av3-bottom">'))+len('  </div>')
s=s[:end]+'\n\n'+performance+s[end:]
# Visible priority on mobile without changing business meanings
s=s.replace('<i class="av3-alert-dot<?= $urgent?', '<i class="av3-alert-dot<?= $urgent?',1)
# Stock critical: use semantic styling on actual data in both modes.
s=s.replace('''<td class="av3-number"><?= (int)($s['stock'] ?? 0) ?></td><td class="av3-number"><?= (int)($s['thr'] ?? 0) ?></td>''',
'''<td class="av3-number"><span class="av3-stock-count<?= (int)($s['stock'] ?? 0) === 0 ? ' av3-stock-count--zero' : ' av3-stock-count--low' ?>"><?= (int)($s['stock'] ?? 0) ?></span></td><td class="av3-number"><?= (int)($s['thr'] ?? 0) ?></td>''')
s=s.replace('''<span>Stock <strong><?= (int)($stock['stock'] ?? 0) ?></strong></span>''',
'''<span class="av3-stock-count<?= (int)($stock['stock'] ?? 0) === 0 ? ' av3-stock-count--zero' : ' av3-stock-count--low' ?>">Stock: <?= (int)($stock['stock'] ?? 0) ?></span>''')
# Supplement event semantics based on displayed event (no backend changes).
s=s.replace('''$label=str_ireplace(['Checkout Mp Payment Error (checkout)','Checkout Mp Payment Error'],['Error en pago de Mercado Pago','Error en pago de Mercado Pago'],$raw);''',
'''$label=str_ireplace(['Checkout Mp Payment Error (checkout)','Checkout Mp Payment Error'],['Error en pago de Mercado Pago','Error en pago de Mercado Pago'],$raw);
        $eventIsError=(bool)preg_match('/error|fallid|rechazad/i', $label);''')
s=s.replace('''<li><span class="av3-event-dot" aria-hidden="true"></span><div>''','''<li><span class="av3-event-dot<?= $eventIsError ? ' av3-event-dot--error' : '' ?>" aria-hidden="true"></span><div>''')
# Improve stock label typography in both layouts
assert s.count('av3-stock-count--zero')==2
assert s.count('class="av3-todo-strip"')==0
assert s.index('class="av3-bottom"') < s.index('class="av3-performance"')
v.write_text(s,encoding='utf8')
print('VIEW_UPDATED',v)
print('BACKUP',bk)
