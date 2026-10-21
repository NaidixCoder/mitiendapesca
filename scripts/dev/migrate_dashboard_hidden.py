from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
view=r/'app/Views/partials/admin/dashboard/trend.php'
s=view.read_text(encoding='utf8').replace('class="hidden"','class="av3-hidden"')
view.write_text(s,encoding='utf8')
for file in ('trend_dom.js','trend_draw.js'):
 p=r/'public_html/assets/js/admin/dashboard'/file
 s=p.read_text(encoding='utf8')
 s=s.replace("'chart-tooltip hidden'","'chart-tooltip av3-hidden'").replace("'hidden'","'av3-hidden'")
 p.write_text(s,encoding='utf8')
css=r/'public_html/assets/css/admin-v3/pages/dashboard.css'
s=css.read_text(encoding='utf8')
s=s.replace('#trend-message.hidden','#trend-message.av3-hidden')
s+='\nbody[data-page^="admin"] .av3-dashboard .av3-hidden{display:none!important}\n'
css.write_text(s,encoding='utf8')
print('DASHBOARD_TAILWIND_HIDDEN_REMOVED')
