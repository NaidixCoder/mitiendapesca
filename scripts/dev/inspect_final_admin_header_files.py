from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
list=[
'app/Views/partials/admin/audit/logins/header.php',
'app/Views/partials/admin/audit/index/hero.php',
'app/Views/partials/admin/audit/show/header_card.php',
'app/Views/partials/admin/products/form/header.php',
'app/Views/partials/admin/settings/mayoristas/header.php',
'app/Views/partials/admin/marketing_email/templates_show/header.php',
'app/Views/partials/admin/marketing_email/campaign_form/content.php',
'app/Views/partials/admin/attributes/index/header.php']
for x in list:
 p=R/x;s=p.read_text(encoding='utf8',errors='replace')
 print('\n###',x,'##\n',s[:2100])
