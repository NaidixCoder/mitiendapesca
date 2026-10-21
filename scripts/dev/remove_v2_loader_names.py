from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
files=[r/'app/Views/partials/head.php']+[p for p in (r/'scripts').rglob('*') if p.suffix in ('.php','.py') and ('adminStyles' in p.read_text(encoding='utf8',errors='replace') or 'publicStyles' in p.read_text(encoding='utf8',errors='replace'))]
for p in files:
 s=p.read_text(encoding='utf8')
 s=s.replace('adminStyles','adminStyles').replace('publicStyles','publicStyles')
 p.write_text(s,encoding='utf8')
old=r/'public_html/assets/css/public/pages/home-hero-v2.css'
new=r/'public_html/assets/css/public/pages/home-hero.css'
h=r/'app/Views/partials/head.php'
s=h.read_text(encoding='utf8')
assert old.is_file() and not new.exists()
assert "'css/public/pages/home-hero-v2.css'" in s
new.write_bytes(old.read_bytes());old.unlink()
h.write_text(s.replace("'css/public/pages/home-hero-v2.css'","'css/public/pages/home-hero.css'"),encoding='utf8')
print('VERSION_NAMES_REMOVED',len(files),'head and tools; home hero renamed')
