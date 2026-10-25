/** Headless Edge/CDP responsive probe. Only under the isolated checkout QA runner. */
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import {spawn, execFileSync} from 'node:child_process';

const base='http://127.0.0.1:18897', port=18998;
const folder=path.join(os.tmpdir(),'mi_checkout_http_private_qa');
const profile=path.join(folder,'edge_profile');
const evidence=path.join('C:\\xampp\\htdocs\\mi-pesca\\docs\\evidencias','preprod_04_responsive_'+new Date().toISOString().replace(/[:.]/g,'-'));
const browserExe='C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
if(process.env.MI_QA_DB_HOST!=='127.0.0.1'||process.env.MI_QA_DB_PORT!=='18997'||process.env.MI_QA_DB_NAME!=='mi_checkout_http_private_qa'||!fs.existsSync(folder)||!fs.existsSync(browserExe))throw Error('REFUSED: invalid private context');
if(fs.existsSync(profile))throw Error('REFUSED: browser profile already exists');
if(fs.existsSync(evidence))throw Error('REFUSED: evidence folder already exists; protect earlier captures');
fs.mkdirSync(evidence,{recursive:true});

const sleep=ms=>new Promise(r=>setTimeout(r,ms));
const cookie=(r, fallback='')=>{
  const vals=r.headers.getSetCookie?.() ?? [];
  const match=vals.find(x=>x.startsWith('MIE2EPRIVATE='));
  return match ? match.split(';')[0].split('=')[1] : fallback;
};
async function authenticate(email){
  const page=await fetch(base+'/login',{redirect:'manual'});
  if(page.status!==200)throw Error('login GET failed '+page.status);
  const initial=cookie(page);
  const html=await page.text();
  const token=html.match(/name=["']_csrf["'][^>]*value=["']([^"']+)/)?.[1]
     ?? html.match(/value=["']([^"']+)["'][^>]*name=["']_csrf["']/)?.[1];
  if(!initial||!token)throw Error('real login cookie or CSRF missing');
  const params=new URLSearchParams({email,password:process.env.MI_QA_HTTP_PASSWORD,_csrf:token});
  const logged=await fetch(base+'/login',{method:'POST',redirect:'manual',headers:{'Content-Type':'application/x-www-form-urlencoded','Cookie':'MIE2EPRIVATE='+initial},body:params});
  if(logged.status!==302)throw Error('login failed '+email+' '+logged.status);
  return cookie(logged,initial);
}

let browser, ws, nextId=0;
const pending=new Map();
function send(method,params={}){
  return new Promise((resolve,reject)=>{
    const id=++nextId; pending.set(id,{resolve,reject});
    ws.send(JSON.stringify({id,method,params}));
    setTimeout(()=>{if(pending.has(id)){pending.delete(id);reject(Error('CDP timeout '+method));}},15000).unref();
  });
}
async function startBrowser(){
  let already=false;
  try{const r=await fetch('http://127.0.0.1:'+port+'/json/version');already=r.ok;}catch{}
  if(already)throw Error('REFUSED: debug port already occupied');
  browser=spawn(browserExe,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--disable-extensions','--disable-background-networking','--remote-allow-origins=*','--remote-debugging-address=127.0.0.1','--remote-debugging-port='+port,'--user-data-dir='+profile,'about:blank'],{stdio:'ignore',windowsHide:true});
  let pageUrl;
  for(let i=0;i<40;i++){
    if(browser.exitCode!==null)throw Error('Edge stopped before debugging available');
    try{
      const pages=await (await fetch('http://127.0.0.1:'+port+'/json/list')).json();
      pageUrl=pages.find(x=>x.type==='page')?.webSocketDebuggerUrl;
      if(pageUrl)break;
    }catch{}
    await sleep(200);
  }
  if(!pageUrl)throw Error('CDP page endpoint not reachable');
  ws=new WebSocket(pageUrl);
  await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
  ws.addEventListener('message',e=>{
    try{const d=JSON.parse(e.data);if(d.id&&pending.has(d.id)){const p=pending.get(d.id);pending.delete(d.id);d.error?p.reject(Error(JSON.stringify(d.error))):p.resolve(d.result);}}catch{}
  });
  await send('Page.enable');await send('Runtime.enable');await send('Network.enable');
}
async function useSession(session){
  const result=await send('Network.setCookie',{name:'MIE2EPRIVATE',value:session,url:base+'/',path:'/',httpOnly:true});
  if(result.success===false)throw Error('CDP cookie rejected');
}
const widths=[363,390,768,1024,1600], reports=[];
async function capture(name,url){
  for(const width of widths){
    await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width<=390});
    await send('Page.navigate',{url:base+url});
    await sleep(1400);
    const obj=await send('Runtime.evaluate',{expression:'JSON.stringify({path:location.pathname, title:document.title, ready:document.readyState, viewport:window.innerWidth, htmlScroll:document.documentElement.scrollWidth,bodyScroll:document.body?.scrollWidth||0, h:document.documentElement.scrollHeight, styleSheetCount:document.styleSheets.length, cssLinks:[...document.querySelectorAll("link[rel=stylesheet]")].slice(0,5).map(x=>x.href), bodyFont:document.body?getComputedStyle(document.body).fontFamily:"", loadedRules:[...document.styleSheets].reduce((n,s)=>{try{return n+s.cssRules.length}catch{return n}},0)})',returnByValue:true});
    if(typeof obj?.result?.value!=='string')throw Error('CDP metrics evaluation failed: '+JSON.stringify(obj).slice(0,900));
    const item=JSON.parse(obj.result.value);
    const overflow=Math.max(item.htmlScroll,item.bodyScroll,item.viewport)>width+2;
    const cssMissing=item.loadedRules===0;
    const wrongPage=(name.startsWith('admin')&&!item.path.startsWith('/admin/'))||(name.startsWith('account')&&!item.path.startsWith('/cuenta/'))||(name==='checkout'&&!item.path.startsWith('/checkout'));
    const png=await send('Page.captureScreenshot',{format:'png',captureBeyondViewport:false});
    const filename=`${name}_${width}.png`;
    fs.writeFileSync(path.join(evidence,filename),Buffer.from(png.data,'base64'));
    const report={name,width,path:item.path,viewport:item.viewport,documentWidth:item.htmlScroll,bodyWidth:item.bodyScroll,overflow,wrongPage,cssMissing,styles:item.styleSheetCount,loadedRules:item.loadedRules,cssLinks:item.cssLinks,bodyFont:item.bodyFont,filename};
    reports.push(report);
    console.log((!overflow&&!wrongPage&&!cssMissing?'PASS':'FAIL')+' responsive '+name+' '+width+' actual='+item.viewport+' scroll='+item.htmlScroll+' rules='+item.loadedRules+' css1='+(item.cssLinks[0]??'none')+' path='+item.path);
  }
}
try{
  await startBrowser();
  const syntheticSessions=JSON.parse(fs.readFileSync(path.join(folder,'visual_sessions.json'),'utf8'));
  const customer=syntheticSessions.customer, admin=syntheticSessions.admin;
  if(!customer||!admin)throw Error('REFUSED: synthetic sessions missing');
  await useSession(customer);
  await capture('account_addresses','/cuenta/direcciones');
  await capture('account_orders','/cuenta/pedidos');
  await useSession(admin);
  await capture('admin_orders','/admin/orders');
  // The account E2E just cancelled an order. Refresh the disposable cart for checkout visuals.
  execFileSync('C:\\xampp\\php\\php.exe',['C:\\xampp\\htdocs\\mi-pesca\\scripts\\dev\\checkout_http_real_fixture.php','race_reset'],{stdio:'pipe',env:process.env});
  await useSession(customer);
  await capture('checkout','/checkout');
  fs.writeFileSync(path.join(evidence,'summary.json'),JSON.stringify(reports,null,2));
  const failures=reports.filter(x=>x.overflow||x.wrongPage||x.cssMissing);
  console.log('CHECKOUT_04_BROWSER_RESPONSIVE '+(reports.length-failures.length)+'/'+reports.length+' failures='+failures.length);
  if(failures.length)process.exitCode=1;
}catch(err){console.error('CHECKOUT_04_BROWSER_ERROR',err?.stack||err);process.exitCode=1;}
finally{
  if(ws){try{ws.close();}catch{}}
  if(browser){
    try{execFileSync('taskkill',['/PID',String(browser.pid),'/T','/F'],{stdio:'ignore',windowsHide:true,timeout:10000});}catch{}
  }
  await sleep(800);
  try{fs.rmSync(profile,{recursive:true,force:true,maxRetries:5,retryDelay:300});}catch(e){console.error('BROWSER_PROFILE_CLEANUP_REVIEW',e.message);process.exitCode=1;}
  console.log('CHECKOUT_04_BROWSER_TEARDOWN completed for PID '+(browser?.pid??'none'));
}
