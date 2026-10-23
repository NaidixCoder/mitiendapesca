import base64,json,os,random,socket,subprocess,tempfile,time,urllib.request,shutil,struct
from pathlib import Path
EDGE=r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
PORT=19223
profile=Path(tempfile.gettempdir())/'mitags_cdp_profile_20261023'
fixture=Path(tempfile.gettempdir())/'mitags_qa'/'fixture.html'
target=os.getenv('TAGS_QA_URL') or fixture.as_uri()
if not os.getenv('TAGS_QA_URL'): assert fixture.exists()
assert not profile.exists(), 'Existing profile, refuse'
class CDP:
 def __init__(self,url):
  from urllib.parse import urlparse
  u=urlparse(url);self.s=socket.create_connection((u.hostname,u.port),timeout=10)
  key=base64.b64encode(os.urandom(16)).decode()
  self.s.sendall((f'GET {u.path} HTTP/1.1\r\nHost: {u.hostname}:{u.port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n').encode())
  reply=b''
  while b'\r\n\r\n' not in reply: reply+=self.s.recv(4096)
  assert reply.startswith(b'HTTP/1.1 101 '),repr(reply[:500])
  self.buf=reply.split(b'\r\n\r\n',1)[1];self.i=0
 def recv_exact(self,n):
  while len(self.buf)<n:self.buf+=self.s.recv(65536)
  out,self.buf=self.buf[:n],self.buf[n:];return out
 def send(self,obj):
  data=json.dumps(obj).encode();n=len(data);mask=os.urandom(4)
  h=bytes([0x81,0x80|n]) if n<126 else (bytes([0x81,0x80|126])+struct.pack('!H',n) if n<65536 else bytes([0x81,0x80|127])+struct.pack('!Q',n))
  self.s.sendall(h+mask+bytes(v^mask[i%4] for i,v in enumerate(data)))
 def receive(self):
  h=self.recv_exact(2);n=h[1]&127
  if n==126:n=struct.unpack('!H',self.recv_exact(2))[0]
  if n==127:n=struct.unpack('!Q',self.recv_exact(8))[0]
  return json.loads(self.recv_exact(n))
 def call(self,method,params=None):
  self.i+=1;self.send({'id':self.i,'method':method,'params':params or {}})
  while True:
   obj=self.receive()
   if obj.get('id')==self.i:
    if 'error' in obj:raise RuntimeError(obj['error'])
    return obj.get('result',{})
def get_json(url):return json.load(urllib.request.urlopen(url,timeout=2))
proc=None
try:
 proc=subprocess.Popen([EDGE,'--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--disable-extensions','--remote-allow-origins=*',f'--remote-debugging-port={PORT}',f'--user-data-dir={profile}','about:blank'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,creationflags=subprocess.CREATE_NO_WINDOW)
 info=None
 for i in range(40):
  try:info=get_json(f'http://127.0.0.1:{PORT}/json/list');break
  except Exception:time.sleep(.15)
 assert info
 tab=next(x for x in info if x['type']=='page')
 c=CDP(tab['webSocketDebuggerUrl'])
 c.call('Page.enable');c.call('Runtime.enable');c.call('Network.enable')
 if os.getenv('TAGS_QA_URL'):
  name=os.getenv('TAGS_QA_COOKIE_NAME','');value=os.getenv('TAGS_QA_COOKIE_VALUE','')
  assert name and value, 'Real auth cookie missing'
  result=c.call('Network.setCookie',{'name':name,'value':value,'url':target});assert result.get('success',False),'Cookie rejected'
 c.call('Page.navigate',{'url':target});time.sleep(1)
 for w in [363,390,768,1024,1600]:
  c.call('Emulation.setDeviceMetricsOverride',{'width':w,'height':900,'deviceScaleFactor':1,'mobile':True if w<600 else False})
  time.sleep(.15)
  obj=c.call('Runtime.evaluate',{'expression':"(()=>{let e=document.documentElement,b=document.body;let t=document.querySelector('.av3-catalog-tags');return JSON.stringify({innerWidth:innerWidth,clientWidth:e.clientWidth,scrollWidth:e.scrollWidth,bodyScroll:b.scrollWidth,rootScroll:t.scrollWidth,rootClient:t.clientWidth,forms:document.querySelectorAll('form').length,links:document.querySelectorAll('a[href*=catalog-tags]').length,adminShell:!!document.querySelector('.av3-master-shell'),hasModule:!!t,hasFixtureText:document.body.textContent.includes('Modalidad Editada')})})()",'returnByValue':True})
  result=json.loads(obj['result']['value'])
  print(w,result,flush=True)
  if os.getenv('TAGS_QA_URL'):
   assert result['hasModule'] and result['adminShell'] and result['hasFixtureText'], 'Real authenticated Admin module not rendered'
   assert result['scrollWidth']<=result['clientWidth'], 'Horizontal page overflow'
  if w==390:
   shot=c.call('Page.captureScreenshot',{'format':'png','captureBeyondViewport':False});(Path(tempfile.gettempdir())/'mitags_qa'/('tags_real_390.png' if os.getenv('TAGS_QA_URL') else 'cdp_390.png')).write_bytes(base64.b64decode(shot['data']))
 c.s.close()
finally:
 if proc:
  try:proc.terminate();proc.wait(timeout=6)
  except Exception:proc.kill();proc.wait(timeout=6)
 if profile.exists():
  for i in range(12):
   try:shutil.rmtree(profile);break
   except Exception:time.sleep(.3)
 print('TEARDOWN',not profile.exists(),flush=True)
