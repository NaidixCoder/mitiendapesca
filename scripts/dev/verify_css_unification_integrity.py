from pathlib import Path
import re,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
assert 'cdn.tailwindcss.com' not in h
for group in ['adminStyles','publicStyles']:
 m=re.search(r'\$'+group+r'\s*=\s*\[(.*?)\];',h,re.S)
 assert m,group
 files=re.findall(r"'(css/[^']+\.css)'",m.group(1))
 assert len(files)==len(set(files)),('duplicate',group)
 assert all((r/'public_html/assets'/x).is_file() for x in files)
 print(group,len(files),'styles resolved, no duplicates')
assert (r/'public_html/assets/css/admin-v3/layout.css').is_file()
assert (r/'public_html/assets/css/shared/reset.css').is_file()
assert (r/'public_html/assets/css/shared/components/interaction.css').is_file()
assert 'av3-dashboard' in (r/'app/Views/pages/admin/dashboard/index.php').read_text(encoding='utf8')
for shared in ['tokens.css','base.css','utilities.css','components/ui.css','components/buttons.css','components/interaction.css']:
 assert 'css/shared/'+shared in h
# Broad scan, with expected legitimate PDF .grid exception.
from subprocess import run
p=run(['py','-3',str(r/'scripts/dev/audit_tailwind_wide.py')],capture_output=True,text=True)
assert p.returncode==0,p.stderr
assert 'SUMMARY 0 0' in p.stdout,p.stdout
print('ADMIN_V3_INTEGRITY_OK')
