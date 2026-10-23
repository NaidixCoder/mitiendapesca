import os,time,requests
from bs4 import BeautifulSoup
BASE='http://127.0.0.1:18898'
n=0
def ck(condition,label):
 global n
 if not condition:raise AssertionError(label)
 n+=1;print('PASS',label,flush=True)
s=requests.Session()
for label,params in [('public unfiltered',{}),('public tag facet',{'tag[qa-modalidad][]':'qa-spinning'}),('public absent tag',{'tag[qa-modalidad][]':'no-existe'})]:
 r=s.get(BASE+'/productos',params=params,timeout=20,allow_redirects=False)
 print('PUBLIC_DIAG',label,r.status_code,flush=True)
 ck(r.status_code==200,label+' HTTP 200')
 ck('Fatal error' not in r.text and 'PDOException' not in r.text,label+' without exposed exception')
a=requests.Session()
r=a.get(BASE+'/login',timeout=12);token=BeautifulSoup(r.text,'html.parser').select_one('input[name="_csrf"]').get('value')
time.sleep(2.2)
r=a.post(BASE+'/login',data={'_csrf':token,'email':'admin-tags@example.test','password':os.environ['TAGS_QA_LOGIN_PASSWORD']},timeout=15,allow_redirects=False)
ck(r.status_code==302,'admin login for product regression')
for path,label in [('/admin/products/edit?id=198','product form'),('/admin/products/variants?product_id=198','variants screen'),('/admin/products/variants.json?product_id=198','variants endpoint')]:
 r=a.get(BASE+path,timeout=18,allow_redirects=False)
 print('ADMIN_REG_DIAG',label,r.status_code,flush=True)
 ck(r.status_code==200,label+' HTTP 200')
 ck('Fatal error' not in r.text,'no fatal '+label)
print('TAGS_CONSUMER_HTTP_REGRESSION_PASS',n,flush=True)
