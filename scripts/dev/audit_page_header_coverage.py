from pathlib import Path
import re,collections
R=Path(r'C:\xampp\htdocs\mi-pesca')
V=R/'app/Views/pages/admin';P=R/'app/Views/partials'
def targets(path,code):
 found=set()
 for x in re.findall(r"['\"](/app/Views/partials/[^'\"]+\.php)['\"]",code):
  found.add(R/('app/Views/partials'+x.split('/app/Views/partials',1)[1]))
 for x in re.findall(r"(?:render_partial|section)\s*\(\s*['\"]([^'\"]+)['\"]",code):
  if x.startswith('partials/'):found.add(R/'app/Views'/ (x+'.php'))
  elif x.startswith('admin/'):found.add(P/(x+'.php'))
 for x in re.findall(r"__DIR__\s*\.\s*['\"](/[^'\"]+\.php)['\"]",code):
  found.add(path.parent/x.lstrip('/'))
 return [p for p in found if p.exists()]
def visit(p,seen,depth=0):
 if p in seen or depth>8:return False,[]
 seen.add(p)
 try:s=p.read_text(encoding='utf8',errors='replace')
 except: return False,[]
 has="partials/admin/page_header.php" in s
 items=[p] if has else []
 for child in targets(p,s):
  ok,files=visit(child,seen,depth+1)
  if ok:has=True;items+=files
 return has,items
for p in sorted(V.rglob('*.php')):
 ok,fs=visit(p,set())
 if not ok:print('UNMIGRATED',p.relative_to(V))
print('DONE')
