from pathlib import Path
from zipfile import ZipFile,ZIP_DEFLATED
from datetime import datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
paths=[p for p in (r/'desktop').rglob('*') if p.is_file()]
paths += [r/'docs/04-development/pedidos-compras-recepcion-conciliacion-separadas-2026-09-29.md']
base=ZipFile(r/'mi-pesca.zip')
known=set(base.namelist())
for p in paths:
 if p.relative_to(r).as_posix() in known:raise RuntimeError('Would touch base file: '+str(p))
for p in paths:
 if not p.exists():raise RuntimeError('Missing '+str(p))
out=Path(r'C:\xampp\mi-pesca-backups')/('misistema-extraccion-recuperada-'+datetime.now().strftime('%Y%m%d-%H%M%S')+'.zip')
out.parent.mkdir(parents=True,exist_ok=True)
with ZipFile(out,'w',ZIP_DEFLATED) as backup:
 for p in paths:backup.write(p,p.relative_to(r).as_posix())
with ZipFile(out) as verify:
 assert len(verify.namelist())==len(paths)
 for p in paths:assert verify.read(p.relative_to(r).as_posix())==p.read_bytes()
for p in paths:p.unlink()
for d in sorted((r/'desktop').rglob('*'),key=lambda x:len(x.parts),reverse=True):
 if d.is_dir() and not any(d.iterdir()):d.rmdir()
if (r/'desktop').exists() and not any((r/'desktop').iterdir()):(r/'desktop').rmdir()
d=r/'docs/04-development'
if d.exists() and not any(d.iterdir()):d.rmdir()
print('BACKUP',out,'FILES_REMOVED',len(paths),'BASE_OVERWRITE_MATCHES',0)
