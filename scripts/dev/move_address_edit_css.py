from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
src=r/'public_html/assets/css/v2/pages/public/account-address-edit.css'
dst=r/'public_html/assets/css/public/pages/account-address-edit.css'
ctl=r/'app/Controllers/Account/AddressesController.php'
s=ctl.read_text(encoding='utf8')
assert src.is_file() and not dst.exists()
assert s.count('css/v2/pages/public/account-address-edit.css')==1
dst.write_bytes(src.read_bytes())
ctl.write_text(s.replace('css/v2/pages/public/account-address-edit.css','css/public/pages/account-address-edit.css'),encoding='utf8')
src.unlink()
print('ADDRESS_EDITOR_CSS_MIGRATED',len(s))
