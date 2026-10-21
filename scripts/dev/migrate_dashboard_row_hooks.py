from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
v=R/'app/Views/partials/admin/dashboard';files=[R/'app/Views/pages/admin/dashboard/index.php',*v.glob('*.php')]
for p in files:
 s=p.read_text(encoding='utf8')
 if 'admin-row-link' not in s:continue
 s=s.replace('class="admin-row-link"','data-admin-row-link="1"')
 s=s.replace(' admin-row-link"','" data-admin-row-link="1"')
 p.write_text(s,encoding='utf8')
js=R/'public_html/assets/js/admin/dashboard/index.js'
s=js.read_text(encoding='utf8');assert 'admin-row-link' in s
s=s.replace('.admin-row-link','[data-admin-row-link]')
js.write_text(s,encoding='utf8')
css=R/'public_html/assets/css/v2/components/ui-admin.css';s=css.read_text(encoding='utf8')
s=s.replace('.admin-row-link', '[data-admin-row-link]')
css.write_text(s,encoding='utf8')
print('HOOK_MIGRATION_OK')
