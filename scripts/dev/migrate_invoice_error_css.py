from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/account/order_factura_a.js'
s=p.read_text(encoding='utf8')
assert s.count("mt-1 text-sm text-red-600")==1
p.write_text(s.replace("mt-1 text-sm text-red-600","site-order-invoice-error"),encoding='utf8')
css=r/'public_html/assets/css/v2/components/no-tailwind-public.css'
with css.open('a',encoding='utf8') as f:f.write('\n.site-order-invoice-error{margin-top:4px;font-size:14px;color:#dc2626}\n')
print('ORDER_INVOICE_ERROR_STYLE_MIGRATED')
