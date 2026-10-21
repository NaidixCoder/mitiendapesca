from pathlib import Path
import re
R=Path(r'C:\xampp\htdocs\mi-pesca');old=R/'public_html/assets/css/v2/pages/admin/dashboard-2026.css'
head=(R/'app/Views/partials/head.php').read_text(encoding='utf8')
new=R/'public_html/assets/css/admin-v3/pages/dashboard.css'
view=(R/'app/Views/pages/admin/dashboard/index.php').read_text(encoding='utf8')
assert new.exists() and old.exists()
assert 'dashboard-2026.css' not in head and "css/admin-v3/pages/dashboard.css" in head
assert 'class="av3-dashboard"' in view and 'class="mt-dashboard"' not in view
assert 'admin-surface' in view and 'admin-row-link' in view and 'pending-orders' in view
assert not any(re.search(r'\bmt-(?:card|summary|table|work|performance|bottom|events|stock)\b',p.read_text(encoding='utf8',errors='replace')) for p in [new,R/'app/Views/pages/admin/dashboard/index.php'])
old.unlink()
test=R/'scripts/dev/verify_admin_v3_dashboard.py'
test.write_text("""from pathlib import Path
import re
r=Path(__file__).resolve().parents[2]
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
v=(r/'app/Views/pages/admin/dashboard/index.php').read_text(encoding='utf8')
styles=(r/'public_html/assets/css/admin-v3/pages/dashboard.css').read_text(encoding='utf8')
assert h.count('css/admin-v3/pages/dashboard.css') == 1
assert 'dashboard-2026.css' not in h
assert not (r/'public_html/assets/css/v2/pages/admin/dashboard-2026.css').exists()
assert 'class="av3-dashboard"' in v and '<div class="admin-surface">' in v
assert 'admin-row-link' in v and 'id="pending-orders"' in v
assert not re.search(r'class="mt-',v)
assert 'body[data-page^="admin"] .av3-dashboard' in styles
print('ADMIN_V3_DASHBOARD_CONTRACT_OK')
""",encoding='utf8')
print('OLD_DASHBOARD_CSS_REMOVED_AND_CONTRACT_CREATED')
