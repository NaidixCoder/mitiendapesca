import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import net from 'node:net';
import {spawn,spawnSync} from 'node:child_process';
import {pathToFileURL} from 'node:url';
const root='C:\\xampp\\htdocs\\mi-pesca';
const php='C:\\xampp\\php\\php.exe';
const edge='C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const fixture=path.join(root,'scripts','dev','facturacion_visual_realviews_fixture.php');
const evidence=path.join(root,'docs','evidencias','facturacion_v3');
const views=[
'control/index','comprobantes/index','comprobantes/show','compras/index','compras/form',
'libro_iva','libro_iva_compras','alertas/index','cierres_mensuales/index','cierres_mensuales/show',
'reportes/home','reportes/resumen','reportes/mensual','reportes/saldo_iva',
'reportes/pedidos_sin_factura','reportes/comprobantes_pendientes','reportes/desvios',
'reportes/duplicados_exactos','reportes/pedidos_no_facturables'
];
const widths=[363,390,768,1024,1600];
const dir=await fs.mkdtemp(path.join(os.tmpdir(),'mi_fiscal_realviews_'));
let browser,ws,next=0;const pending=new Map();
const sleep=ms=>new Promise(resolve=>setTimeout(resolve,ms));
function call(method,params={},sessionId) {
  const id=++next;
  return new Promise((resolve,reject)=>{
    const timeout=setTimeout(()=>{pending.delete(id);reject(new Error('CDP timeout '+method));},12000);
    pending.set(id,msg=>{clearTimeout(timeout);msg.error?reject(new Error(msg.error.message)):resolve(msg.result);});
    ws.send(JSON.stringify({id,method,params,...(sessionId?{sessionId}:{})}));
  });
}
const summary=[];const problems=[];
try {
  await fs.mkdir(evidence,{recursive:true});
  const files=[];
  for (const view of views) {
    const rendered=spawnSync(php,[fixture,view],{encoding:'utf8',windowsHide:true,maxBuffer:3000000,timeout:15000});
    if(rendered.status!==0||!rendered.stdout?.includes('av3-master-shell')||!rendered.stdout?.includes('av3-fiscal-page')){
      problems.push({view,problem:'PHP_RENDER',exit:rendered.status,stderr:(rendered.stderr||'').slice(0,250)});continue;
    }
    const file=path.join(dir,view.replaceAll('/','_')+'.html');
    await fs.writeFile(file,rendered.stdout,'utf8');
    files.push({view,file,phpWarning:rendered.stderr?.slice(0,300)||''});
  }
  const port=await new Promise((resolve,reject)=>{
    const s=net.createServer();s.once('error',reject);
    s.listen(0,'127.0.0.1',()=>{const p=s.address().port;s.close(()=>resolve(p));});
  });
  browser=spawn(edge,['--headless=new','--disable-gpu','--no-first-run','--no-default-browser-check',
    '--disable-background-networking','--disable-extensions','--remote-allow-origins=*',
    '--user-data-dir='+path.join(dir,'profile'),'--remote-debugging-port='+port,'about:blank'],
    {windowsHide:true,stdio:'ignore'});
  let endpoint;
  for(let i=0;i<70;i++){
    try {const response=await fetch('http://127.0.0.1:'+port+'/json/version');if(response.ok){endpoint=(await response.json()).webSocketDebuggerUrl;break;}}catch{}
    await sleep(150);
  }
  if(!endpoint)throw new Error('Edge CDP unavailable');
  ws=new WebSocket(endpoint);
  await new Promise((resolve,reject)=>{ws.addEventListener('open',resolve,{once:true});ws.addEventListener('error',reject,{once:true});});
  ws.addEventListener('message',event=>{let result;try{result=JSON.parse(event.data);}catch{return;}
    if(pending.has(result.id)){pending.get(result.id)(result);pending.delete(result.id);}
  });
  const {targetId}=await call('Target.createTarget',{url:'about:blank'});
  const {sessionId}=await call('Target.attachToTarget',{targetId,flatten:true});
  await call('Page.enable',{},sessionId);
  let done=0;
  for(const {view,file,phpWarning} of files){
    for(const width of widths){
      await call('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false},sessionId);
      await call('Page.navigate',{url:pathToFileURL(file).href},sessionId);
      let data=null;
      for(let retry=0;retry<35;retry++){
        const exp='(() => {if(document.readyState!=="complete"||!document.querySelector(".av3-master-shell"))return null;const r=document.querySelector(".av3-fiscal-page");const w=document.querySelector(".av3-master-workspace");const elems=[...document.querySelectorAll(".av3-fiscal-page .btn, .av3-fiscal-page button, .av3-fiscal-page input, .av3-fiscal-page select")];return {width:innerWidth,scrollWidth:document.documentElement.scrollWidth,bodyWidth:document.body.scrollWidth,mainWidth:Math.round(r?.getBoundingClientRect().width||0),workspaceWidth:Math.round(w?.getBoundingClientRect().width||0),visibleControls:elems.filter(e=>e.getBoundingClientRect().width>0).length,clippedControls:elems.filter(e=>e.getBoundingClientRect().width>0&&(e.getBoundingClientRect().right>innerWidth+3||e.getBoundingClientRect().left<0)).length,clippedNames:elems.filter(e=>e.getBoundingClientRect().width>0&&(e.getBoundingClientRect().right>innerWidth+3||e.getBoundingClientRect().left<0)).map(e=>({tag:e.tagName,outer:e.outerHTML.slice(0,100),left:Math.round(e.getBoundingClientRect().left),right:Math.round(e.getBoundingClientRect().right)})),realSidebar:!!document.querySelector("#av3-admin-navigation"),header:!!document.querySelector(".av3-page-header"),css:getComputedStyle(document.querySelector(".av3-fiscal-page")).fontSize,cards:document.querySelectorAll(".av3-fiscal-data-table--cards").length};})()';
        const r=await call('Runtime.evaluate',{expression:exp,returnByValue:true},sessionId);
        data=r.result?.value;
        if(data)break;await sleep(75);
      }
      const ok=!!data&&data.width===width&&data.scrollWidth<=width+1&&data.bodyWidth<=width+1&&data.realSidebar&&data.header&&data.clippedControls===0;
      summary.push({view,width,ok,...data,phpWarning});
      if(!ok){problems.push({view,width,reason:'LAYOUT',data});}
      const capture=await call('Page.captureScreenshot',{format:'png',captureBeyondViewport:false},sessionId);
      await fs.writeFile(path.join(evidence,view.replaceAll('/','_')+'_'+width+'.png'),Buffer.from(capture.data,'base64'));
      if(ok)done++;
    }
    console.log('VIEW',view,'checks',summary.filter(x=>x.view===view&&x.ok).length+'/'+widths.length, 'warn', phpWarning?phpWarning.slice(0,80):'none');
  }
  const report={date:'2026-10-23',type:'real PHP templates + real Admin V3 layout, synthetic in-memory data, NO original auth/HTTP, NO commercial DB',engine:'Edge headless CDP',viewportWidths:widths,pages:views.length,rendered:files.length,total:summary.length,passed:done,failures:problems,results:summary};
  await fs.writeFile(path.join(evidence,'qa_report.json'),JSON.stringify(report,null,2),'utf8');
  console.log('FACTURACION_REALVIEW_SHELL',done+'/'+summary.length,'PAGES',files.length,'ISSUES',problems.length);
  for(const p of problems.slice(0,15))console.log('ISSUE',JSON.stringify(p));
  if(problems.length)process.exitCode=1;
} finally {
  try {if(ws?.readyState===WebSocket.OPEN)ws.send(JSON.stringify({id:++next,method:'Browser.close'}));}catch{}
  try{ws?.close();}catch{}
  await sleep(500);
  if(browser&&browser.exitCode===null&&browser.signalCode===null){
    await new Promise(resolve=>{
      const p=spawn('taskkill',['/PID',String(browser.pid),'/T','/F'],{windowsHide:true,stdio:'ignore'});
      p.on('error',resolve);p.on('exit',resolve);
    });
  }
  try{await fs.rm(dir,{recursive:true,force:true,maxRetries:4,retryDelay:150});console.log('PRIVATE_BROWSER_TEMP_CLEAN');}
  catch(e){console.log('TEMP_CLEAN_FAIL',e.message,dir);}
}
