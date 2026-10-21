from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
s=p.read_text(encoding='utf8')
old='body[data-page^="admin"] .av3-dashboard :is(.av3-summary,.av3-work,.av3-bottom,.av3-rankings,.av3-card,.av3-performance,.av3-ranking) {min-width:0}'
new='body[data-page^="admin"] .av3-dashboard :is(.av3-summary,.av3-dashboard__columns,.av3-dashboard__column,.av3-rankings,.av3-card,.av3-performance,.av3-ranking) {min-width:0}'
assert old in s;s=s.replace(old,new,1)
start=s.index('/* 02. Grilla de trabajo.')
end=s.index('body[data-page^="admin"] .av3-dashboard .av3-card {',start)
s=s[:start]+'''/* 02. Columnas independientes: filas móviles se ordenan por prioridad. */
body[data-page^="admin"] .av3-dashboard :is(.av3-dashboard__columns,.av3-rankings){
  display:grid;grid-template-columns:minmax(0,1fr);gap:16px;min-width:0
}
body[data-page^="admin"] .av3-dashboard .av3-dashboard__column{display:contents}
body[data-page^="admin"] .av3-dashboard .av3-alerts{order:0}
body[data-page^="admin"] .av3-dashboard .av3-orders{order:1}
body[data-page^="admin"] .av3-dashboard .av3-stock{order:2}
body[data-page^="admin"] .av3-dashboard .av3-events{order:3}
body[data-page^="admin"] .av3-dashboard :is(.av3-orders,.av3-stock){container-type:inline-size}
'''+s[end:]
# Current mobile bottom row has date, replace it by middle row.
old='''body[data-page^="admin"] .av3-dashboard .av3-order-mobile__bottom {
  display:flex;align-items:center;gap:4px;flex-wrap:wrap
}
body[data-page^="admin"] .av3-dashboard .av3-order-mobile__bottom small {
  margin-left:auto;font-size:12px;color:var(--dash-muted);white-space:nowrap
}'''
new='''body[data-page^="admin"] .av3-dashboard .av3-order-mobile__middle{
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:4px 12px;min-width:0
}
body[data-page^="admin"] .av3-dashboard .av3-order-mobile__date{
  flex:none;color:var(--dash-muted);font-size:12px;
  font-variant-numeric:tabular-nums;white-space:nowrap
}
body[data-page^="admin"] .av3-dashboard .av3-order-mobile__bottom{
  display:flex;align-items:center;gap:4px;flex-wrap:wrap
}
body[data-page^="admin"] .av3-dashboard .av3-order-mobile__bottom .av3-state{
  margin:0 4px 0 0
}'''
assert old in s;s=s.replace(old,new,1)
start=s.index('/* Container rules: measure available content, not viewport including sidebar. */')
s=s[:start]+'''/* Responsive: fallbacks usan viewport; container queries usan ancho real de panel.
   No se añade !important ni se depende de una sola tecnología de layout. */
@media(min-width:900px){
  body[data-page^="admin"] .av3-dashboard .av3-summary{
    grid-template-columns:repeat(4,minmax(0,1fr));gap:16px
  }
  body[data-page^="admin"] .av3-dashboard .av3-summary__metric{
    min-height:104px;padding:18px
  }
}
/* Para tablas en pantallas medianas con los paneles apilados, usar todo el ancho. */
@media(min-width:900px) and (max-width:1199px){
  body[data-page^="admin"] .av3-dashboard .av3-orders .av3-table-wrap,
  body[data-page^="admin"] .av3-dashboard .av3-stock .av3-table-wrap{display:block}
  body[data-page^="admin"] .av3-dashboard .av3-order-mobile,
  body[data-page^="admin"] .av3-dashboard .av3-stock-mobile{display:none}
}
@media(min-width:1200px){
  body[data-page^="admin"] .av3-dashboard .av3-dashboard__columns{
    grid-template-columns:minmax(0,7fr) minmax(0,5fr);align-items:start
  }
  body[data-page^="admin"] .av3-dashboard .av3-dashboard__column{
    display:grid;grid-template-columns:minmax(0,1fr);gap:16px;align-content:start
  }
  body[data-page^="admin"] .av3-dashboard .av3-dashboard__column > *{order:initial}
  body[data-page^="admin"] .av3-dashboard .av3-rankings{
    grid-template-columns:repeat(2,minmax(0,1fr))
  }
}
/* Cada panel mide su propio ancho: evita tablas comprimidas junto al aside. */
@container (min-width:620px){
  body[data-page^="admin"] .av3-dashboard .av3-orders .av3-table-wrap,
  body[data-page^="admin"] .av3-dashboard .av3-stock .av3-table-wrap{display:block}
  body[data-page^="admin"] .av3-dashboard .av3-orders .av3-order-mobile,
  body[data-page^="admin"] .av3-dashboard .av3-stock .av3-stock-mobile{display:none}
}
@media(min-width:1300px){
  body[data-page^="admin"] .av3-dashboard .av3-orders .av3-table-wrap,
  body[data-page^="admin"] .av3-dashboard .av3-stock .av3-table-wrap{display:block}
  body[data-page^="admin"] .av3-dashboard .av3-order-mobile,
  body[data-page^="admin"] .av3-dashboard .av3-stock-mobile{display:none}
}
'''
# Remove dashboard container type on parent to avoid interfering with the panel-specific container query at medium.
# It remains usable for testing width; keep it, but specific panels become nearer ancestors for their own data.
for legacy in ('.av3-work','.av3-bottom'):
 assert legacy not in s, 'legacy selector remains '+legacy
p.write_text(s,encoding='utf8')
print('CSS_INDEPENDENT_COLUMNS_APPLIED','LINES',len(s.splitlines()))
