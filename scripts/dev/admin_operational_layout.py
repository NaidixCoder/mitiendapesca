from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/css/admin-v3/layout.css'
s=p.read_text(encoding='utf8')
marker='/* Admin content width modes — opt-in */'
if marker not in s:
 s+='\n'+marker+'''
body[data-page^="admin"] .av3-layout--operational{width:100%;max-width:none;margin-inline:0;min-width:0}
body[data-page^="admin"] .av3-layout--detail{width:min(100%,1280px);max-width:1280px;margin-inline:auto;min-width:0}
body[data-page^="admin"] .av3-layout--form{width:min(100%,860px);max-width:860px;margin-inline:auto;min-width:0}
'''
 p.write_text(s,encoding='utf8')
p=r/'app/Views/pages/admin/analytics/index.php'
s=p.read_text(encoding='utf8').replace('class="av3-wrap av3-analytics av3-ui-page"','class="av3-analytics av3-ui-page av3-layout--operational"')
p.write_text(s,encoding='utf8')
p=r/'app/Views/partials/admin/analytics/overview.php'
s=p.read_text(encoding='utf8')
if 'av3-analytics__funnel-layout' not in s:
 s=s.replace('  <div class="av3-analytics__funnel">','  <div class="av3-analytics__funnel-layout"><div class="av3-analytics__funnel">',1)
 needle='''  </div>
</section>
<section class="av3-analytics__section" aria-labelledby="analytics-reports-title">'''
 replace='''  </div>
  <aside class="av3-analytics__overview" aria-label="Resumen de tráfico">
    <h3>Actividad registrada</h3>
    <p>Datos del mismo período, sin estimaciones.</p>
    <dl>
      <div><dt>Páginas con visitas</dt><dd><?= count($na['top_pages'] ?? []) ?></dd></div>
      <div><dt>Fuentes de tráfico</dt><dd><?= count($na['top_sources'] ?? []) ?></dd></div>
      <div><dt>Productos consultados</dt><dd><?= count($na['top_products'] ?? []) ?></dd></div>
    </dl>
  </aside></div>
</section>
<section class="av3-analytics__section" aria-labelledby="analytics-reports-title">'''
 assert needle in s
 s=s.replace(needle,replace,1)
 p.write_text(s,encoding='utf8')
p=r/'public_html/assets/css/admin-v3/pages/analytics.css'
s=p.read_text(encoding='utf8')
s=s.replace('width:min(var(--v2-content-max),100%);max-width:var(--v2-content-max);margin:0 auto;', 'width:100%;max-width:none;margin:0;')
s=s.replace('grid-template-columns:repeat(3,minmax(0,300px));gap:12px;justify-content:start;', 'grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;')
s=s.replace('grid-template-columns:repeat(2,minmax(0,480px));justify-content:start;gap:16px','grid-template-columns:repeat(2,minmax(0,1fr));gap:16px')
s=s.replace('max-width:920px;padding:18px 20px;', 'min-width:0;padding:18px 20px;')
if '/* Fluid operational analytics */' not in s:
 s+='''
/* Fluid operational analytics */
body[data-page^="admin"] .av3-analytics.av3-layout--operational{width:100%;max-width:none;margin-inline:0;padding-inline:0}
body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:repeat(3,minmax(0,1fr))}
body[data-page^="admin"] .av3-analytics__reports{grid-template-columns:repeat(2,minmax(0,1fr))}
body[data-page^="admin"] .av3-analytics__funnel-layout{display:grid;grid-template-columns:minmax(0,1.85fr) minmax(260px,1fr);gap:16px;align-items:stretch}
body[data-page^="admin"] .av3-analytics__overview{background:#fff;border:1px solid var(--av3-color-border);border-radius:11px;padding:18px 20px;min-width:0}
body[data-page^="admin"] .av3-analytics__overview h3{font-size:14px;font-weight:600;margin:0 0 5px;color:#182b39}
body[data-page^="admin"] .av3-analytics__overview p{font-size:12px;color:#647786;margin:0 0 12px}
body[data-page^="admin"] .av3-analytics__overview dl{margin:0}
body[data-page^="admin"] .av3-analytics__overview dl>div{display:flex;justify-content:space-between;gap:10px;border-top:1px solid #e9eef0;padding:10px 0}
body[data-page^="admin"] .av3-analytics__overview dt{font-size:13px;color:#516673}
body[data-page^="admin"] .av3-analytics__overview dd{margin:0;font-size:15px;font-weight:600;color:#182b39;font-variant-numeric:tabular-nums}
@media(max-width:950px){body[data-page^="admin"] .av3-analytics__funnel-layout{grid-template-columns:1fr}}
@media(max-width:780px){body[data-page^="admin"] .av3-analytics__reports{grid-template-columns:1fr}body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:480px){body[data-page^="admin"] .av3-analytics__kpis{grid-template-columns:1fr}}
'''
 p.write_text(s,encoding='utf8')
c=r/'CONTRATO_VISUAL_ADMIN_2026.md'
with c.open('a',encoding='utf8') as f:f.write('''
## Tipos de contenedor (2026-10-19)
- Operativo, clase av3-layout--operational: ancho fluido disponible con gutter del shell, para listados y analíticas.
- Detalle, clase av3-layout--detail: ancho máximo 1280px.
- Formulario, clase av3-layout--form: ancho máximo 860px centrado.
- Modos opt-in; aside y padding de 24px exclusivamente propiedad del layout global. Sin alterar páginas existentes.
''')
for name in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/name).open('a',encoding='utf8') as f:f.write('\n## Layout fluido 2026-10-19\n- Tres contenedores opcionales en admin-v3/layout.css (operativo/detalle/formulario).\n- Analíticas usa modo operativo, KPI y tablas fluidos, panel de conversión y resumen lateral.\n- Sin cambiar sidebar ni vistas restantes. Sin ZIP.\n')
print('APPLIED_OPERATIONAL_LAYOUT')
