import urllib.request,urllib.parse,urllib.error,http.cookiejar,html.parser,subprocess,json,time
php=r'C:\xampp\php\php.exe';fixture=r'C:\xampp\htdocs\mi-pesca\scripts\dev\qa_fiscal_fixture_main.php'
class Hidden(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.h={}
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=='input' and d.get('type')=='hidden' and d.get('name'):self.h[d['name']]=d.get('value','')
class NoRedir(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,r,fp,code,msg,heads,url):return None
def authenticated(email):
 op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NoRedir())
 def get(path,data=None):
  req=urllib.request.Request('http://127.0.0.1:18855'+path,data=urllib.parse.urlencode(data).encode() if data is not None else None)
  try:r=op.open(req,timeout=12)
  except urllib.error.HTTPError as e:r=e
  return r.status,r.read().decode('utf8','replace')
 s,h=get('/login');f=Hidden();f.feed(h);time.sleep(2.1)
 s,_=get('/login',{**f.h,'email':email,'password':'QA-ONLY-2026-Test!'})
 assert s==302
 return get
meta=json.loads(subprocess.check_output([php,fixture,'seed'],text=True))
try:
 admin=authenticated('qa-admin-main@example.invalid')
 user=authenticated('qa-client-main@example.invalid')
 for k in ('invoice','credit_note'):
  url='/admin/facturacion/comprobantes/show?id='+str(meta[k])
  status,body=admin(url)
  print('ADMIN_FISCAL',k,status,'size',len(body))
  assert status==200
  status,_=user(url);print('CLIENT_FISCAL_DENIED',k,status);assert status==403
 status,body=admin('/admin/facturacion/comprobantes?order_id='+str(meta['order']))
 print('FISCAL_LIST',status);assert status==200
 print('QA_FISCAL_HTTP_SQL_PASS')
finally:
 print(subprocess.check_output([php,fixture,'cleanup',str(meta['order'])],text=True).strip())
