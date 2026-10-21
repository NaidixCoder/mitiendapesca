from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
p=R/'app/Views/partials/admin/page_header.php';s=p.read_text(encoding='utf8')
a=s.index('<header class="av3-page-header');b=s.index('</header>',a)+len('</header>')
replacement='''<header class="av3-page-header<?= $adminHeaderClass !== '' ? ' ' . e($adminHeaderClass) : '' ?>" aria-label="Encabezado de página">
  <div class="av3-page-header__main">
    <div class="av3-page-header__copy">
      <h1><?= e($adminHeaderTitle) ?></h1>
      <?php if ($adminHeaderBreadcrumbs): ?>
      <nav class="av3-page-header__breadcrumb" aria-label="Ruta de navegación">
        <ol>
          <?php foreach ($adminHeaderBreadcrumbs as $i => $crumb): ?>
          <li>
            <?php $label=(string)($crumb['label'] ?? ''); $href=(string)($crumb['href'] ?? ''); ?>
            <?php if ($href !== '' && $i < count($adminHeaderBreadcrumbs)-1): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span<?= $i === count($adminHeaderBreadcrumbs)-1 ? ' aria-current="page"' : '' ?>><?= e($label) ?></span><?php endif; ?>
          </li>
          <?php endforeach; ?>
        </ol>
      </nav>
      <?php endif; ?>
    </div>
    <?php if ($adminHeaderActions !== ''): ?><div class="av3-page-header__actions"><?= $adminHeaderActions ?></div><?php endif; ?>
  </div>
</header>'''
p.write_text(s[:a]+replacement+s[b:],encoding='utf8')
p=R/'public_html/assets/css/admin-v3/components/page-header.css'
p.write_text('''/* MiTiendaPesca: Page Header compacto para aplicación de gestión */
body[data-page^="admin"] .av3-page-header{margin:0;min-width:0;color:#182b39}
body[data-page^="admin"] .av3-page-header__main{display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:36px}
body[data-page^="admin"] .av3-page-header__copy{display:flex;flex-direction:column;justify-content:center;gap:3px;min-width:0;flex:1 1 auto}
body[data-page^="admin"] .av3-page-header__copy h1{margin:0;font-family:var(--v2-font-display,"Segoe UI",sans-serif);font-size:20px;line-height:1.25;font-weight:600;letter-spacing:-.015em;color:#182b39}
body[data-page^="admin"] .av3-page-header__breadcrumb{margin:0;min-width:0}
body[data-page^="admin"] .av3-page-header__breadcrumb ol{display:flex;align-items:center;flex-wrap:wrap;list-style:none;gap:7px;padding:0;margin:0}
body[data-page^="admin"] .av3-page-header__breadcrumb li{display:inline-flex;align-items:center;gap:7px;font-size:11px;line-height:1.35;color:#647786}
body[data-page^="admin"] .av3-page-header__breadcrumb li+li::before{content:"›";font-size:14px;line-height:1;color:#9daeb6}
body[data-page^="admin"] .av3-page-header__breadcrumb a{color:#647786;text-decoration:none}
body[data-page^="admin"] .av3-page-header__breadcrumb a:hover{color:#006b6b;text-decoration:underline}
body[data-page^="admin"] .av3-page-header__breadcrumb a:focus-visible{outline:2px solid #006b6b;outline-offset:2px}
body[data-page^="admin"] .av3-page-header__breadcrumb [aria-current="page"]{color:#405766;font-weight:500}
body[data-page^="admin"] .av3-page-header__actions{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:8px;flex:0 1 auto}
@media(max-width:650px){body[data-page^="admin"] .av3-page-header__main{align-items:flex-start;flex-wrap:wrap;gap:10px}body[data-page^="admin"] .av3-page-header__actions{justify-content:flex-start;width:100%}}
''',encoding='utf8')
p=R/'public_html/assets/css/admin-v3/components/master-shell.css';s=p.read_text(encoding='utf8')
assert 'padding:21px var(--v2-content-gutter) 22px' in s
p.write_text(s.replace('padding:21px var(--v2-content-gutter) 22px','padding:12px var(--v2-content-gutter) 12px'),encoding='utf8')
print('COMPACT_HEADER_INSTALLED')
