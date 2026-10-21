from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
m={
 'hover:bg-white':('av3-hover-surface-white','body[data-page^="admin"] .av3-hover-surface-white:hover{background-color:#fff}'),
 'md:p-6':('av3-md-pad-6','@media(min-width:768px){body[data-page^="admin"] .av3-md-pad-6{padding:24px}}'),
 'md:gap-3':('av3-md-gap-3','@media(min-width:768px){body[data-page^="admin"] .av3-md-gap-3{gap:12px}}')
}
for root in ('app/Views','public_html/assets/js/admin'):
 for p in (r/root).rglob('*'):
  if p.suffix not in ('.php','.js'):continue
  b=p.read_bytes();old=b
  for token,(replacement,css) in m.items():
   b=re.sub(rb'(?<![\w:-])'+re.escape(token.encode())+rb'(?=[\s\x27\x22<>]|$)',replacement.encode(),b)
  if b!=old:p.write_bytes(b)
css=r/'public_html/assets/css/admin-v3/components/primitives.css'
with css.open('a',encoding='utf8') as f:
 f.write('\n/* Migrated interaction/breakpoint variants */\n')
 for _,(_,rule) in m.items():f.write(rule+'\n')
print('RESPONSIVE_VARIANTS_MIGRATED')
