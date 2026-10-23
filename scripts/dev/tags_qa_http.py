import os,requests,re,sys,time,subprocess,json
from bs4 import BeautifulSoup
BASE='http://127.0.0.1:18898'
PYTHON_CHECKS=0
def ck(test,name):
 global PYTHON_CHECKS
 if not test:raise AssertionError(name)
 PYTHON_CHECKS+=1;print('PASS',name,flush=True)
def csrf(html):
 soup=BeautifulSoup(html,'html.parser')
 inp=soup.select_one('input[name="_csrf"]')
 assert inp,'missing real csrf field'
 return inp.get('value','')
def get(s,p):return s.get(BASE+p,timeout=12,allow_redirects=False)
def post(s,p,data):return s.post(BASE+p,data=data,timeout=15,allow_redirects=False)
def login(role):
 s=requests.Session();r=get(s,'/login')
 ck(r.status_code==200,f'GET login {role}')
 token=csrf(r.text)
 ck(post(s,'/login',{'_csrf':'bad','email':role+'-tags@example.test','password':os.environ['TAGS_QA_LOGIN_PASSWORD']}).status_code==419,'login rejects invalid CSRF '+role)
 r=get(s,'/login');token=csrf(r.text)
 time.sleep(2.3)
 p=post(s,'/login',{'_csrf':token,'email':role+'-tags@example.test','password':os.environ['TAGS_QA_LOGIN_PASSWORD']})
 print('login diagnostic',role,p.status_code,p.headers.get('Location',''),p.text[:120],flush=True)
 ck(p.status_code==302,'real login '+role)
 return s
a=requests.Session()
r=get(a,'/admin/catalog-tags')
ck(r.status_code in (302,401,403),'anonymous GET tags denied')
r=post(a,'/admin/catalog-tags/group/save',{'name':'Anon'})
ck(r.status_code in (302,401,403),'anonymous POST denied')
s=login('admin')
r=get(s,'/admin/catalog-tags');ck(r.status_code==200,'admin GET 200')
links=[x.get('href') for x in BeautifulSoup(r.text,'html.parser').select('link[rel=stylesheet]')]
print('CSS LINKS',links[:12],flush=True)
for href in links[:4]:
 try:
  rr=s.get(requests.compat.urljoin(BASE,href),timeout=10);print('CSS GET',rr.status_code,rr.headers.get('Content-Type',''),href,flush=True)
 except Exception as error:print('CSS GET ERROR',str(error)[:100],flush=True)
ck('Etiquetas del catálogo' in r.text and 'av3-catalog-tags' in r.text,'admin V3 page')
token=csrf(r.text);ck(len(token)>20,'real admin session csrf field')
data={'_csrf':token,'name':'Modalidad','slug':'modalidad','sort':'0','is_active':'1'}
ck(post(s,'/admin/catalog-tags/group/save',{**data,'_csrf':'invalid'}).status_code==419,'admin group CSRF reject')
ck(post(s,'/admin/catalog-tags/group/save',data).status_code==302,'create group HTTP')
r=get(s,'/admin/catalog-tags');ck(r.status_code==200 and 'modalidad' in r.text,'group persisted/rendered')
ck(post(s,'/admin/catalog-tags/group/save',data).status_code==302,'duplicate group POST handled')
r=get(s,'/admin/catalog-tags');ck('No se pudo guardar el grupo' in r.text,'duplicate group reported')
ck(post(s,'/admin/catalog-tags/tag/save',{'_csrf':token,'name':'Spinning','slug':'spinning','group_id':'1','is_active':'1','sort':'0'}).status_code==302,'create tag HTTP')
r=get(s,'/admin/catalog-tags');ck('spinning' in r.text,'tag persisted/rendered')
ck(post(s,'/admin/catalog-tags/tag/save',{'_csrf':token,'name':'Spinning','slug':'spinning','group_id':'1','is_active':'1','sort':'0'}).status_code==302,'duplicate tag handled')
r=get(s,'/admin/catalog-tags');ck('No se pudo guardar la etiqueta' in r.text,'duplicate tag reported')
ck(post(s,'/admin/catalog-tags/tag/save',{'_csrf':token,'name':'Invalid','slug':'invalid','group_id':'999','is_active':'1','sort':'0'}).status_code==302,'foreign group error HTTP handled')
r=get(s,'/admin/catalog-tags');ck('No se pudo guardar la etiqueta' in r.text,'foreign group error visible')
ck(post(s,'/admin/catalog-tags/group/save',{'_csrf':token,'id':'1','name':'Modalidad Editada','slug':'modalidad-editada','sort':'2','is_active':'0'}).status_code==302,'edit group HTTP')
r=get(s,'/admin/catalog-tags?group_id=1');ck('modalidad-editada' in r.text and 'Modalidad Editada' in r.text,'group edit persisted')
ck(post(s,'/admin/catalog-tags/tag/save',{'_csrf':token,'id':'1','group_id':'1','name':'Spinning Editado','slug':'spinning-editado','sort':'2','is_active':'1'}).status_code==302,'edit tag HTTP')
r=get(s,'/admin/catalog-tags?tag_id=1');ck('spinning-editado' in r.text,'tag edit persisted')
ck(post(s,'/admin/catalog-tags/tag/save',{'_csrf':token,'id':'999','group_id':'1','name':'Unknown','slug':'unknown','sort':'0','is_active':'1'}).status_code==302,'edit unknown ID handled')
r=get(s,'/admin/catalog-tags');ck('No se pudo guardar la etiqueta' in r.text,'unknown ID reported')
cookie=s.cookies.get('MITAGSQA')
ck(bool(cookie),'admin session cookie available to real browser')
env=os.environ.copy();env.update({'TAGS_QA_URL':BASE+'/admin/catalog-tags','TAGS_QA_COOKIE_NAME':'MITAGSQA','TAGS_QA_COOKIE_VALUE':cookie})
p=subprocess.run(['python',r'C:\xampp\htdocs\mi-pesca\scripts\dev\tags_cdp_responsive.py'],env=env,capture_output=True,text=True,timeout=35)
print(p.stdout,flush=True)
if p.returncode:print(p.stderr,flush=True)
ck(p.returncode==0 and 'TEARDOWN True' in p.stdout,'real browser Admin V3 five viewport sizes')
c=login('cliente')
ck(get(c,'/admin/catalog-tags').status_code==403,'customer GET denied')
ck(post(c,'/admin/catalog-tags/group/save',data).status_code==403,'customer POST denied')
print('AUTHENTIC_HTTP_MARIADB_TAGS_PASS',PYTHON_CHECKS,flush=True)
