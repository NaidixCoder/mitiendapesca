from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'app/Views/pages/admin/analytics/index.php'
s=p.read_text(encoding='utf8')
assert 'class="av3-analytics"' in s
s=s.replace('class="av3-analytics"','class="av3-analytics av3-ui-page"')
s=s.replace('class="av3-analytics__header"','class="av3-analytics__header av3-ui-page-header"')
s=s.replace('class="av3-analytics__back"','class="av3-analytics__back av3-ui-btn"')
p.write_text(s,encoding='utf8')
p=r/'app/Views/partials/admin/analytics/overview.php'
s=p.read_text(encoding='utf8')
s=s.replace('class="av3-analytics__panel"','class="av3-analytics__panel av3-ui-card"')
s=s.replace('class="av3-analytics__table-wrap"','class="av3-analytics__table-wrap av3-ui-table-scroll"')
s=s.replace('class="av3-analytics__table"','class="av3-analytics__table av3-ui-table"')
s=s.replace('class="av3-analytics__tag"','class="av3-analytics__tag av3-ui-badge"')
p.write_text(s,encoding='utf8')
print('ANALYTICS_COMPONENTS_CONNECTED')
