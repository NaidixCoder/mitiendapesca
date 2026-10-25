import urllib.request, urllib.parse, urllib.error, http.cookiejar, html.parser, time
BASE='http://127.0.0.1:18855'
class Inputs(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.data={}
 def handle_starttag(self,tag,attrs):
  if tag!='input':return
  a=dict(attrs);name=a.get('name')
  if name and (a.get('type','').lower()=='hidden'):self.data[name]=a.get('value','')
def request(opener,path,form=None):
 data=urllib.parse.urlencode(form).encode() if form is not None else None
 req=urllib.request.Request(BASE+path,data=data,headers={'User-Agent':'MiPesca-Synthetic-E2E','Content-Type':'application/x-www-form-urlencoded'})
 try:
  with opener.open(req,timeout=15) as res:return res.status,res.url,res.read().decode('utf-8','replace')
 except urllib.error.HTTPError as e:return e.code,e.url,e.read().decode('utf-8','replace')
class NoRedirect(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,req,fp,code,msg,headers,newurl):
  print('REDIRECT_LOCATION',headers.get('Location',''))
  return None
jar=http.cookiejar.CookieJar();opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar),NoRedirect())
s,u,html=request(opener,'/login')
inputs=Inputs();inputs.feed(html)
print('GET_LOGIN',s,'hidden_keys',sorted(inputs.data.keys()))
if s!=200:raise SystemExit('FAIL GET login')
form={**inputs.data,'email':'qa-client-main@example.invalid','password':'QA-ONLY-2026-Test!'}
time.sleep(2.2)
s,u,html=request(opener,'/login',form)
print('POST_LOGIN',s,'url',u,'authenticated_text',('Cerrar sesi' in html or 'Mi cuenta' in html))
s2,u2,body=request(opener,'/cuenta')
print('GET_ACCOUNT',s2,'url',u2,'len',len(body))
if s2!=200 or '/login' in u2:raise SystemExit('FAIL AUTHENTICATED ACCOUNT')
print('QA_HTTP_AUTH_PASS')
