import urllib.request,urllib.parse,urllib.error,http.cookiejar,html.parser,time,csv,io
B='http://127.0.0.1:18855'
class Fields(html.parser.HTMLParser):
 def __init__(self):super().__init__();self.data={}
 def handle_starttag(self,t,a):
  d=dict(a)
  if t=='input' and d.get('type')=='hidden' and d.get('name'):self.data[d['name']]=d.get('value','')
class NR(urllib.request.HTTPRedirectHandler):
 def redirect_request(self,r,fp,code,msg,headers,url):return None
op=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()),NR())
def get(path,form=None):
 r=urllib.request.Request(B+path,data=urllib.parse.urlencode(form).encode() if form is not None else None)
 try:h=op.open(r,timeout=18)
 except urllib.error.HTTPError as e:h=e
 return h.status,h.read(),h.headers.get('Content-Type','')
s,body,_=get('/login');f=Fields();f.feed(body.decode('utf8','replace'));time.sleep(2.1)
s,_,_=get('/login',{**f.data,'email':'qa-admin-main@example.invalid','password':'QA-ONLY-2026-Test!'});assert s==302
s,t,ct=get('/admin/imports/template')
print('TEMPLATE',s,'bytes',len(t),ct);assert s==200 and 'text/csv' in ct
s,e,ct=get('/admin/imports/export')
print('EXPORT',s,'bytes',len(e),ct);assert s==200 and 'text/csv' in ct
template=list(csv.reader(io.StringIO(t.decode('utf-8-sig')),delimiter=';'))
export=list(csv.reader(io.StringIO(e.decode('utf-8-sig')),delimiter=';'))
assert template[0]==export[0] and len(template[0])>=30
assert len(export)>=4,'Expected header and QA products'
joined=' '.join(' '.join(x) for x in export)
assert 'QA Producto disponible' in joined,'Missing synthetic product'
print('QA_EXPORT_HTTP_CSV_PASS','columns',len(export[0]),'rows',len(export)-1)
