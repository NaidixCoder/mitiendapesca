from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/checkout/_deprecated/mp_bricks.js'
s=p.read_text(encoding='utf8')
for old,new in [('text-sm','site-checkout-feedback'),('text-red-700','site-checkout-feedback--error'),('text-gray-700','site-checkout-feedback--neutral')]:
 assert old in s
 s=s.replace("'"+old+"'","'"+new+"'")
p.write_text(s,encoding='utf8')
css=r/'public_html/assets/css/v2/components/no-tailwind-public.css'
with css.open('a',encoding='utf8') as f:f.write('\n.site-checkout-feedback{font-size:14px}\n.site-checkout-feedback--error{color:#b91c1c}\n.site-checkout-feedback--neutral{color:#374151}\n')
print('DEPRECATED_CHECKOUT_CLASSES_MIGRATED')
