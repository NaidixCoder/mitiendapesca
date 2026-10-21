from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca');p=r/'public_html/assets/js/catalog/catalog.js'
s=p.read_text(encoding='utf8')
assert s.count('setupDensityToggle')==1
marker='/**\n * Toggle de densidad'
i=s.index(marker)
assert 'function setupDensityToggle()' in s[i:]
p.write_text(s[:i].rstrip()+'\n',encoding='utf8')
print('DEAD_DENSITY_TAILWIND_REMOVED')
