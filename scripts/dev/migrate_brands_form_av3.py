from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
br=r/'app/Views/pages/admin/brands'
for name in ('index.php','form.php'):
 p=br/name;s=p.read_text(encoding='utf8')
 s=s.replace('class="admin-wrap space-y-4"','class="admin-wrap av3-page-stack"')
 s=s.replace('class="font-medium"','class="av3-emphasis"').replace('class="font-mono"','class="av3-code"')
 s=s.replace('class="grid md:grid-cols-2 gap-4"','class="av3-form-columns"')
 s=s.replace('class="label mt-2"','class="label av3-field-followup"')
 s=s.replace('class="mt-6"','class="av3-section-space"')
 s=s.replace('class="mt-4 flex items-center justify-end gap-2"','class="av3-form-actions-end"')
 p.write_text(s,encoding='utf8')
css=r/'public_html/assets/css/admin-v3/components/common.css'
s=css.read_text(encoding='utf8')
s+='''
/* Reusable page/form arrangements — admin only */
body[data-page^="admin"] .av3-page-stack>*+*{margin-top:16px}
body[data-page^="admin"] .av3-form-columns{display:grid;grid-template-columns:1fr;gap:16px}
@media(min-width:768px){body[data-page^="admin"] .av3-form-columns{grid-template-columns:repeat(2,minmax(0,1fr))}}
body[data-page^="admin"] .av3-emphasis{font-weight:500}
body[data-page^="admin"] .av3-code{font-family:ui-monospace,SFMono-Regular,Consolas,monospace}
body[data-page^="admin"] .av3-field-followup{display:block;margin-top:8px}
body[data-page^="admin"] .av3-section-space{margin-top:24px}
body[data-page^="admin"] .av3-form-actions-end{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:16px}
'''
css.write_text(s,encoding='utf8')
print('BRANDS_MAIN_VIEWS_MIGRATED')
