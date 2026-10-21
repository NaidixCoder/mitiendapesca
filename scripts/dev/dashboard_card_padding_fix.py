from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\css\admin-v3\pages\dashboard.css')
s=p.read_text(encoding='utf8')
s=s.replace('min-width:0;max-width:100%;background:#fff;\n  border:1px solid var(--dash-border);border-radius:11px;', 'min-width:0;max-width:100%;padding:0;background:#fff;\n  border:1px solid var(--dash-border);border-radius:11px;')
p.write_text(s,encoding='utf8')
print('FIXED_LEGACY_AV3_CARD_PADDING')
