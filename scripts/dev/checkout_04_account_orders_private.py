#!/usr/bin/env python3
"""Private-only HTTP account/admin negative and positive tests. Requires checkout private runner."""
import os, json, subprocess, time
import requests
from bs4 import BeautifulSoup

BASE='http://127.0.0.1:18897'
FIXTURE=r'C:\xampp\htdocs\mi-pesca\scripts\dev\checkout_http_real_fixture.php'
assert all(os.getenv(k)==v for k,v in {'MI_QA_DB_HOST':'127.0.0.1','MI_QA_DB_PORT':'18997','MI_QA_DB_NAME':'mi_checkout_http_private_qa'}.items()), 'REFUSED private DB identity'
assert len(os.getenv('MI_QA_DB_PASS',''))>=20 and len(os.getenv('MI_QA_HTTP_PASSWORD',''))>=20
count=0

def ck(cond,label):
    global count
    if not cond: raise AssertionError('FAIL '+label)
    count+=1
    print('PASS '+label,flush=True)

def snapshot():
    return json.loads(subprocess.check_output([r'C:\xampp\php\php.exe',FIXTURE,'account_snapshot'],text=True,timeout=20))

def login(email):
    s=requests.Session(); r=s.get(BASE+'/login',timeout=15)
    ck(r.status_code==200,'login GET '+email)
    form=BeautifulSoup(r.text,'html.parser').select_one('form.auth-login__form')
    assert form
    fields={x['name']:x.get('value','') for x in form.select('input[name]') if x.get('type')=='hidden'}
    csrf=fields.get('_csrf',fields.get('csrf',''))
    ck(bool(csrf),'real CSRF '+email)
    fields.update(email=email,password=os.environ['MI_QA_HTTP_PASSWORD'])
    time.sleep(2.2)
    ret=s.post(BASE+'/login',data=fields,allow_redirects=False,timeout=20)
    ck(ret.status_code==302 and bool(s.cookies.get('MIE2EPRIVATE')),'login POST '+email)
    return s,csrf

def get(s,path):return s.get(BASE+path,allow_redirects=False,timeout=20)
def post(s,path,body=None,csrf=None,asjson=False):
    headers={'Accept':'application/json'} if asjson else {}
    data=dict(body or {});data['_csrf']=csrf or ''
    return s.post(BASE+path,data=data,headers=headers,allow_redirects=False,timeout=25)

def post_json(s,path,body=None,csrf=None):
    return s.post(BASE+path,json=body or {},headers={'Accept':'application/json','X-CSRF-TOKEN':csrf or ''},allow_redirects=False,timeout=25)

