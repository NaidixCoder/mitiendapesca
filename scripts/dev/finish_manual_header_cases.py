from pathlib import Path
import shutil,datetime,re
R=Path(r'C:\xampp\htdocs\mi-pesca')
P=R/'app/Views/partials/admin'
V=R/'app/Views/pages/admin'
changes={}
def put(p,s):changes[p]=s
# Attributes index: preserve statistics and primary action outside header copy.
p=P/'attributes/index/header.php';s=p.read_text(encoding='utf8')
start=s.index('    <div class="av3-flex av3-flex-wrap av3-items-center')
end=s.index('    </div>',start)+len('    </div>')
stats=s[start:end]
new='''<?php
$adminHeaderTitle='Atributos';
$adminHeaderDescription='Definí atributos de variantes y sus valores reutilizables.';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Atributos']];
$adminHeaderActions='<a class="btn-primary" href="'.url('/admin/attributes/new').'">Nuevo atributo</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
'''+stats+'\n'
put(p,new)
# Login audit
p=P/'audit/logins/header.php'
put(p,'''<?php
$adminHeaderTitle='Inicios de sesión';
$adminHeaderDescription='Eventos de seguridad relacionados con el acceso al sistema.';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Auditoría','href'=>url('/admin/audit')],['label'=>'Inicios de sesión']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
''')
# Audit main: add page header at page root; remove duplicate hero title.
p=P/'audit/index/content.php';s=p.read_text(encoding='utf8')
needle='''    <div class="audit-wrap audit-min">'''
assert needle in s
header='''    <?php
    $adminHeaderTitle='Auditoría';
    $adminHeaderDescription='Registro de acciones administrativas y del sistema.';
    $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Auditoría']];
    require BASE_PATH.'/app/Views/partials/admin/page_header.php';
    ?>
'''
put(p,s.replace(needle,header+needle,1))
p=P/'audit/index/hero.php';s=p.read_text(encoding='utf8')
s,n=re.subn(r'\s*<div class="audit-hero__title">\s*<h1>Auditoria</h1>\s*<p>Registro de acciones administrativas y del sistema\.</p>\s*</div>','',s,count=1)
assert n==1
put(p,s)
# Audit detail, keep data cards intact and move only navigation to new header.
p=V/'audit/show.php';s=p.read_text(encoding='utf8')
start=s.index('      <div style="display:flex; align-items:center; justify-content:space-between;')
end=s.index("      <?php require BASE_PATH.'/app/Views/partials/admin/audit/show/header_card.php';",start)
rep='''      <?php
      $adminHeaderTitle='Detalle de auditoría';
      $adminHeaderDescription='';
      $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Auditoría','href'=>url('/admin/audit')],['label'=>'Detalle']];
      $adminHeaderActions='<a class="btn btn--ghost" href="'.e((string)($backUrl ?? base_url('/admin/audit'))).'">Volver a auditoría</a>';
      require BASE_PATH.'/app/Views/partials/admin/page_header.php';
      ?>

'''
put(p,s[:start]+rep+s[end:])
# Product editor: retain functional wizard/progress and all form buttons.
p=P/'products/form/header.php';s=p.read_text(encoding='utf8')
start=s.index('  <div class="av3-header-right product-editor-head__tools">')
end=s.rfind('</header>')
assert end>start
actions=s[start:end]
rep='''<?php
$adminHeaderTitle=(string)($ui['title'] ?? 'Producto');
$adminHeaderDescription=(string)($ui['subtitle'] ?? 'Completá la información comercial del producto.');
$adminHeaderBreadcrumbs=admin_crumbs(!empty($ui['is_edit']) ? 'products.edit' : 'products.new', ['id'=>$id]);
$adminHeaderClass='product-editor-head';
ob_start(); ?>
'''+actions+'''<?php $adminHeaderActions=ob_get_clean();
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
'''
put(p,rep)
# Mayoristas: move header above form card and keep old partial a thin shared-view adapter.
p=P/'settings/mayoristas/header.php'
put(p,'''<?php
$adminHeaderTitle='Precios mayoristas';
$adminHeaderDescription='Configuración de condiciones y precios mayoristas.';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>'Mayoristas']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
''')
p=V/'settings/mayoristas.php';s=p.read_text(encoding='utf8')
needle='''<div class="av3-wrap">
  <div class="card">'''
assert needle in s
s=s.replace(needle,'''<div class="av3-wrap">
  <?php require BASE_PATH.'/app/Views/partials/admin/settings/mayoristas/header.php'; ?>
  <div class="card">''',1)
old="      <?php require BASE_PATH.'/app/Views/partials/admin/settings/mayoristas/header.php'; ?>\n"
assert old in s
s=s.replace(old,'',1);put(p,s)
# Settings public home hero: preserve guidelines as separate info panel.
p=V/'settings/home_hero.php';s=p.read_text(encoding='utf8')
m=re.search(r'<header class="av3-card av3-pad-4">([\s\S]*?)</header>',s)
assert m
rules=re.search(r'<div class="av3-space-top-3 av3-type-caption av3-opacity-80 av3-flow-1">[\s\S]*?</div>',m.group(1))
assert rules
rep='''<?php
$adminHeaderTitle='Hero del inicio';
$adminHeaderDescription='Gestioná imágenes, orden y enlaces del carrusel principal.';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>'Hero del inicio']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<div class="av3-card av3-pad-4">'''+rules.group(0)+'''</div>'''
put(p,s[:m.start()]+rep+s[m.end():])
# Settings home sections: no need for empty content card.
p=V/'settings/home_sections.php';s=p.read_text(encoding='utf8')
m=re.search(r'<header class="av3-card av3-pad-4 av3-flow-1">([\s\S]*?)</header>',s)
assert m
rep='''<?php
$adminHeaderTitle='Secciones del inicio';
$adminHeaderDescription='Administrá las secciones dinámicas de productos del inicio.';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>'Secciones del inicio']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>'''
put(p,s[:m.start()]+rep+s[m.end():])
# Email template detail had Windows-1252 dash; replace the entire old partial in UTF-8.
p=P/'marketing_email/templates_show/header.php'
put(p,'''<?php
/** @var array<string,mixed> $template_show */
$adminHeaderTitle=(string)($template_show['name'] ?? 'Plantilla');
$adminHeaderDescription='Modo: '.(string)($template_show['mode_label'] ?? 'Visual').' · Creada: '.(string)($template_show['created_at'] ?? '');
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Marketing','href'=>url('/admin/marketing/campanas')],['label'=>'Plantillas','href'=>url('/admin/marketing/email/plantillas')],['label'=>$adminHeaderTitle]];
ob_start(); ?>
<a href="<?= e((string)($template_show['back_url'] ?? '#')) ?>" class="av3-ui-btn">Volver</a>
<a href="<?= e((string)($template_show['use_url'] ?? '#')) ?>" class="av3-ui-btn av3-ui-btn--primary">Usar en campaña</a>
<?php $adminHeaderActions=ob_get_clean();
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
''')
backup=Path(r'C:\xampp\mi-pesca-backups')/('header_hand_tuned_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S'))
for p,t in changes.items():
 dest=backup/p.relative_to(R);dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(p,dest)
 p.write_text(t,encoding='utf8')
 print('UPDATED',p.relative_to(R))
print('FILES',len(changes),'BACKUP',backup)
