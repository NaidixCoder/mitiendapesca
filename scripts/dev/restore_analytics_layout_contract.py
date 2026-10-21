from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'app/Views/pages/admin/analytics/index.php'
s=p.read_text(encoding='utf8')
s=s.replace('<main class="av3-analytics av3-ui-page"', '<main class="av3-wrap av3-analytics av3-ui-page"')
p.write_text(s,encoding='utf8')
p=r/'public_html/assets/css/admin-v3/pages/analytics.css'
s=p.read_text(encoding='utf8')
s=s.replace('width:100%;max-width:1500px;margin:0 auto;padding:24px 24px 64px;', 'width:min(var(--v2-content-max),100%);max-width:var(--v2-content-max);margin:0 auto;padding:0 0 36px;')
s=s.replace('font-size:26px;font-weight:750;', 'font-size:24px;font-weight:600;')
s=s.replace('font-size:29px;line-height:1.08;letter-spacing:-.045em;font-weight:750;', 'font-size:28px;line-height:1.08;letter-spacing:-.025em;font-weight:600;')
s=s.replace('.av3-analytics{padding:18px 12px 45px}', '.av3-analytics{padding:0 0 32px}')
p.write_text(s,encoding='utf8')
print('GLOBAL_LAYOUT_RESTORED')
