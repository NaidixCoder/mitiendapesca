import urllib.request,urllib.error,urllib.parse,http.cookiejar,html.parser,time
B='http://127.0.0.1:18855'
class H(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.hidden={}
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=='input' and d.get('type')=='hidden' and d.get('name'):self.hidden[d['name']]=d.get('value','')
class NR(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,heads,url):return None
o=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NR())
def req(path,data=None):
 r=urllib.request.Request(B+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers={'User-Agent':'Synthetic QA'})
 try:q=o.open(r,timeout=12)
 except urllib.error.HTTPError as e:q=e
 return q.status,q.read().decode('utf8','replace'),q.headers.get('Location','')
tests=[('/phpinfo.php',404),('/tmp_session_dump.php',404),('/tmp_session_info.php',404),('/qa-404-private',404)]
for path,expected in tests:
 s,_,_=req(path);print('DIAGNOSTIC',path,s);assert s==expected
s,_,_=req('/cuenta');print('GUEST_ACCOUNT',s);assert s in (302,401,403)
s,_,_=req('/admin/products');print('GUEST_ADMIN',s);assert s in (302,401,403)
s,_,_=req('/carrito/agregar',{'product_id':'2','qty':'1'});print('POST_ADD_NO_CSRF',s);assert s in (400,403,419,422)
s,body,_=req('/login');h=H();h.feed(body);time.sleep(2.1)
s,_,loc=req('/login',{**h.hidden,'email':'qa-client-main@example.invalid','password':'QA-ONLY-2026-Test!'});assert s==302;print('CLIENT_LOGIN',s)
s,_,loc=req('/admin/products');print('CLIENT_ADMIN',s,loc);assert s in (302,401,403)
s,_,_=req('/cuenta');print('CLIENT_ACCOUNT',s);assert s==200
print('QA_SECURITY_HTTP_PASS')
