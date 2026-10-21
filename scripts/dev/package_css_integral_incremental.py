from pathlib import Path
import datetime,zipfile,json
r=Path(r'C:\xampp\htdocs\mi-pesca');now=datetime.datetime.now()
notes=f'''
## Consolidación de estructura ({now.strftime('%Y-%m-%d %H:%M')})
- Carpetas CSS operativas: css/shared, css/public y css/admin-v3.
- Módulos compartidos importados desde css/shared; 31 hojas públicas trasladadas a css/public, incluida edición de direcciones desde AddressesController.
- CSS público principal movido a css/public/app.css e importación de Mi Cuenta a css/public/components/account.css. Se verificaron cuatro imports relativos.
- Se removió la importación pública del viejo paquete admin y sus cuatro archivos, sin consumidores directos adicionales.
- Tres agregadores v2 obsoletos eliminados, con respaldos en C:\\xampp\\mi-pesca-backups; carpeta css/v2 sin archivos activos.
- Los estilos administrativos legacy-bridge y foundations no están eliminados: todavía tienen consumidores. Las 17 reglas sin referencias de ui-admin-compat se retiraron con respaldo.
- Eliminar nombres v2 y el CDN Tailwind no garantiza paridad visual: la revisión funcional y visual de rutas públicas y admin sigue siendo requisito de cierre.
'''
for name in ['PLAN_CSS_UNIFICADO.md','MANIFEST.md']:
 with (r/name).open('a',encoding='utf8') as f:f.write(notes)
start=datetime.datetime(2026,10,17,22,0).timestamp()
dirs=['app/Views','app/Controllers','public_html/assets/css','public_html/assets/js','scripts/build','docs']
files=[]
for d in dirs:
 files.extend([p for p in (r/d).rglob('*') if p.is_file() and p.suffix in ('.php','.js','.css','.json','.md') and p.stat().st_mtime>=start])
files += [r/'MANIFEST.md',r/'PLAN_CSS_UNIFICADO.md']
for p in (r/'scripts/dev').rglob('*.py'):
 if p.stat().st_mtime>=start and any(w in p.name for w in ('css','tailwind','migrate','loading','opacity','hidden','grid','bricks','checkpoint','unification','prune','legacy','public','contract','style','audit','verify')):files.append(p)
files=list(dict.fromkeys(files))
deleted=['public_html/assets/css/v2/index.css','public_html/assets/css/v2/admin.css','public_html/assets/css/v2/public.css','public_html/assets/css/app.css','public_html/assets/css/components/account.css','public_html/assets/css/components/admin.css','public_html/assets/css/components/admin/base.css','public_html/assets/css/components/admin/ops.css','public_html/assets/css/components/admin/variants.css']
mapping=json.loads((r/'docs/REUBICACION_CSS_PUBLICO.json').read_text(encoding='utf8'))
deleted += ['public_html/assets/'+p for p in mapping.keys()]
out=r/'CSS_UNIFICACION_INTEGRAL_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for p in files:z.write(p,p.relative_to(r).as_posix())
 z.writestr('DELETIONS.txt','\n'.join(sorted(set(deleted)))+'\n')
with zipfile.ZipFile(out) as z:print('INTEGRAL_INCREMENTAL',len(z.namelist()),out.stat().st_size,z.testzip())
