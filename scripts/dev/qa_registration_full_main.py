import urllib.request,urllib.error,urllib.parse,http.cookiejar,html.parser,time,re,tempfile,pathlib,uuid,json
email='qa-reg-'+uuid.uuid4().hex[:10]+'@example.invalid'
class H(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.d={}
 def handle_starttag(self,t,a):
  x=dict(a)
  if t=='input' and x.get('type')=='hidden' and x.get('name'):self.d[x['name']]=x.get('value','')
class NR(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,r,fp,code,msg,h,url):return None
op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NR())
base='http://127.0.0.1:18855'
def req(path,data=None):
 h={'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
 request=urllib.request.Request(base+path,data=urllib.parse.urlencode(data).encode() if data is not None else None,headers=h)
 try:q=op.open(request,timeout=15)
 except urllib.error.HTTPError as e:q=e
 return q.status,q.read().decode('utf8','replace'),q.headers.get('Location','')
s,html,_=req('/registro');assert s==200
h=H();h.feed(html);assert h.d.get('_csrf')
time.sleep(2.2)
data={**h.d,'name':'QA Registro Sintetico','email':email,'password':'QA-Register-Test-2026!','password_confirm':'QA-Register-Test-2026!','terms':'1','newsletter_optin':'0'}
s,body,_=req('/registro',data)
print('REGISTER',s,body[:240])
assert s==200 and json.loads(body).get('ok') is True
sink=pathlib.Path(tempfile.gettempdir())/'mi_pesca_qa_mail_sink_20261025.txt'
assert sink.exists(),'QA mail sink missing'
mail=sink.read_text(encoding='utf8',errors='replace')
section=mail.split('TO='+email+'\n')[-1].split('--END--')[0]
token=re.search(r'verificar-email\?token=([a-f0-9]{64})',section)
assert token,'Verification mail not captured'
s,_,loc=req('/verificar-email?token='+token.group(1))
print('VERIFY',s,loc)
assert s==302 and '/cuenta' in loc
s,_,_=req('/cuenta')
print('VERIFIED_ACCOUNT',s);assert s==200
s,_,loc=req('/logout',{'_csrf':h.d['_csrf']})
print('LOGOUT',s,loc);assert s==302
s,_,_=req('/cuenta')
print('AFTER_LOGOUT_ACCOUNT',s);assert s==401
sink.unlink(missing_ok=True)
print('QA_REGISTER_VERIFY_HTTP_PASS',email)
