from pathlib import Path
import re
r=Path(__file__).resolve().parents[2]
h=(r/'app/Views/partials/head.php').read_text(encoding='utf8')
v=(r/'app/Views/pages/admin/dashboard/index.php').read_text(encoding='utf8')
styles=(r/'public_html/assets/css/admin-v3/pages/dashboard.css').read_text(encoding='utf8')
assert h.count('css/admin-v3/pages/dashboard.css') == 1
assert 'dashboard-2026.css' not in h
assert not (r/'public_html/assets/css/v2/pages/admin/dashboard-2026.css').exists()
assert 'class="av3-dashboard"' in v and '<div class="admin-surface">' in v
assert 'data-admin-row-link' in v and 'id="pending-orders"' in v
assert not re.search(r'class="mt-',v)
assert 'body[data-page^="admin"] .av3-dashboard' in styles
print('ADMIN_V3_DASHBOARD_CONTRACT_OK')
