from pathlib import Path
import tinycss2,re
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/css/admin-v3/pages/dashboard.css'
src=p.read_text(encoding='utf8')
nodes=tinycss2.parse_stylesheet(src,skip_comments=True,skip_whitespace=True)
errors=[n.message for n in nodes if n.type=='error']
print('CSS_PARSE_ERRORS',errors,'CSS_RULES',len(nodes))
assert not errors
print('SCOPE_ADMIN',len(re.findall(r'body\[data-page\^="admin"\]',src)))
for rule in ['av3-dashboard__columns','av3-dashboard__column--primary','av3-dashboard__column--secondary','av3-order-mobile__middle','@media(min-width:1200px)','@media(min-width:900px)','@container (min-width:620px)']:
 print(rule,rule in src)
print('DEAD_LEGACY_WORK_BOTTOM',not any(x in src for x in ('.av3-work','.av3-bottom')))