baseline=snapshot()
ck(len(baseline['orders'])==1,'one synthetic order from previous concurrency suite')
order=int(baseline['orders'][0]['id'])
ck(baseline['orders'][0]['user_id']==197,'order owned by buyer 197')
customer,csrf=login('comprador-checkout@example.test')
ck(get(customer,'/cuenta/pedidos').status_code==200,'customer orders GET 200')
ck(get(customer,f'/cuenta/pedidos/ver?id={order}').status_code==200,'owner order detail GET 200')
ck(get(customer,'/cuenta/direcciones').status_code==200,'customer addresses GET 200')
ck(get(customer,'/cuenta/direcciones/nueva').status_code==200,'customer address create form GET 200')
ck(get(customer,'/cuenta/direcciones/editar?id=198').status_code==302,'foreign address edit GET blocked')
ck(get(customer,'/admin/orders').status_code==403,'customer admin orders denied 403')
ck(post_json(customer,'/admin/orders/status',{'id':order,'status':'cancelled'},csrf).status_code==403,'customer admin mutation denied 403')
ck(post(customer,'/cuenta/direcciones/crear',{'full_name':'QA'},'bad',True).status_code==419,'address creation invalid CSRF 419')
invalid=post(customer,'/cuenta/direcciones/crear',{'full_name':'QA'},csrf,True)
ck(invalid.status_code==422 and invalid.json().get('ok') is False,'address form validation 422')
valid={'label':'QA secundaria','full_name':'Comprador QA','phone':'3465123456','street':'Calle Privada','number':'700','city':'Firmat','state':'Santa Fe','zip':'2630'}
foreign=post(customer,'/cuenta/direcciones/guardar',dict(valid,id=198),csrf,True)
ck(foreign.status_code==422 and foreign.json().get('ok') is False,'foreign update returns 422 after AddressRepository fix')
ck(any(a['id']==198 and a['street']=='Calle B' for a in snapshot()['addresses']),'foreign address cannot be modified in SQL')
ck(post(customer,'/cuenta/direcciones/principal',{'id':198},csrf).status_code==302,'foreign default address request bounded')
ck(any(a['id']==197 and a['is_default']==1 for a in snapshot()['addresses']),'foreign default attempt retains own default')
ck(post(customer,'/cuenta/direcciones/eliminar',{'id':198},csrf).status_code==302,'foreign address delete request bounded')
ck(any(a['id']==198 and a['user_id']==198 for a in snapshot()['addresses']),'foreign address remained after delete')
created=post(customer,'/cuenta/direcciones/crear',valid,csrf,True)
ck(created.status_code==200 and created.json().get('ok') is True,'create valid address HTTP status='+str(created.status_code)+' body='+created.text[:450])
address=int(created.json()['id'])
ck(any(a['id']==address and a['user_id']==197 for a in snapshot()['addresses']),'created address persisted for owner')
updated=post(customer,'/cuenta/direcciones/guardar',dict(valid,id=address,street='Calle Modificada'),csrf,True)
ck(updated.status_code==200 and updated.json().get('ok') is True,'update own address HTTP')
ck(any(a['id']==address and a['street']=='Calle Modificada' for a in snapshot()['addresses']),'address update persisted')
ck(post(customer,'/cuenta/direcciones/principal',{'id':address},csrf).status_code==302,'set default HTTP')
ck(any(a['id']==address and a['is_default']==1 for a in snapshot()['addresses']),'default address persisted')
ck(post(customer,'/cuenta/direcciones/eliminar',{'id':address},csrf).status_code==302,'delete own address HTTP')
ck(not any(a['id']==address for a in snapshot()['addresses']),'own address deletion persisted')
ck(post(customer,'/cuenta/pedidos/comprobante/subir',{'order_id':order},'bad').status_code==419,'receipt upload invalid CSRF')
ck(get(customer,f'/cuenta/pedidos/comprobante/ver?order_id={order}&file=nonexistent').status_code in (302,404),'missing receipt safe response')
from io import BytesIO
from urllib.parse import quote
from pathlib import Path
receipt_dir=Path(os.environ['TEMP'])/'mi_checkout_http_private_qa'/'site'/'storage'/'receipts'/f'order_{order}'
def upload(s,token,oid,filename,data):
    return s.post(BASE+'/cuenta/pedidos/comprobante/subir',data={'order_id':oid,'_csrf':token},files={'receipt':(filename,BytesIO(data),'application/octet-stream')},allow_redirects=False,timeout=25)
ck(upload(customer,'bad',order,'valid.pdf',b'%PDF-1.4\n%%EOF\n').status_code==419,'receipt multipart CSRF rejected')
ck(upload(customer,csrf,order,'payload.php',b'<?php echo 1; ?>').status_code==302,'malicious PHP MIME upload bounded')
ck(not receipt_dir.exists() or not list(receipt_dir.glob('*')),'PHP MIME upload leaves no file')
ck(upload(customer,csrf,order,'empty.png',b'').status_code==302,'empty receipt upload bounded')
ck(not receipt_dir.exists() or not list(receipt_dir.glob('*')),'empty upload leaves no file')
ck(upload(customer,csrf,order,'oversize.pdf',b'A'*(6*1024*1024+1)).status_code in (302,413),'oversize receipt rejected without exception')
ck(not receipt_dir.exists() or not list(receipt_dir.glob('*')),'oversize upload leaves no file')
pdf=b'%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n%%EOF\n'
positive=upload(customer,csrf,order,'comprobante-qa.pdf',pdf)
ck(positive.status_code==302 and 'comprobante=1' in positive.headers.get('Location',''),'valid PDF upload acknowledged')
files=sorted(receipt_dir.glob('*'))
ck(len(files)==1 and files[0].suffix=='.pdf' and files[0].read_bytes()==pdf,'valid PDF stored only under isolated order folder')
receipt_name=quote(files[0].name)
for suffix in ('ver','descargar'):
    r=get(customer,f'/cuenta/pedidos/comprobante/{suffix}?order_id={order}&file={receipt_name}')
    ck(r.status_code==200 and r.content==pdf,'owner receipt '+suffix+' returns exact PDF')
    ck('application/pdf' in r.headers.get('Content-Type',''),'owner receipt '+suffix+' correct MIME')
