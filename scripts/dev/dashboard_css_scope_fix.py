from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\css\admin-v3\pages\dashboard.css')
s=p.read_text(encoding='utf-8-sig')
s=s.replace('.av3-stock-mobile{display:grid}', 'body[data-page^="admin"] .av3-stock-mobile{display:grid}')
p.write_text(s,encoding='utf8')
print('CSS_SCOPE_FIXED')
