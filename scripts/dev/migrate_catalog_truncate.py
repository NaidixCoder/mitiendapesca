from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'app/Views/partials/catalogo/facet-cats.php'
s=p.read_text(encoding='utf8')
assert s.count(' truncate"')==2
p.write_text(s.replace(' truncate"',' site-truncate"'),encoding='utf8')
p=r/'public_html/assets/css/v2/components/no-tailwind-public.css'
with p.open('a',encoding='utf8') as f:f.write('\n.site-truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}\n')
print('CATALOG_TRUNCATE_MIGRATED')
