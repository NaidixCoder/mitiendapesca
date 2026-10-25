import urllib.request,urllib.parse,urllib.error,http.cookiejar,html.parser,time,json
base='http://127.0.0.1:18855'
class Inputs(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.fields={}
 def handle_starttag(self,t,a):
  x=dict(a)
  if t=='input' and x.get('type')=='hidden' and x.get('name'):self.fields[x['name']]=x.get('value','')
op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
def call(path,form=None):
 h={'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
 req=urllib.request.Request(base+path,data=urllib.parse.urlencode(form).encode() if form is not None else None,headers=h)
 try:r=op.open(req,timeout=15)
 except urllib.error.HTTPError as e:r=e
 return r.status,r.read().decode('utf-8','replace')
s,body=call('/registro');print('GET_REGISTER',s);assert s==200
inp=Inputs();inp.feed(body);assert inp.fields.get('_csrf')
s,body=call('/registro',{'_csrf':'wrong','email':'qa-client-main@example.invalid'})
print('REGISTER_CSRF_INVALID',s);assert s==419
time.sleep(2.2)
s,body=call('/registro',{**inp.fields,'name':'','email':'invalid','password':'short','password_confirm':'different','newsletter_optin':'0'})
print('REGISTER_FIELDS_INVALID',s,body[:190]);assert s==422
print('REGISTRATION_NEGATIVE_HTTP_PASS')
