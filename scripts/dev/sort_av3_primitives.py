from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\public_html\assets\css\admin-v3\components\primitives.css')
s=p.read_text(encoding='utf8')
marker='/* Admin V3 design primitives — structured layout and color tokens */'
assert s.count(marker)==1
before,after=s.split(marker)
rules=[line for line in after.splitlines() if line.strip()]
def category(line):
 if line.startswith('@media(min-width:640px)'):return 2
 if line.startswith('@media(min-width:768px)'):return 3
 if line.startswith('@media(min-width:1024px)'):return 4
 if line.startswith('@media(min-width:1280px)'):return 5
 if ':hover{' in line or ':focus{' in line:return 1
 return 0
ordered=sorted(enumerate(rules),key=lambda x:(category(x[1]),x[0]))
p.write_text(before+marker+'\n'+'\n'.join(line for _,line in ordered)+'\n',encoding='utf8')
print('CSS_CASCADE_ORDER_FIXED',len(ordered),'rules')
