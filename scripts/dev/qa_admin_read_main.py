import urllib.request,urllib.parse,urllib.error,http.cookiejar,re,time,html.parser
class H(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.d={}
 def handle_starttag(self,t,a):
  z=dict(a)
  if t=='input' and z.get('type')=='hidden' and z.get('name'):self.d[z['name']]=z.get('value','')
class NR(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):return None
base='http://127.0.0.1:18855';op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NR())
def get(path,post=None):
 req=urllib.request.Request(base+path,data=urllib.parse.urlencode(post).encode() if post is not None else None)
 try:r=op.open(req,timeout=10)
 except urllib.error.HTTPError as e:r=e
 return r.status,r.read().decode('utf-8','replace'),r.headers.get('Location','')
s,b,_=get('/login');h=H();h.feed(b);time.sleep(2.1)
s,b,loc=get('/login',{**h.d,'email':'qa-admin-main@example.invalid','password':'QA-ONLY-2026-Test!'})
print('ADMIN_LOGIN',s,loc)
if s!=302:raise SystemExit('FAIL admin login')
for path in ['/admin','/admin/products','/admin/products/edit?id=2','/admin/settings','/admin/orders','/admin/proveedores','/admin/proveedores/new','/admin/imports','/admin/facturacion','/admin/facturacion/comprobantes','/admin/facturacion/compras','/admin/facturacion/libro-iva','/admin/facturacion/libro-iva-compras','/admin/facturacion/reportes','/admin/facturacion/reportes/resumen','/admin/facturacion/reportes/mensual','/admin/facturacion/reportes/saldo-iva','/admin/facturacion/reportes/pedidos-sin-factura','/admin/facturacion/cierres-mensuales','/admin/facturacion/alertas']:
 s,b,loc=get(path)
 print(path,s,'len',len(b),'loc',loc)
 if s!=200:raise SystemExit('FAIL admin route '+path)
print('QA_ADMIN_READ_PASS')
