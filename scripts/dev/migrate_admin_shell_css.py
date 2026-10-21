from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
old=r/'public_html/assets/css/v2/layouts/admin-shell.css'
new=r/'public_html/assets/css/admin-v3/layout.css'
head=r/'app/Views/partials/head.php'
s=head.read_text(encoding='utf8')
assert "'css/v2/layouts/admin-shell.css'," in s
assert old.exists()
new.write_bytes(old.read_bytes())
head.write_text(s.replace("'css/v2/layouts/admin-shell.css',","'css/admin-v3/layout.css',"),encoding='utf8')
old.unlink()
print('ADMIN_SHELL_CSS_MOVED_VERBATIM',new.stat().st_size)
