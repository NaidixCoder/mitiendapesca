from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
h=r/'app/Views/partials/head.php'
s=h.read_text(encoding='utf8')
target="""  <?php if (!$isCatalogPage): ?>
  <link rel="stylesheet" href="<?= e(asset('css/aside.css') . $assetQuery) ?>">
  <?php endif; ?>
"""
assert target in s
assert not (r/'public_html/assets/css/aside.css').exists()
h.write_text(s.replace(target,''),encoding='utf8')
print('REMOVED_DEAD_PUBLIC_CSS_404')
