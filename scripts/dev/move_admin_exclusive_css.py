from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
H=R/'app/Views/partials/head.php';s=H.read_text(encoding='utf8')
replacements={
'css/v2/components/ui-admin.css':'css/admin-v3/components/ui-admin-compat.css',
'css/v2/components/admin-beta-bridge.css':'css/admin-v3/components/legacy-bridge.css',
'css/v2/components/admin-foundations.css':'css/admin-v3/components/foundations.css'}
for old,new in replacements.items():
 o=R/'public_html/assets'/old;n=R/'public_html/assets'/new
 assert o.is_file() and not n.exists() and s.count("'"+old+"',")==1
 n.parent.mkdir(parents=True,exist_ok=True)
 n.write_bytes(o.read_bytes())
 s=s.replace("'"+old+"',","'"+new+"',")
H.write_text(s,encoding='utf8')
for old in replacements:(R/'public_html/assets'/old).unlink()
print('ADMIN_EXCLUSIVE_CSS_MOVED',len(replacements))
