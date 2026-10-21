from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'scripts/dev/verify_admin_v3_dashboard.py'
s=p.read_text(encoding='utf8').replace("'admin-row-link' in v","'data-admin-row-link' in v")
p.write_text(s,encoding='utf8')
print('CONTRACT_UPDATED')
