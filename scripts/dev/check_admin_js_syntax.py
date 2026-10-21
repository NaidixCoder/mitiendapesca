from pathlib import Path
import subprocess
r=Path(r'C:\xampp\htdocs\mi-pesca')
bad=[]
for p in (r/'public_html/assets/js/admin').rglob('*.js'):
 x=subprocess.run(['node','--check',str(p)],capture_output=True,text=True)
 if x.returncode:bad.append((p.relative_to(r).as_posix(),x.stderr.splitlines()[:4]))
print('JS_CHECK',len(bad),'errors');print(*bad[:14],sep='\n')
