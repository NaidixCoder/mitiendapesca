from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca'); root=r/'app/Views'
mapping={'text-sm':'av3-type-body','text-xs':'av3-type-caption','font-semibold':'av3-weight-strong','font-medium':'av3-weight-medium','bg-white':'av3-surface-white','rounded-xl':'av3-radius-card','rounded-lg':'av3-radius-control'}
tot=collections.Counter();files=0
for p in root.rglob('*.php'):
 if '/admin/' not in '/'+p.relative_to(root).as_posix():continue
 s=p.read_text(encoding='latin1'); n=s
 for old,new in mapping.items():
  pattern=r'(?<![\w:-])'+re.escape(old)+r'(?=[\s"\\x27>\}\)\]]|$)'
  n,count=re.subn(pattern,new,n);tot[old]+=count
 if n!=s:p.write_text(n,encoding='latin1');files+=1
css=r/'public_html/assets/css/admin-v3/components/primitives.css'
css.write_text('''/* Admin V3 — design-system primitives, replacing common Tailwind utilities. */
body[data-page^="admin"] .av3-type-body{font-size:14px;line-height:20px}
body[data-page^="admin"] .av3-type-caption{font-size:12px;line-height:16px}
body[data-page^="admin"] .av3-weight-strong{font-weight:600}
body[data-page^="admin"] .av3-weight-medium{font-weight:500}
body[data-page^="admin"] .av3-surface-white{background-color:#fff}
body[data-page^="admin"] .av3-radius-card{border-radius:12px}
body[data-page^="admin"] .av3-radius-control{border-radius:8px}
''',encoding='utf8')
head=r/'app/Views/partials/head.php';s=head.read_text(encoding='utf8')
needle="    'css/admin-v3/components/common.css',"
assert needle in s
s=s.replace(needle,needle+"\n    'css/admin-v3/components/primitives.css',")
head.write_text(s,encoding='utf8')
print('PRIMITIVES_MIGRATED',files,'files',dict(tot))
