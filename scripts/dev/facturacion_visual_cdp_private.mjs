import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import net from 'node:net';
import {spawn} from 'node:child_process';
import {pathToFileURL} from 'node:url';
const edge='C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const css='C:\\xampp\\htdocs\\mi-pesca\\public_html\\assets\\css\\admin-v3\\pages\\billing.css';
const dir=await fs.mkdtemp(path.join(os.tmpdir(),'fiscal_cdp_'));
let child,ws,next=0; const pending=new Map();
const sleep=ms=>new Promise(r=>setTimeout(r,ms));
function call(method,params={},sessionId) {
  let id=++next;
  return new Promise((resolve,reject)=>{
    const timeout=setTimeout(()=>{pending.delete(id);reject(new Error('timeout '+method));},10000);
    pending.set(id,msg=>{clearTimeout(timeout);msg.error?reject(new Error(msg.error.message)):resolve(msg.result);});
    ws.send(JSON.stringify({id,method,params,...(sessionId?{sessionId}:{})}));
  });
}
const html='<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="'+pathToFileURL(path.join(path.dirname(css),'..','tokens.css')).href+'"><link rel="stylesheet" href="'+pathToFileURL(css).href+'"></head>'+
'<body style="margin:0;padding:12px;background:#f6f8fa" data-page="admin-facturacion-control"><main class="av3-fiscal-page"><section class="fisc-wrap fisc-control">'+
'<header class="fisc-head"><h1 class="fisc-title">Control fiscal</h1><div class="fisc-head-actions"><button class="btn btn-primary">Cerrar mes</button><button class="btn">Comprobantes</button></div></header>'+
'<div class="fisc-grid-main"><div class="fisc-col"><section class="fisc-card"><div class="fisc-card-head"><h2>Comprobantes</h2></div><div style="padding:14px">'+
'<form class="av3-grid"><label>Desde<input type="date"></label><label>Hasta<input type="date"></label><label>Buscar<input type="search"></label><button class="btn btn-primary">Filtrar</button></form>'+
'<div class="av3-overflow-auto"><table><thead><tr><th>ID</th><th>Comprobante</th><th>Estado</th><th>Importe</th><th>Acción</th></tr></thead><tbody><tr><td>100</td><td>00001-00001234</td><td>Emitida</td><td>150000</td><td>Consultar</td></tr></tbody></table></div>'+
'</div></section></div><aside class="fisc-col"><section class="fisc-card"><div class="fisc-card-head"><h2>Resumen</h2></div><p>Mes operativo</p></section></aside></div></section></main></body></html>';
try {
  const page=path.join(dir,'fixture.html');await fs.writeFile(page,html,'utf8');
  const port=await new Promise((resolve,reject)=>{const s=net.createServer();s.once('error',reject);s.listen(0,'127.0.0.1',()=>{const p=s.address().port;s.close(()=>resolve(p));});});
  child=spawn(edge,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check','--disable-background-networking','--disable-extensions',
    '--remote-allow-origins=*','--user-data-dir='+path.join(dir,'profile'),'--remote-debugging-port='+port,'about:blank'],{stdio:'ignore',windowsHide:true});
  let endpoint;
  for(let i=0;i<70;i++){try{const r=await fetch('http://127.0.0.1:'+port+'/json/version');if(r.ok){endpoint=(await r.json()).webSocketDebuggerUrl;break;}}catch{}await sleep(120);}
  if(!endpoint)throw new Error('CDP endpoint unavailable');
  ws=new WebSocket(endpoint);
  await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
  ws.addEventListener('message',e=>{let m;try{m=JSON.parse(e.data);}catch{return;}if(pending.has(m.id)){pending.get(m.id)(m);pending.delete(m.id);}});
  const {targetId}=await call('Target.createTarget',{url:'about:blank'});
  const {sessionId}=await call('Target.attachToTarget',{targetId,flatten:true});
  await call('Page.enable',{},sessionId);
  for (const width of [363,390,768,1024,1600]) {
    await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false},sessionId);
    await call('Page.navigate',{url:pathToFileURL(page).href},sessionId);
    let data;
    for(let tries=0;tries<40;tries++){
      const expression='(() => {if(document.readyState!=="complete"||!document.querySelector(".fisc-grid-main"))return null;const g=document.querySelector(".fisc-grid-main");return {inner:innerWidth,scroll:document.documentElement.scrollWidth,body:document.body.scrollWidth,grid:getComputedStyle(g).gridTemplateColumns,border:getComputedStyle(document.querySelector(".fisc-card")).borderTopWidth};})()';
      const r=await call('Runtime.evaluate',{expression,returnByValue:true},sessionId);
      data=r.result?.value;
      if(data)break;
      await sleep(100);
    }
    if(!data)throw new Error('No DOM at '+width);
    const ok=data.inner===width&&data.scroll<=width&&data.body<=width&&data.border!=='0px';
    console.log(JSON.stringify({width,...data,ok}));
    if(!ok)throw new Error('Responsive check failed at '+width);
  }
  console.log('SYNTHETIC_RESPONSIVE_CDP_OK 5/5');
} finally {
  try{if(ws?.readyState===WebSocket.OPEN)ws.send(JSON.stringify({id:++next,method:'Browser.close'}));}catch{}
  try{ws?.close();}catch{}
  await sleep(400);
  if(child&&child.exitCode===null&&child.signalCode===null){
    await new Promise(resolve=>{const p=spawn('taskkill',['/PID',String(child.pid),'/T','/F'],{windowsHide:true,stdio:'ignore'});p.on('exit',resolve);p.on('error',resolve);});
  }
  try{await fs.rm(dir,{recursive:true,force:true,maxRetries:5,retryDelay:150});console.log('OWN_PROFILE_CLEANED');}catch(e){console.error('CLEANUP_FAILED '+dir+' '+e.message);}
  console.log('OWN_BROWSER_TEARDOWN');
}
