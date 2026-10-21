from pathlib import Path
import re,shutil,datetime
R=Path(r'C:\xampp\htdocs\mi-pesca')
V=R/'app/Views/pages/admin/facturacion'
P=R/'app/Views/partials/admin/facturacion'
files=list(V.rglob('*.php'))+[P/'reportes/resumen/content.php']
delegates={
 'cierres_mensuales/index.php':'cierres_mensuales/index/header.php',
 'compras/form.php':'compras/form/header.php',
 'control/index.php':'control/header.php',
 'reportes/desvios.php':'reportes/desvios/header.php',
 'reportes/saldo_iva.php':'reportes/saldo_iva/header_filters.php',
}
remove=re.compile(r'<\?php\s*render_partial\(["\x27]admin/facturacion_nav["\x27]\)\s*;\s*\?>\s*')
changes={}
migrated=[]
for path in files:
 s=changes.get(path,path.read_text(encoding='utf8'))
 if not remove.search(s):continue
 t,n=remove.subn('',s,count=1)
 assert n==1
 changes[path]=t
 relative=path.relative_to(V).as_posix() if path.is_relative_to(V) else 'reportes/resumen/content.php'
 target=P/delegates[relative] if relative in delegates else path
 old=changes.get(target,target.read_text(encoding='utf8'))
 needle="require BASE_PATH.'/app/Views/partials/admin/page_header.php';"
 assert old.count(needle)==1,(relative,str(target))
 new=old.replace(needle,needle+"\nrender_partial('admin/facturacion_nav');",1)
 changes[target]=new
 migrated.append((str(path.relative_to(R)),str(target.relative_to(R))))
print('MOVE_FISC_NAV',len(migrated),'CHANGES_FILES',len(changes))
backup=Path(r'C:\xampp\mi-pesca-backups')/('fiscal_nav_below_header_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for p,s in changes.items():
 dest=backup/p.relative_to(R);dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dest)
 p.write_text(s,encoding='utf8')
print('BACKUP',backup)
for a,b in migrated:print('RELOCATED',a,'=>',b)
