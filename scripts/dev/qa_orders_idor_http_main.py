import subprocess,urllib.request,urllib.error,urllib.parse,http.cookiejar,html.parser,time
php=r'C:\xampp\php\php.exe';fixture=r'C:\xampp\htdocs\mi-pesca\scripts\dev\qa_orders_idor_fixture_main.php'
class Form(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.h={}
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=='input' and d.get('type')=='hidden' and d.get('name'):self.h[d['name']]=d.get('value','')
class NR(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,r,fp,c,m,h,u):return None
def client(email):
 o=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NR())
 def call(path,form=None):
  r=urllib.request.Request('http://127.0.0.1:18855'+path,data=urllib.parse.urlencode(form).encode() if form is not None else None)
  try:s=o.open(r,timeout=10)
  except urllib.error.HTTPError as e:s=e
  return s.status,s.read().decode('utf8','replace'),s.headers.get('Location','')
 status,body,_=call('/login');f=Form();f.feed(body);time.sleep(2.1)
 status,_,_=call('/login',{**f.h,'email':email,'password':'QA-ONLY-2026-Test!'})
 assert status==302
 return call
oid=int(subprocess.check_output([php,fixture,'seed'],text=True).strip())
try:
 mine=client('qa-client-main@example.invalid')
 other=client('qa-wholesale-main@example.invalid')
 own_status,own_body,own_loc=mine('/cuenta/pedidos/ver?id='+str(oid))
 other_status,other_body,other_loc=other('/cuenta/pedidos/ver?id='+str(oid))
 print('OWN_ORDER',own_status,'OTHER_ORDER',other_status,'OTHER_REDIRECT',other_loc)
 assert own_status==200 and other_status==302 and '/cuenta/pedidos' in other_loc
 print('QA_ORDERS_IDOR_HTTP_PASS')
finally:
 print(subprocess.check_output([php,fixture,'delete',str(oid)],text=True).strip())
