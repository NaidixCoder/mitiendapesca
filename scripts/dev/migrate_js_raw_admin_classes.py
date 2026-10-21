from pathlib import Path
import re
r=Path(r'C:\xampp\htdocs\mi-pesca')
root=r/'public_html/assets/js/admin'
cssfile=r/'public_html/assets/css/admin-v3/components/primitives.css'
css=cssfile.read_text(encoding='utf8')
tokens={'relative','absolute','border','rounded','truncate','opacity-70','opacity-80','object-cover','overflow-hidden','shrink-0','aspect-square','last:border-b-0'}
rules={'rounded':'border-radius:4px','truncate':'overflow:hidden;text-overflow:ellipsis;white-space:nowrap','opacity-70':'opacity:.7','opacity-80':'opacity:.8','object-cover':'object-fit:cover','overflow-hidden':'overflow:hidden','shrink-0':'flex-shrink:0','aspect-square':'aspect-ratio:1 / 1','last:border-b-0:last-child':'border-bottom:0'}
for name,body in rules.items():
 if name.startswith('last:'):continue
 if not re.search(r'\.av3-'+re.escape(name)+r'(?![\w-])',css):
  css+='\nbody[data-page^="admin"] .av3-'+name+'{'+body+'}\n'
if '.av3-last-no-bottom:last-child' not in css:
 css+='\nbody[data-page^="admin"] .av3-last-no-bottom:last-child{border-bottom:0}\n'
cssfile.write_text(css,encoding='utf8')
def convert(value):
 return ' '.join('av3-last-no-bottom' if token=='last:border-b-0' else 'av3-'+token if token in tokens else token for token in value.split())
changed={}
for p in root.rglob('*.js'):
 s=p.read_text(encoding='utf8');old=s
 s=re.sub(r'class="([^"]+)"',lambda m:'class="'+convert(m.group(1))+'"',s)
 s=re.sub(r'(\.className\s*=\s*)(["\x27])([^\n"\x27]+)(["\x27])',lambda m:m.group(1)+m.group(2)+convert(m.group(3))+m.group(4),s)
 if s!=old:
  p.write_text(s,encoding='utf8')
  changed[str(p.relative_to(r))]=True
print('RAW_JS_CLASS_MIGRATION',len(changed),'files')
for file in changed:print(file)
