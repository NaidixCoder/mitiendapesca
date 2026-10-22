/** Capturas del detalle I05 solo HTTP privado / credencial QA en Temp. */
const fs=require("fs");
const path=require("path");
const puppeteer=require("C:\\Users\\matii\\AppData\\Local\\npm-cache\\_npx\\4b4c857f6efdfb61\\node_modules\\puppeteer");
const ROOT="C:\\Users\\matii\\AppData\\Local\\Temp\\mi_import_i05_final_isolated";
const OUT="C:\\xampp\\htdocs\\mi-pesca\\docs\\modulos\\capturas-importaciones";
const BASE="http://127.0.0.1:18838";
async function main(){
  if (!fs.existsSync(path.join(ROOT,"private_auth.json"))) throw Error("Missing private QA credentials");
  const auth=JSON.parse(fs.readFileSync(path.join(ROOT,"private_auth.json"),"utf8"));
  fs.mkdirSync(OUT,{recursive:true});
  const browser=await puppeteer.launch({
    headless:true,
    executablePath:"C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe",
    userDataDir:path.join(ROOT,"browser-i05-profile"),
    args:["--no-first-run","--disable-default-apps","--no-sandbox"]
  });
  try {
    const page=await browser.newPage();
    await page.setViewport({width:1440,height:900,deviceScaleFactor:1});
    await page.goto(BASE+"/login",{waitUntil:"networkidle0"});
    await page.type('input[name="email"]',auth.email);
    await page.type('input[name="password"]',auth.password);
    await new Promise(resolve=>setTimeout(resolve,2400));
    await Promise.all([
      page.waitForNavigation({waitUntil:"networkidle0"}),
      page.click('form.auth-login__form button[type="submit"]')
    ]);
    for (const [kind,width,height] of [["mobile",390,844],["tablet",768,1024],["desktop",1440,900]]) {
      await page.setViewport({width,height,deviceScaleFactor:1});
      for(const [status,id] of [["revertida",1],["validacion-fallida",2],["apply-fallido",3]]) {
        await page.goto(BASE+"/admin/imports/show?id="+id,{waitUntil:"networkidle0"});
        const output=path.join(OUT,"i05-detalle-"+status+"-"+width+".png");
        await page.screenshot({path:output,fullPage:true});
        const metrics=await page.evaluate(()=>({
          width:window.innerWidth,
          scroll:document.documentElement.scrollWidth,
          hasDetails:document.body.innerText.includes("Diferencias y detalle por fila"),
          hasNoDataFalse:document.body.innerText.includes("No se registraron altas ni actualizaciones"),
          title:document.title,
          rows:(document.body.innerText.match(/QA-I05-HTTP-OK-[12]/g)||[]).length
        }));
        if(metrics.scroll>metrics.width+1)throw Error("Responsive overflow "+status+" "+width+" "+JSON.stringify(metrics));
        if(status==="revertida" && (metrics.rows<2||metrics.hasNoDataFalse)) throw Error("Historical data not rendered "+JSON.stringify(metrics));
        console.log("PASS_VISUAL",status,kind,JSON.stringify(metrics),"FILE",path.basename(output));
      }
    }
  } finally {
    await browser.close();
    console.log("PRIVATE_EDGE_CLOSED");
  }
}
main().catch(e=>{console.error(e);process.exitCode=1});
