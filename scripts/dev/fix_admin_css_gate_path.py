from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'scripts/build/admin_css_scope.php'
s=p.read_text(encoding='utf8')
old='css/v2/components/admin-foundations.css';new='css/admin-v3/components/foundations.css'
assert s.count(old)==3
p.write_text(s.replace(old,new),encoding='utf8')
print('GATE_PATH_UPDATED')
