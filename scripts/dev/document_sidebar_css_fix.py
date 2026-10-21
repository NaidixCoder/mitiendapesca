from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
note="""
## Corrección de regresión visual del sidebar — 2026-10-18
- La migración automática de clases admin-* a av3-* creó una colisión entre av3-label/av3-link del sidebar y las clases de formularios/enlaces en admin-components.css.
- Se aislaron las clases del sidebar: av3-sidebar-label, av3-sidebar-link y av3-sidebar-icon en su vista y en admin-v3/layout.css. Sin rediseñar el sidebar.
- Se añadió scripts/build/sidebar_style_contract.php al build gate. Respaldo previo en C:\\xampp\\mi-pesca-backups. Validación visual pendiente.
"""
for name in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/name).open('a',encoding='utf8') as f:f.write(note)
print('DOCS_UPDATED')
