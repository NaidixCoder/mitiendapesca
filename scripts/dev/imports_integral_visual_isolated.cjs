/** Screenshot suite QA: private Edge profile, no personal browser. */
const fs = require('fs');
const path = require('path');
const puppeteer = require("C:\\Users\\matii\\AppData\\Local\\npm-cache\\_npx\\4b4c857f6efdfb61\\node_modules\\puppeteer");
const SITE = "http://127.0.0.1:18828";
const OUT = "C:\\xampp\\htdocs\\mi-pesca\\docs\\modulos\\capturas-importaciones";
const PROFILE = "C:\\Users\\matii\\AppData\\Local\\Temp\\mi_imports_integral_isolated\\visual-profile";
async function main(){
  fs.mkdirSync(OUT,{recursive:true});
  const browser = await puppeteer.launch({
    headless: true,
    executablePath: "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe",
    userDataDir: PROFILE,
    args: ["--no-first-run","--disable-default-apps","--no-sandbox"]
  });
  try {
    const page=await browser.newPage();
    await page.setViewport({width:1440,height:900,deviceScaleFactor:1});
    await page.goto(SITE+"/login",{waitUntil:"networkidle0"});
    await page.type('input[name="email"]',"qa-integral@example.test");
    await page.type('input[name="password"]',process.env.MI_IMPORTS_QA_PASSWORD);
    await new Promise(r=>setTimeout(r,2300));
    await Promise.all([
      page.waitForNavigation({waitUntil:"networkidle0"}),
      page.click('form.auth-login__form button[type="submit"]')
    ]);
    for(const [kind,width,height] of [["mobile",390,844],["tablet",768,1024],["desktop",1440,900]]){
      await page.setViewport({width,height,deviceScaleFactor:1});
      for(const [view,url] of [
        ["dashboard","/admin"],
        ["pedidos","/admin/orders"],
        ["importaciones","/admin/imports"],
        ["detalle","/admin/imports/show?id=4"]
      ]){
        await page.goto(SITE+url,{waitUntil:"networkidle0"});
        await page.screenshot({path:path.join(OUT,view+"-"+kind+".png"),fullPage:true});
        const metrics=await page.evaluate(() => ({
          scrollWidth:document.documentElement.scrollWidth,
          innerWidth:window.innerWidth,
          bodyHeight:document.body.scrollHeight,
          title:document.title
        }));
        console.log("SCREENSHOT",kind,view,JSON.stringify(metrics));
      }
    }
  } finally {
    await browser.close();
    console.log("QA_BROWSER_CLOSED");
  }
}
main().catch(e=>{console.error(e);process.exitCode=1});
