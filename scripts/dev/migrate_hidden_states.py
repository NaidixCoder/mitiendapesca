from pathlib import Path
import re,zipfile,datetime
R=Path(r'C:\xampp\htdocs\mi-pesca')
roots=[R/'app/Views', R/'public_html/assets/js']
css=R/'public_html/assets/css/v2/pages/public/checkout.css'
shared=R/'public_html/assets/css/v2/components/no-tailwind-public.css'
files=[p for base in roots for p in base.rglob('*') if p.suffix in ('.php','.js')]
modified={}
def replace_class_tokens(value):
 return re.sub(r'(?<![\w-])hidden(?![\w-])','ui-is-hidden',value)
for p in files:
 s=p.read_text(encoding='utf8',errors='replace');old=s
 # Literal HTML class attributes (PHP templates and JS strings).
 s=re.sub(r'(class\s*=\s*["\x27])([^"\x27]*)(["\x27])',lambda m:m.group(1)+replace_class_tokens(m.group(2))+m.group(3),s)
 # JS className string assignments.
 if p.suffix=='.js':
  s=re.sub(r'(\.className\s*=\s*["\x27])([^"\x27]*)(["\x27])',lambda m:m.group(1)+replace_class_tokens(m.group(2))+m.group(3),s)
  # JS classList operations.
  s=re.sub(r'\.classList\.(?:add|remove|toggle|contains|replace)\s*\([^\)]*\)',lambda m:re.sub(r'(["\x27])hidden\1',lambda x:x.group(1)+'ui-is-hidden'+x.group(1),m.group(0)),s)
  s=s.replace("'.hidden'","'.ui-is-hidden'").replace('".hidden"','".ui-is-hidden"')
 if s!=old:modified[p]=s.encode('utf8')
old_css=css.read_text(encoding='utf8')
new_css=old_css.replace('.checkout-step.hidden','.checkout-step.ui-is-hidden').replace('.checkout-step:not(.hidden)','.checkout-step:not(.ui-is-hidden)').replace('.checkout-step--address:not(.hidden)','.checkout-step--address:not(.ui-is-hidden)')
if new_css!=old_css:modified[css]=new_css.encode('utf8')
old_shared=shared.read_text(encoding='utf8')
if '.ui-is-hidden' not in old_shared:
 modified[shared]=(old_shared+'\n/* Visibility state replaces Tailwind hidden in PHP and JS. */\n.ui-is-hidden{display:none!important}\n').encode('utf8')
backup=Path(r'C:\xampp\mi-pesca-backups')/('css_hidden_state_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
backup.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:
 for p in modified:z.write(p,p.relative_to(R).as_posix())
for p,data in modified.items():p.write_bytes(data)
print('HIDDEN_MIGRATED',len(modified),'files','BACKUP',backup)
