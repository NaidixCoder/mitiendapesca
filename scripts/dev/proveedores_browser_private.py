"""Browser real headless: solo contra HTTP QA aislado, con cookie real de sesión admin."""
import sys, tempfile, pathlib, json, os, shutil

def run_browser_qa(base_url, cookies):
    assert base_url == "http://127.0.0.1:18879", "Refused non-private browser target"
    lib=pathlib.Path(tempfile.gettempdir())/"mi_proveedores_browser_libs"
    assert (lib/"playwright").is_dir(), "Playwright Python is not installed privately"
    sys.path.insert(0,str(lib))
    from playwright.sync_api import sync_playwright
    evidence=pathlib.Path(tempfile.gettempdir())/"mi_proveedores_responsive_evidence_20261023"
    if evidence.exists():
        shutil.rmtree(evidence)
    evidence.mkdir(parents=True)
    edge=r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
    assert pathlib.Path(edge).is_file()
    results=[]
    with sync_playwright() as pw:
        browser=pw.chromium.launch(executable_path=edge,headless=True,args=["--no-first-run","--disable-extensions"])
        try:
            for width in [363,390,768,1024,1600]:
                context=browser.new_context(viewport={"width":width,"height":850},device_scale_factor=1)
                try:
                    data=[{"name":n,"value":v,"url":base_url,"httpOnly":False} for n,v in cookies.items()]
                    context.add_cookies(data)
                    # PHP built-in test server does not normalize duplicate path slashes;
                    # rewrite only static assets on localhost to canonical path.
                    def qa_static_route(route):
                        original=route.request.url
                        prefix=base_url+"//assets/"
                        if original.startswith(prefix):
                            route.continue_(url=base_url+"/assets/"+original[len(prefix):])
                        else:
                            route.continue_()
                    context.route("**/*", qa_static_route)
                    page=context.new_page()
                    try:
                        for label,path in [("listado","/admin/proveedores"),("formulario","/admin/proveedores/new")]:
                            resp=page.goto(base_url+path,wait_until="networkidle",timeout=30000)
                            if resp is None or resp.status!=200:
                                raise AssertionError(f"{width} {label}: HTTP {resp.status if resp else 'none'}")
                            heading=page.locator("body").inner_text()
                            assert "Proveedor" in heading, f"{width} {label}: authenticated supplier content absent"
                            metrics=page.evaluate("""() => ({
                              font:getComputedStyle(document.body).fontFamily,
                              desktop:getComputedStyle(document.querySelector('.av3-proveedores__desktop') || document.body).display,
                              mobile:getComputedStyle(document.querySelector('.av3-proveedores__mobile') || document.body).display,
                              viewport:window.innerWidth,
                              document:document.documentElement.scrollWidth,
                              body:document.body.scrollWidth,
                              header:!!document.querySelector('.av3-page-header, .admin-page-header'),
                              visibleActions: Array.from(document.querySelectorAll('button,a')).filter(x => x.getBoundingClientRect().width>0).length,
                              form:!!document.querySelector('form[action*="/proveedores/save"]')
                            })""")
                            filename=f"proveedores_{label}_{width}.png"
                            page.screenshot(path=str(evidence/filename),full_page=True)
                            result={"width":width,"page":label,"metrics":metrics,"image":filename}
                            results.append(result)
                            if metrics["document"]>width+2:
                                offenders=page.evaluate("""() => Array.from(document.querySelectorAll('*')).map(e => ({tag:e.tagName,cls:String(e.className).slice(0,100),right:Math.round(e.getBoundingClientRect().right),width:Math.round(e.getBoundingClientRect().width),scroll:e.scrollWidth})).filter(x=>x.right>window.innerWidth+3).sort((a,b)=>b.right-a.right).slice(0,18)""")
                                print("BROWSER OVERFLOW OFFENDERS",json.dumps(offenders,ensure_ascii=False),flush=True)
                                css=page.evaluate("""() => ({
                                page:document.body.getAttribute('data-page'),
                                desktop:getComputedStyle(document.querySelector('.av3-proveedores__desktop')).display,
                                mobile:getComputedStyle(document.querySelector('.av3-proveedores__mobile')).display,
                                css:Array.from(document.styleSheets).map(s=>s.href).filter(Boolean).filter(x=>x.includes('proveedores')),
                                links:Array.from(document.querySelectorAll('link[href]')).map(e=>e.href).filter(x=>x.includes('proveedores'))
                                })""")
                                print("BROWSER CSS DIAGNOSTIC",json.dumps(css,ensure_ascii=False),flush=True)
                                raise AssertionError(f"{width} {label}: horizontal overflow {metrics['document']}-{width}")
                            if metrics["visibleActions"]<1:
                                raise AssertionError(f"{width} {label}: no visible actions")
                            if label=="formulario" and not metrics["form"]:
                                raise AssertionError(f"{width} form missing")
                            print(f"BROWSER PASS {label} {width}px document={metrics['document']} actions={metrics['visibleActions']}",flush=True)
                    finally:
                        page.close()
                finally:
                    context.close()
        finally:
            browser.close()
    (evidence/"result.json").write_text(json.dumps(results,indent=2),encoding="utf-8")
    print("BROWSER QA:",len(results),"/",len(results),"screenshots at",evidence,flush=True)
    return results
