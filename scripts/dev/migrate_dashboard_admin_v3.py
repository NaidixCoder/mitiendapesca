from pathlib import Path
import re,shutil
R=Path(r'C:\xampp\htdocs\mi-pesca')
view=R/'app/Views/pages/admin/dashboard/index.php'
css=R/'public_html/assets/css/v2/pages/admin/dashboard-2026.css'
head=R/'app/Views/partials/head.php'
s=view.read_text(encoding='utf8')
c=css.read_text(encoding='utf8')
h=head.read_text(encoding='utf8')
# Existing dashboard names are presentation-specific except JS row click hook.
assert 'class="mt-dashboard"' in s and 'body[data-page^="admin"] .mt-dashboard' in c
assert "'css/v2/pages/admin/dashboard-2026.css'," in h
# Ensure references outside the dashboard are known before renaming.
other=[]
for p in list((R/'app').rglob('*.php'))+list((R/'public_html/assets/js').rglob('*.js')):
 if p==view:continue
 txt=p.read_text(encoding='utf8',errors='replace')
 if any(z in txt for z in ['mt-dashboard','mt-summary','mt-card','mt-work']):other.append(str(p.relative_to(R)))
if other:raise RuntimeError('shared references: '+repr(other[:12]))
s=re.sub(r'\bmt-([a-z][a-z0-9_-]*)',r'av3-\1',s)
c=re.sub(r'\bmt-([a-z][a-z0-9_-]*)',r'av3-\1',c)
folder=R/'public_html/assets/css/admin-v3'
(folder/'components').mkdir(parents=True,exist_ok=True)
(folder/'pages').mkdir(parents=True,exist_ok=True)
new=folder/'pages/dashboard.css'
new.write_text(c,encoding='utf8')
view.write_text(s,encoding='utf8')
head.write_text(h.replace("'css/v2/pages/admin/dashboard-2026.css',","'css/admin-v3/pages/dashboard.css',"),encoding='utf8')
print('MIGRATED_DASHBOARD',len(s),'view bytes',len(c),'css bytes')
