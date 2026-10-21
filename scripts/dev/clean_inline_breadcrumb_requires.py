from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca')
files=list((R/'app/Views/pages/admin').rglob('*.php'))+list((R/'app/Views/partials/admin').rglob('*.php'))
pat=re.compile(r'\b(?:require|require_once)\s+BASE_PATH\s*\.\s*["\x27]/app/Views/partials/admin/breadcrumbs\.php["\x27]\s*;\s*',re.I)
modified=[]
for p in files:
 try:s=p.read_text(encoding='utf8')
 except UnicodeError:continue
 t,n=pat.subn('',s)
 if n:
  p.write_text(t,encoding='utf8');modified.append(str(p.relative_to(R)))
print('REMOVED_OLD_REQUIRE_STATEMENTS',len(modified))
for x in modified:print(x)
