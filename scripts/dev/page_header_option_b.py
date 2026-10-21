from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'app/Views/partials/admin/page_header.php'
s=p.read_text(encoding='utf8')
s=s.replace('<?php if ($adminHeaderBreadcrumbs): ?>','<?php if ($adminHeaderBreadcrumbs): ?>',1)
s=s.replace('<li>\n        <?php $label=', '<li>\n        <?php $label=',1)
s=s.replace("<?php if ($href !== '' && $i < count($adminHeaderBreadcrumbs)-1): ?><a href=\"<?= e($href) ?>\"><?= e($label) ?></a><?php else: ?><span aria-current=\"page\"><?= e($label) ?></span><?php endif; ?>",
"""<?php if ($href !== '' && $i < count($adminHeaderBreadcrumbs)-1): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span<?= $i === count($adminHeaderBreadcrumbs)-1 ? ' aria-current="page"' : '' ?>><?= e($label) ?></span><?php endif; ?>""",1)
# avoid meaningless right-side "Estado de la tienda" on dashboard; no business-state data connected
p.write_text(s,encoding='utf8')
p=R/'app/Views/pages/admin/dashboard/index.php'
s=p.read_text(encoding='utf8')
s=s.replace("$adminHeaderDescription='';","$adminHeaderDescription='Actividad y rendimiento de tu tienda.';",1)
s=s.replace("$adminHeaderActions='<span class=\"av3-dashboard__context\">Estado de la tienda</span>';","$adminHeaderActions='';",1)
p.write_text(s,encoding='utf8')
p=R/'public_html/assets/css/admin-v3/components/page-header.css'
p.write_text("""/* MiTiendaPesca — Page Header, opción B. Contrato visual compartido. */
body[data-page^="admin"] .av3-page-header{min-width:0;margin:0;color:#182b39}
body[data-page^="admin"] .av3-page-header__breadcrumb{margin:0 0 12px}
body[data-page^="admin"] .av3-page-header__breadcrumb ol{list-style:none;display:flex;align-items:center;flex-wrap:wrap;gap:8px;margin:0;padding:0}
body[data-page^="admin"] .av3-page-header__breadcrumb li{display:inline-flex;align-items:center;gap:8px;font-size:12px;line-height:1.4;color:#647786}
body[data-page^="admin"] .av3-page-header__breadcrumb li+li::before{content:"›";font-size:16px;line-height:1;color:#9daeb6;font-weight:400}
body[data-page^="admin"] .av3-page-header__breadcrumb a{color:#647786;text-decoration:none;transition:color .15s ease}
body[data-page^="admin"] .av3-page-header__breadcrumb a:hover{color:#006b6b;text-decoration:underline}
body[data-page^="admin"] .av3-page-header__breadcrumb a:focus-visible{outline:2px solid #006b6b;outline-offset:3px;border-radius:2px}
body[data-page^="admin"] .av3-page-header__breadcrumb [aria-current="page"]{color:#314a58;font-weight:600}
body[data-page^="admin"] .av3-page-header__main{display:flex;justify-content:space-between;align-items:center;gap:20px;flex-wrap:wrap}
body[data-page^="admin"] .av3-page-header__copy{min-width:0;flex:1 1 340px}
body[data-page^="admin"] .av3-page-header__copy h1{font-family:var(--v2-font-display,"Segoe UI",sans-serif);font-size:24px;font-weight:600;line-height:1.25;letter-spacing:-.02em;color:#182b39;margin:0}
body[data-page^="admin"] .av3-page-header__copy p{font-size:13px;line-height:1.5;color:#647786;margin:6px 0 0}
body[data-page^="admin"] .av3-page-header__actions{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:8px}
@media(max-width:650px){body[data-page^="admin"] .av3-page-header__main{align-items:flex-start;gap:12px}body[data-page^="admin"] .av3-page-header__actions{justify-content:flex-start}body[data-page^="admin"] .av3-page-header__copy{flex-basis:100%}}
""",encoding='utf8')
p=R/'public_html/assets/css/admin-v3/components/master-shell.css'
s=p.read_text(encoding='utf8').replace('padding:19px var(--v2-content-gutter) 18px','padding:21px var(--v2-content-gutter) 22px')
p.write_text(s,encoding='utf8')
print('PAGE_HEADER_B_APPLIED')
