from pathlib import Path
import re, shutil, datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
files=[r/'app/Views/pages/admin/dashboard/index.php', *sorted((r/'app/Views/partials/admin/dashboard').glob('*.php')),
       r/'app/Views/partials/admin/components/metric_card.php',r/'app/Views/partials/head.php']
css=[r/'public_html/assets/css/v2/pages/admin'/n for n in ('dashboard.css','dashboard-trend.css','dashboard-v3.css')]
backup=Path(r'C:\xampp\mi-pesca-backups')/('dashboard_reset_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for f in files+css:
 if f.exists():
  dst=backup/f.relative_to(r);dst.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,dst)
# Keep JS hooks and conditional visibility functionality. Remove plain presentational class attributes.
hooks={'admin-row-link','hidden'}
count={}
for f in files:
 if f.name=='head.php':continue
 s=f.read_text(encoding='utf-8')
 def rem(m):
  raw=m.group(1)
  if '<?' in raw or '?>' in raw:
   # Dynamic PHP expressions: preserve as functional template rather than rewrite blindly
   return m.group(0)
  kept=[v for v in raw.split() if v in hooks]
  count[f.name]=count.get(f.name,0)+1
  return ('class="'+ ' '.join(kept)+'"') if kept else ''
 s=re.sub(r'class="([^"]*)"',rem,s)
 f.write_text(s,encoding='utf-8',newline='')
head=r/'app/Views/partials/head.php'
s=head.read_text(encoding='utf-8')
for name in ('dashboard.css','dashboard-trend.css','dashboard-v3.css'):
 s=s.replace("    'css/v2/pages/admin/"+name+"',\n",'')
head.write_text(s,encoding='utf-8',newline='')
for f in css:
 if f.exists():f.unlink()
print('BACKUP',backup)
print('REMOVED CSS',*[p.name for p in css])
print('STATIC CLASS ATTRIBUTES CLEANED',count)
print('DYNAMIC CLASSES KEPT FOR SAFETY')