ck(get(customer,f'/cuenta/pedidos/comprobante/ver?order_id={order}&file=../../not-a-real-file').status_code==302,'path traversal attempt remains bounded')
second,csrf2=login('comprador-b-checkout@example.test')
ck(get(second,f'/cuenta/pedidos/ver?id={order}').status_code==302,'foreign order detail denied')
ck(get(second,'/cuenta/pedidos').status_code==200,'second buyer order listing works')
ck(get(second,'/cuenta/direcciones/editar?id=197').status_code==302,'second buyer foreign address denied')
ck(upload(second,csrf2,order,'foreign.pdf',pdf).status_code==302,'foreign receipt upload blocked')
ck(len(list(receipt_dir.glob('*')))==1,'foreign upload preserves owner receipt only')
for suffix in ('ver','descargar'):
    r=get(second,f'/cuenta/pedidos/comprobante/{suffix}?order_id={order}&file={receipt_name}')
    ck(r.status_code==302 and r.content!=pdf,'foreign receipt '+suffix+' denied')
ck(post(second,'/cuenta/pedidos/repetir',{'order_id':order},csrf2).status_code==302,'foreign repeat order request bounded')
ck(snapshot()['stock']==3 and len(snapshot()['orders'])==1,'foreign repeat does not create order or change stock')
admin,admincsrf=login('admin-checkout@example.test')
ck(get(admin,'/admin/orders').status_code==200,'admin order listing GET')
ck(get(admin,f'/admin/orders/show?id={order}').status_code==200,'admin order detail GET')
ck(post_json(admin,'/admin/orders/status',{'id':order,'status':'cancelled'},'bad').status_code==419,'admin status invalid CSRF')
invalid_status=post_json(admin,'/admin/orders/status',{'id':order,'status':'__bad__'},admincsrf)
ck(invalid_status.status_code==422 and invalid_status.json().get('ok') is False,'admin invalid status rejected 422')
ck(post(admin,'/admin/orders/mark-paid',{'id':order},'bad',True).status_code==419,'admin mark-paid invalid CSRF')
ck(post(admin,'/admin/orders/shipping',{'order_id':order},'bad',True).status_code==419,'admin shipping invalid CSRF')
ck(get(admin,f'/admin/orders/comprobante/ver?order_id={order}&file=nonexistent').status_code==302,'admin missing receipt safe response')
ck(snapshot()['orders'][0]['status']=='pending_payment','invalid mutations retained pending_payment')
ck(snapshot()['stock']==3,'before cancellation stock reserved 5 to 3')
cancel=post_json(admin,'/admin/orders/status',{'id':order,'status':'cancelled','skip_arca_auto':1,'skip_arca_nc':1},admincsrf)
ck(cancel.status_code==200 and cancel.json().get('ok') is True,'admin cancel pending transfer HTTP without fiscal integrations')
ck(snapshot()['orders'][0]['status']=='cancelled','cancelled order status persisted')
ck(snapshot()['stock']==5,'cancellation replenishes reserved stock 3 to 5')
repeat=post_json(admin,'/admin/orders/status',{'id':order,'status':'cancelled','skip_arca_auto':1,'skip_arca_nc':1},admincsrf)
ck(repeat.status_code==200 and repeat.json().get('ok') is True,'repeat cancellation idempotent HTTP')
ck(snapshot()['stock']==5 and len(snapshot()['orders'])==1,'replay does not double-restock or create orders')
invalid_transition=post_json(admin,'/admin/orders/status',{'id':order,'status':'shipped','skip_arca_auto':1,'skip_arca_nc':1},admincsrf)
ck(invalid_transition.status_code==422,'invalid transition cancelled to shipped denied')
ck(snapshot()['orders'][0]['status']=='cancelled','invalid transition leaves status unchanged')
from pathlib import Path
session_path=Path(os.environ.get('TEMP',r'C:\\Windows\\Temp'))/'mi_checkout_http_private_qa'/'visual_sessions.json'
if not session_path.parent.is_dir(): raise RuntimeError('REFUSED: private session folder missing')
session_path.write_text(json.dumps({'customer':customer.cookies.get('MIE2EPRIVATE'),'admin':admin.cookies.get('MIE2EPRIVATE')}),encoding='utf-8')
print('CHECKOUT_04_ACCOUNT_ADMIN_HTTP_PASS',count,flush=True)
