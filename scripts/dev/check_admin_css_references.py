from pathlib import Path
import re,json
R=Path(r'C:\xampp\htdocs\mi-pesca')
head=(R/'app/Views/partials/head.php').read_text(encoding='utf8')
styles=re.findall(r"'(css/[^']+\.css)'",head)
miss=[x for x in styles if not (R/'public_html/assets'/x).is_file()]
admin=head.split('$adminStyles = [',1)[1].split('];',1)[0]
active=re.findall(r"'(css/[^']+\.css)'",admin)
print('ACTIVE_ADMIN_CSS',len(active),'MISSING_REFERENCES',miss)
print('ADMIN_CSS_TREE',sorted(x for x in active if x.startswith('css/admin-v3/')))
print('ADMIN_LEGACY',sorted(x for x in active if '/v2/' in x))
assert all((R/'public_html/assets'/x).is_file() for x in active)
print('ALL_ADMIN_REFERENCES_EXIST')
