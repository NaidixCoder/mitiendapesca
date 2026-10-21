from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\js\admin\core\sidebar.js')
s=p.read_text(encoding='utf8').replace("document.querySelector('[data-av3-mobile-backdrop]')", "document.querySelector?.('[data-av3-mobile-backdrop]')").replace("document.querySelector('[data-av3-sidebar]')", "document.querySelector?.('[data-av3-sidebar]')").replace("  document.addEventListener('keydown',", "  document.addEventListener?.('keydown',")
p.write_text(s,encoding='utf8')
print('SMOKE_COMPAT_FIXED')
