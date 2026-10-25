import urllib.request,urllib.parse,urllib.error,http.cookiejar,html.parser,time,re,json
BASE='http://127.0.0.1:18855'
class Hidden(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.values={}
 def handle_starttag(self,tag,attrs):
  a=dict(attrs)
  if tag=='input' and a.get('name') and a.get('type')=='hidden':self.values[a['name']]=a.get('value','')
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):return None
op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedirect())
def call(path,data=None,json_req=False):
 h={'User-Agent':'QA-Synthetic','Content-Type':'application/x-www-form-urlencoded'}
 if json_req:h.update({'Accept':'application/json','X-Requested-With':'XMLHttpRequest'})
 q=urllib.request.Request(BASE+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers=h)
 try:r=op.open(q,timeout=20)
 except urllib.error.HTTPError as e:r=e
 body=r.read().decode('utf-8','replace')
 return r.status,body,r.headers.get('Location','')
s,html,_=call('/login');h=Hidden();h.feed(html);time.sleep(2.1)
s,body,loc=call('/login',{**h.values,'email':'qa-client-main@example.invalid','password':'QA-ONLY-2026-Test!'})
print('LOGIN',s,'location',loc)
assert s==302 and '/cuenta' in loc
s,body,_=call('/producto?slug=qa-local-main-producto');h=Hidden();h.feed(body)
print('PRODUCT',s,'hidden_keys',sorted(h.values.keys()))
assert s==200 and '_csrf' in h.values
import_id=re.findall(r'name="product_id"[^>]*value="(\d+)"',body)
pid=h.values.get('product_id',import_id[0] if import_id else None)
assert pid,'Missing product_id'
csrf=h.values['_csrf']
s,body,_=call('/carrito/agregar',{'_csrf':csrf,'product_id':pid,'qty':'1','variant_id':'0'},True)
print('ADD',s,body[:250])
assert s==200 and json.loads(body)['ok']
s,body,_=call('/carrito');h=Hidden();h.feed(body)
keys=re.findall(r'name="qty\[([^\]]+)\]"',body)
print('CART',s,'qty_fields',keys,'has_product',('QA Producto disponible' in body))
assert keys
csrf=h.values['_csrf']
s,body,_=call('/carrito/actualizar',{'_csrf':csrf,'qty['+keys[0]+']':'2'},True)
print('UPDATE',s,body[:270])
assert s==200 and json.loads(body)['ok']
checkout_status,checkout_body,_=call('/checkout')
print('CHECKOUT_AUTHENTICATED',checkout_status,'len',len(checkout_body))
assert checkout_status==200
s,body,_=call('/carrito/quitar',{'_csrf':csrf,'product_id':pid,'variant_id':'0'},True)
print('REMOVE',s,body[:270])
assert s==200 and json.loads(body)['ok']
s,body,_=call('/carrito');print('CART_AFTER_REMOVE',s,'empty',('QA Producto disponible' not in body))
print('QA_CART_HTTP_DONE')
