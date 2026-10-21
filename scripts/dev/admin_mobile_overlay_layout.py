from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'app/Views/layouts/admin.php'
s=p.read_text(encoding='utf8')
s=s.replace('''  <div class="av3-master-workspace">''','''  <button type="button" class="av3-mobile-backdrop" data-av3-mobile-backdrop aria-label="Cerrar menú" tabindex="-1" hidden></button>
  <div class="av3-master-workspace">''')
s=s.replace('''      <div class="av3-master-header">
        <?= $GLOBALS['_admin_master_header'] ?>''','''      <div class="av3-master-header">
        <button type="button" id="av3-mobile-menu-toggle" class="av3-mobile-menu-toggle" aria-controls="av3-admin-navigation" aria-expanded="false" aria-label="Abrir menú de navegación">
          <span aria-hidden="true">☰</span>
        </button>
        <?= $GLOBALS['_admin_master_header'] ?>''')
p.write_text(s,encoding='utf8')
p=R/'app/Views/partials/admin/sidebar_v2.php'
s=p.read_text(encoding='utf8').replace('<aside class="av3-sidebar" data-av3-sidebar>','<aside id="av3-admin-navigation" class="av3-sidebar" data-av3-sidebar aria-label="Navegación administrativa">')
p.write_text(s,encoding='utf8')
p=R/'public_html/assets/css/admin-v3/components/master-shell.css'
s=p.read_text(encoding='utf8')
s+='''
/* Navegación móvil overlay: no consume ancho ni desplaza el contenido. */
body[data-page^="admin"] .av3-mobile-menu-toggle,
body[data-page^="admin"] .av3-mobile-backdrop{display:none}
@media(max-width:767px){
  body[data-page^="admin"],body[data-page^="admin"].av3-expanded,body[data-page^="admin"].av3-collapsed{--v2-sidebar-offset:0px;--v2-content-gutter:16px}
  body[data-page^="admin"] .av3-master-workspace{margin-left:0!important;width:100%;min-width:0}
  body[data-page^="admin"] .av3-master-header{display:flex;align-items:center;gap:12px;padding:11px 16px}
  body[data-page^="admin"] .av3-master-header .av3-page-header{flex:1;min-width:0}
  body[data-page^="admin"] .av3-master-content{padding:16px;min-width:0}
  body[data-page^="admin"] .av3-mobile-menu-toggle{display:inline-flex;flex:none;align-items:center;justify-content:center;width:40px;height:40px;border:1px solid #dfe5e9;background:#fff;border-radius:8px;color:#006b6b;cursor:pointer;font-size:21px}
  body[data-page^="admin"] .av3-mobile-menu-toggle:focus-visible{outline:2px solid #006b6b;outline-offset:2px}
  body[data-page^="admin"] .av3-sidebar,body[data-page^="admin"].av3-collapsed .av3-sidebar,body[data-page^="admin"].av3-expanded .av3-sidebar{position:fixed;inset:0 auto 0 0;width:min(290px,85vw)!important;max-width:85vw;height:100dvh;transform:translateX(-105%);transition:transform .22s ease;z-index:120;padding:12px}
  body[data-page^="admin"].av3-mobile-nav-open .av3-sidebar{transform:translateX(0)}
  body[data-page^="admin"] .av3-sidebar .av3-brand,body[data-page^="admin"] .av3-sidebar .av3-group-title,body[data-page^="admin"] .av3-sidebar .av3-sidebar-label{display:initial!important}
  body[data-page^="admin"] .av3-sidebar .av3-sidebar-link{justify-content:flex-start!important;padding:8px 10px!important;gap:10px!important}
  body[data-page^="admin"] .av3-sidebar #av3-toggle{display:none}
  body[data-page^="admin"] .av3-mobile-backdrop:not([hidden]){display:block;position:fixed;inset:0;width:100%;height:100dvh;z-index:110;border:0;background:rgba(10,23,32,.5);cursor:pointer}
  body[data-page^="admin"].av3-mobile-nav-open{overflow:hidden}
}
@media(prefers-reduced-motion:reduce){body[data-page^="admin"] .av3-sidebar{transition:none}}
'''
p.write_text(s,encoding='utf8')
print('MOBILE_OVERLAY_MARKUP_CSS_DONE')
