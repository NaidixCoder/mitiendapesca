from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
names=[
'app/Views/partials/admin/sidebar_v2.php',
'public_html/assets/js/admin/core/sidebar.js',
'public_html/assets/js/app.js',
'public_html/assets/css/admin-v3/layout.css',
'public_html/assets/css/v2/components/ui-admin.css',
'public_html/assets/css/admin-v3/pages/products.css'
]
for name in names:
 p=r/name;s=p.read_text(encoding='utf8')
 if name.endswith('/app.js'):s=s.replace('initAdminSidebarV2','initAdminSidebarV3')
 else:
  s=s.replace('admin-v2-','av3-')
  if name.endswith('sidebar.js'):
   s=s.replace('initAdminSidebarV2','initAdminSidebarV3')
 # leave persisted admin_v2_sidebar_state unchanged to retain prior user's preference
 p.write_text(s,encoding='utf8')
print('SIDEBAR_CLASS_NAMESPACE_MIGRATED',len(names))
