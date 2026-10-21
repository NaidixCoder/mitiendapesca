from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/checkout/_deprecated/mp_bricks.js'
s=p.read_text(encoding='utf8')
s=s.replace("el.className = 'text-sm ' + (","el.className = 'site-checkout-feedback ' + (")
s=s.replace("'text-green-700'","'site-checkout-feedback--success'")
p.write_text(s,encoding='utf8')
css=r/'public_html/assets/css/v2/components/no-tailwind-public.css'
with css.open('a',encoding='utf8') as f:f.write('\n.site-checkout-feedback--success{color:#15803d}\n')
print('DEPRECATED_CHECKOUT_DONE')
