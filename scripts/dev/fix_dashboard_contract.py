from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\scripts\build\dashboard_responsive_contract.php')
s=p.read_text(encoding='utf8')
s=s.replace("$css=(string)file_get_contents", "$view.=(string)file_get_contents($root.'/app/Views/partials/admin/dashboard/trend.php');\n$css=(string)file_get_contents")
p.write_text(s,encoding='utf8')
