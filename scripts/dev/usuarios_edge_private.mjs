import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import net from 'node:net';
import {spawn} from 'node:child_process';
const edge='C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const base=process.env.USERS_QA_URL;
const cookie=process.env.USERS_QA_COOKIE;
if(!base||!cookie)throw Error('Only permitted within private HTTP fixture');
const tmp=await fs.mkdtemp(path.join(os.tmpdir(),'users_cdp_'));
let child,ws,count=0;const pending=new Map();const pause=ms=>new Promise(r=>setTimeout(r,ms));
function send(method,params={},sessionId){
 const id=++count;
 return new Promise((resolve,reject)=>{
  const timer=setTimeout(()=>{pending.delete(id);reject(Error('CDP timeout '+method));},15000);
  pending.set(id,msg=>{clearTimeout(timer);msg.error?reject(Error(msg.error.message)):resolve(msg.result)});
  ws.send(JSON.stringify({id,method,params,...(sessionId?{sessionId}:{})}));
 });
}
try{
 const port=await new Promise((resolve,reject)=>{const s=net.createServer();s.once('error',reject);s.listen(0,'127.0.0.1',()=>{const p=s.address().port;s.close(()=>resolve(p));});});
 child=spawn(edge,['--headless=new','--disable-gpu','--no-first-run','--disable-extensions','--remote-allow-origins=*','--user-data-dir='+path.join(tmp,'profile'),'--remote-debugging-port='+port,'about:blank'],{stdio:'ignore',windowsHide:true});
 let endpoint;
 for(let i=0;i<70;i++){try{const r=await fetch('http://127.0.0.1:'+port+'/json/version');if(r.ok){endpoint=(await r.json()).webSocketDebuggerUrl;break;}}catch{}await pause(120);}
 if(!endpoint)throw Error('Edge CDP not available');
 ws=new WebSocket(endpoint);
 await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
 ws.addEventListener('message',e=>{let msg;try{msg=JSON.parse(e.data)}catch{return}if(pending.has(msg.id)){pending.get(msg.id)(msg);pending.delete(msg.id)}});
 const {targetId}=await send('Target.createTarget',{url:'about:blank'});
 const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
 await send('Page.enable',{},sessionId);
 await send('Runtime.enable',{},sessionId);
 await send('Network.enable',{},sessionId);
 const cookies=JSON.parse(cookie);
 for(const [name,value] of Object.entries(cookies))await send('Network.setCookie',{name,value,url:base},sessionId);
 let passed=0;
 for(const width of [363,390,768,1024,1600]){
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false},sessionId);
  for(const route of ['/admin/users','/admin/users/show?id=198']){
   await send('Page.navigate',{url:base+route},sessionId);
   let info;
   for(let n=0;n<55;n++){
    const r=await send('Runtime.evaluate',{expression:'(()=>{if(document.readyState!=="complete")return null;const m=document.querySelector(".av3-users");if(!m)return null;return {width:innerWidth,scroll:document.documentElement.scrollWidth,body:document.body.scrollWidth,heading:document.querySelector("h1")?.textContent,table:!!document.querySelector("table"),css:getComputedStyle(m).minWidth}})()',returnByValue:true},sessionId);
    info=r.result?.value;if(info)break;await pause(140);
   }
   const ok=!!info&&info.width===width&&info.scroll<=width+2&&info.body<=width+2&&(info.table||route.includes('/show'));
   console.log(JSON.stringify({width,route,info,ok}));
   if(!ok){
    const overflow=await send('Runtime.evaluate',{expression:'JSON.stringify([...document.querySelectorAll("body *")].map(e=>({tag:e.tagName,cl:String(e.className).slice(0,110),right:Math.round(e.getBoundingClientRect().right),left:Math.round(e.getBoundingClientRect().left),scroll:e.scrollWidth})).filter(x=>x.right>innerWidth+2).slice(0,18))',returnByValue:true},sessionId);
    console.log('OVERFLOW_ELEMENTS '+overflow.result?.value);
    const styles=await send('Runtime.evaluate',{expression:'JSON.stringify({links:[...document.querySelectorAll("link[rel=stylesheet]")].map(e=>e.href).filter(s=>s.includes("users")),sheets:[...document.styleSheets].map(s=>s.href).filter(s=>s&&s.includes("users")),media:matchMedia("(max-width:767px)").matches,thead:getComputedStyle(document.querySelector(".av3-users__table thead")).display})',returnByValue:true},sessionId);
    console.log('STYLE_CHECK '+styles.result?.value);
    throw Error('Responsive failed '+width+' '+route);
   }
   passed++;
  }
 }
 console.log('USUARIOS_AUTHENTIC_EDGE_RESPONSIVE_PASS '+passed+'/10');
}finally{
 try{if(ws?.readyState===WebSocket.OPEN)ws.send(JSON.stringify({id:++count,method:'Browser.close'}));}catch{}
 try{ws?.close()}catch{}
 await pause(450);
 if(child&&child.exitCode===null&&child.signalCode===null){
  await new Promise(done=>{const p=spawn('taskkill',['/PID',String(child.pid),'/T','/F'],{windowsHide:true,stdio:'ignore'});p.on('exit',done);p.on('error',done)});
 }
 try{await fs.rm(tmp,{recursive:true,force:true,maxRetries:5,retryDelay:150});console.log('USERS_EDGE_OWN_PROFILE_CLEANED')}catch(e){console.log('USERS_EDGE_CLEANUP_WARNING '+e.message)}
}
