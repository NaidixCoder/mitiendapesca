from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
files=['app/Views/pages/admin/arca/test.php','app/Views/pages/admin/brands/index.php','app/Views/pages/admin/catalog_tags/index.php','app/Views/pages/admin/categories/form.php']
import re
labels={'arca':('Facturación', '/admin/facturacion/control'),'brands':('Catálogo','/admin/brands'),'catalog_tags':('Catálogo','/admin/catalog-tags'),'categories':('Catálogo','/admin/categories')}
for name in files:
 p=r/name
 s=p.read_text(encoding='utf8')
 sec=p.relative_to(r/'app/Views/pages/admin').parts[0]
 label,href=labels[sec]
 s=re.sub(r"\['label'=>'Index\.php','href'=>url\('/admin'\)\]",lambda m:"['label'=>'"+label+"','href'=>url('"+href+"')]",s)
 s=re.sub(r"\['label'=>'Form\.php','href'=>url\('/admin'\)\]",lambda m:"['label'=>'"+label+"','href'=>url('"+href+"')]",s)
 p.write_text(s,encoding='utf8')
print('BREADCRUMB_SECTION_FIXED')
