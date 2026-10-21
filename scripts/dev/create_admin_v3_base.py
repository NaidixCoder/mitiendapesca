from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca'); base=R/'public_html/assets/css/admin-v3'
(base/'tokens.css').write_text('''/* Admin V3 tokens — isolated to admin only. */
body[data-page^="admin"] {
  --av3-color-text: #182B39;
  --av3-color-muted: #647786;
  --av3-color-border: #DFE5E9;
  --av3-color-primary: #006B6B;
  --av3-color-surface: #FFFFFF;
  --av3-color-background: #F6F8FA;
  --av3-radius-card: 11px;
  --av3-space-panel: 18px;
  --av3-control-height: 40px;
}
''',encoding='utf8')
(base/'components'/'common.css').write_text('''/* Cross-page reusable controls, opt-in av3 classes. */
body[data-page^="admin"] .av3-panel {
  min-width: 0;
  max-width: 100%;
  background: var(--av3-color-surface);
  border: 1px solid var(--av3-color-border);
  border-radius: var(--av3-radius-card);
}
body[data-page^="admin"] .av3-panel__heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 15px var(--av3-space-panel);
  border-bottom: 1px solid #EDF0F2;
}
body[data-page^="admin"] .av3-panel__heading h2 {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
body[data-page^="admin"] .av3-action-link {
  color: var(--av3-color-primary);
  font-size: 13px;
  font-weight: 500;
  text-decoration: none;
}
body[data-page^="admin"] .av3-action-link:hover { text-decoration: underline; }
body[data-page^="admin"] .av3-empty {
  padding: 18px;
  color: var(--av3-color-muted);
  font-size: 13px;
}
''',encoding='utf8')
head=R/'app/Views/partials/head.php'
s=head.read_text(encoding='utf8')
needle="    'css/admin-v3/pages/dashboard.css',"
assert s.count(needle)==1
s=s.replace(needle,"    'css/admin-v3/tokens.css',\n    'css/admin-v3/components/common.css',\n"+needle)
head.write_text(s,encoding='utf8')
view=R/'app/Views/pages/admin/dashboard/index.php'
s=view.read_text(encoding='utf8')
s=s.replace('class="av3-card ', 'class="av3-panel av3-card ')
s=s.replace('class="av3-card__head"', 'class="av3-panel__heading av3-card__head"')
s=s.replace('class="av3-empty"', 'class="av3-empty"')
view.write_text(s,encoding='utf8')
print('BASE_COMPONENTS_OK')
