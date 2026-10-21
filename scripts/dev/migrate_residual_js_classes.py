from pathlib import Path
import re,collections
r=Path(r'C:\xampp\htdocs\mi-pesca')
mapping={'border-emerald-300':'av3-border-emerald-300','text-amber-700':'av3-text-amber-700','bg-amber-50':'av3-bg-amber-50','rounded-full':'av3-radius-full','grid-cols-2':'av3-grid-cols-2','md:grid-cols-3':'av3-md-grid-cols-3','border-emerald-200':'av3-border-emerald-200','rounded-2xl':'av3-radius-xl','shadow-xl':'av3-shadow-xl','text-base':'av3-type-base','w-2/3':'av3-width-two-thirds','border-b':'av3-border-bottom','rounded-md':'av3-radius-md','flex-1':'av3-flex-1','text-amber-800':'av3-text-amber-800'}
cnt=collections.Counter()
for p in (r/'public_html/assets/js/admin').rglob('*.js'):
 b=p.read_bytes();orig=b
 for old,new in mapping.items():
  b,n=re.subn(rb'(?<![\w:-])'+re.escape(old.encode())+rb'(?=[\s\x27\x22<>]|$)',new.encode(),b);cnt[old]+=n
 if b!=orig:p.write_bytes(b)
css=r/'public_html/assets/css/admin-v3/components/primitives.css'
with css.open('a',encoding='utf8') as f:
 f.write('\n/* Final JS-only semantic class equivalents */\n')
 f.write('body[data-page^="admin"] .av3-border-emerald-300{border-color:#6ee7b7}\n')
 f.write('body[data-page^="admin"] .av3-width-two-thirds{width:66.666667%}\n')
print('JS_REMAINING_MAPPED',sum(cnt.values()),dict(cnt))
