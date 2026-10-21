from pathlib import Path
import zipfile
r=Path(r'C:\xampp\htdocs\mi-pesca')
for fn in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/fn).open('a',encoding='utf8') as f:
  f.write('\n- Se actualizó scripts/build/admin_css_scope.php para comprobar foundations.css en admin-v3 tras su traslado (la primera ejecución del gate detectó ruta antigua).\n')
zpath=r/'CSS_UNIFICACION_CONTINUACION_INCREMENTAL.zip'
with zipfile.ZipFile(zpath,'a',zipfile.ZIP_DEFLATED) as z:
 z.write(r/'scripts/build/admin_css_scope.php','scripts/build/admin_css_scope.php')
 z.write(r/'scripts/dev/fix_admin_css_gate_path.py','scripts/dev/fix_admin_css_gate_path.py')
 # no duplicate manifests: archive already contains older manifest; rebuild below
with zipfile.ZipFile(zpath) as z: entries={n:z.read(n) for n in z.namelist()}
entries['MANIFEST.md']=(r/'MANIFEST.md').read_bytes()
entries['PLAN_CSS_UNIFICADO.md']=(r/'PLAN_CSS_UNIFICADO.md').read_bytes()
with zipfile.ZipFile(zpath,'w',zipfile.ZIP_DEFLATED) as z:
 for n,b in entries.items():z.writestr(n,b)
with zipfile.ZipFile(zpath) as z:print('UPDATED_ZIP',len(z.namelist()),z.testzip())
