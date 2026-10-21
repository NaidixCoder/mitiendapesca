from pathlib import Path
import tinycss2,importlib.util
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'app/Views/pages/admin/dashboard/index.php'
s=p.read_text(encoding='utf8')
s=s.replace("$todos = is_array($todosCards ?? null) ? $todosCards : [];\n",'')
s=s.replace('<h2>Rendimiento</h2>','<h2>Rendimiento comercial</h2>',1)
p.write_text(s,encoding='utf8')
p=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
s=p.read_text(encoding='utf8')
s=s.replace('''body[data-page^="admin"] .av3-dashboard .av3-performance__body > section > header {
  display:flex;align-items:center;justify-content:space-between;
  gap:12px;padding:13px 0;border-bottom:1px solid #EDF1F2
}''','''body[data-page^="admin"] .av3-dashboard .av3-performance__body > section > header {
  display:none
}''')
s=s.replace('''body[data-page^="admin"] .av3-dashboard .av3-performance:has(#trend-message.is-empty-period) .av3-performance__body > section > header {
  display:none
}
''','')
p.write_text(s,encoding='utf8')
rules=tinycss2.parse_stylesheet(s,skip_comments=True,skip_whitespace=True)
errors=[f'{rule.message}' for rule in rules if rule.type=='error']
print('CSS_PARSE',len(rules),'RULES','ERRORS',errors)
assert not errors
print('SELENIUM',bool(importlib.util.find_spec('selenium')))
print('TINYCSS2',bool(importlib.util.find_spec('tinycss2')))
