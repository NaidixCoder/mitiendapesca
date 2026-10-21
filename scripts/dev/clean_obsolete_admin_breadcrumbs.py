from pathlib import Path
import re,shutil,datetime
R=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[R/'app/Views/pages/admin',R/'app/Views/partials/admin']
old_patterns=[
 re.compile(r'<\?php\s*render_partial\(\s*["\x27]admin/breadcrumbs["\x27]\s*,[\s\S]*?\)\s*;\s*\?>\s*',re.I),
 re.compile(r'<\?php\s*require\s+BASE_PATH\s*\.\s*["\x27]/app/Views/partials/admin/breadcrumbs\.php["\x27]\s*;\s*\?>\s*',re.I),
]
changes={}
for root in roots:
 for p in root.rglob('*.php'):
  if p.name in ('breadcrumbs.php','page_header.php'):continue
  try:s=p.read_text(encoding='utf8')
  except UnicodeError:continue
  t=s
  for pat in old_patterns:t=pat.sub('',t)
  if t!=s:changes[p]=t
print('OLD_BREADCRUMB_CALLERS_TO_CLEAN',len(changes))
for p,t in changes.items():
 assert 'admin/breadcrumbs' not in t, str(p)
backup=Path(r'C:\xampp\mi-pesca-backups')/('legacy_breadcrumb_removal_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for p,t in changes.items():
 dst=backup/p.relative_to(R);dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dst)
 p.write_text(t,encoding='utf8')
 print('CLEANED',p.relative_to(R))
for rel in ['app/Views/partials/admin/breadcrumbs.php','app/Views/partials/admin/components/page_header.php']:
 p=R/rel
 if p.exists():
  dst=backup/p.relative_to(R);dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dst)
  p.unlink();print('REMOVED_OBSOLETE',rel)
print('BACKUP',backup)
