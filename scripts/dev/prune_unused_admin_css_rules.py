from pathlib import Path
import re,json,tinycss2,zipfile,datetime
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/css/admin-v3/components/ui-admin-compat.css'
candidate=json.loads((r/'docs/AUDITORIA_CSS_REGLAS_CANDIDATAS.json').read_text(encoding='utf8'))['ui-admin-compat.css']['removable_simple_rules']
old=p.read_text(encoding='utf8')
rules=tinycss2.parse_stylesheet(old,skip_whitespace=False,skip_comments=False)
filtered=[];removed=[]
for item in rules:
 if item.type=='qualified-rule' and tinycss2.serialize(item.prelude).strip() in candidate:
  removed.append(tinycss2.serialize(item.prelude).strip())
 else: filtered.append(item)
assert len(removed)==len(candidate)==17, (len(removed),len(candidate))
backup=Path(r'C:\xampp\mi-pesca-backups')/('ui_admin_unused_rules_'+datetime.datetime.now().strftime('%Y%m%d_%H%M%S')+'.zip')
with zipfile.ZipFile(backup,'w',zipfile.ZIP_DEFLATED) as z:z.writestr(p.relative_to(r).as_posix(),old)
new=tinycss2.serialize(filtered)
p.write_text(new,encoding='utf8')
print('REMOVED_UNUSED_CSS_RULES',len(removed),'BACKUP',backup)
