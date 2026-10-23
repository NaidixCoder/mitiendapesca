#!/usr/bin/env python3
"""Proveedores QA real aislado. Clona código sin .env, levanta MariaDB+PHP privados,
prueba sesión/CSRF nativos y retira solo procesos de esta ejecución."""
import os, sys, re, time, shutil, secrets, subprocess, tempfile, pathlib, signal, json, urllib.parse
import requests
from bs4 import BeautifulSoup
ROOT=pathlib.Path(r"C:\xampp\htdocs\mi-pesca")
BASE=pathlib.Path(tempfile.gettempdir())/"mi_auditoria_http_qa_20261023"
SITE=BASE/"site"
DATA=BASE/"data"
PORT=18983
HTTP=18883
PHP=r"C:\xampp\php\php.exe"
MYSQL=r"C:\xampp\mysql\bin\mysql.exe"
BOOT=r"C:\xampp\mysql\bin\mysql_install_db.exe"
MYSQLD=r"C:\xampp\mysql\bin\mysqld.exe"
DB="mi_auditoria_private_qa"
URL=f"http://127.0.0.1:{HTTP}"
SECRET=secrets.token_hex(24)
PASSWORD=secrets.token_hex(20)
env=os.environ.copy()
env["MYSQL_PWD"]=SECRET
children=[]
checks=0
def check(ok, label):
    global checks
    if not ok: raise AssertionError(label)
    checks+=1
    print(f"PASS {checks}: {label}",flush=True)
def sql(query):
    cmd=[MYSQL,"--no-defaults","--protocol=tcp","--host=127.0.0.1",f"--port={PORT}","--user=root","--batch","--skip-column-names","--execute",query]
    r=subprocess.run(cmd,env=env,capture_output=True,text=True,timeout=30)
    if r.returncode: raise RuntimeError("Private SQL failed: "+r.stderr[:400])
    return r.stdout.strip()
def token(html):
    soup=BeautifulSoup(html,"html.parser")
    el=soup.select_one('input[name="_csrf"]')
    if not el: raise RuntimeError("CSRF field missing")
    return el.get("value")
def get_form_fields(html):
    soup=BeautifulSoup(html,"html.parser")
    form=soup.select_one("form.auth-login__form")
    if form is None: raise RuntimeError("Login form missing")
    return {el["name"]:el.get("value","") for el in form.select('input[type="hidden"][name]')}
def make_proc(args,output):
    f=open(BASE/output,"w",encoding="utf-8")
    p=subprocess.Popen(args,stdout=f,stderr=subprocess.STDOUT,env=env,cwd=str(BASE),creationflags=subprocess.CREATE_NO_WINDOW)
    children.append((p,f))
    return p
def main():
    for f in [ROOT/"database/schema.sql",pathlib.Path(PHP),pathlib.Path(MYSQL),pathlib.Path(MYSQLD),pathlib.Path(BOOT)]:
        if not f.is_file(): raise RuntimeError("Prerequisite missing")
    if BASE.exists(): raise RuntimeError("REFUSED existing QA dir: inspect manually")
    import socket
    for port in [PORT,HTTP]:
        with socket.socket() as sock:
            if sock.connect_ex(("127.0.0.1",port))==0: raise RuntimeError(f"REFUSED port {port} in use")
    BASE.mkdir()
    cmd=["robocopy",str(ROOT),str(SITE),"/E","/XD",".git","storage","node_modules",".vs",".idea","uploads","/XF",".env",".env.*","phpinfo.php","tmp_session_dump.php","tmp_session_info.php","/NFL","/NDL","/NJH","/NJS","/NP"]
    c=subprocess.run(cmd,capture_output=True,text=True,timeout=180)
    if c.returncode>7: raise RuntimeError(f"clone failed {c.returncode}: {c.stderr[:300]}")
    check(not (SITE/".env").exists() and (SITE/"vendor/autoload.php").is_file(),"Clon privado sin .env ni almacenamiento comercial")
    # En el servidor raíz privado, normalizar URLs de estilos del CLON, sin tocar el proyecto.
    test_head=SITE/"app/Views/partials/head.php"
    head_code=test_head.read_text(encoding="utf-8")
    pattern="asset($cssFile) . $assetQuery"
    if head_code.count(pattern)<2: raise RuntimeError("QA-only CSS path normalization contract changed")
    test_head.write_text(head_code.replace(pattern,"('/assets/' . ltrim($cssFile, '/')) . $assetQuery"),encoding="utf-8")
    (SITE/"storage").mkdir(exist_ok=True)
    sessions=BASE/"sessions";sessions.mkdir()
    lines=["APP_ENV=local","APP_DEBUG=false",f"APP_URL={URL}","DB_HOST=127.0.0.1",f"DB_PORT={PORT}",f"DB_NAME={DB}","DB_USER=root",f"DB_PASS={SECRET}","SESSION_NAME=MIAUDITORIAQA","MAIL_MAILER=log","MAIL_DRIVER=log","MP_ACCESS_TOKEN=","MP_WEBHOOK_SECRET=","MP_PUBLIC_KEY="]
    (SITE/".env").write_text("\n".join(lines)+"\n",encoding="utf-8")
    init=subprocess.run([BOOT,f"--datadir={DATA}",f"--port={PORT}",f"--password={SECRET}"],env=env,capture_output=True,text=True,timeout=80)
    if init.returncode: raise RuntimeError("MariaDB bootstrap failed "+init.stderr[-500:])
    ini=DATA/"my.ini"
    check(ini.is_file(),"MariaDB privado inicializado")
    mariadb=make_proc([MYSQLD,f'--defaults-file={ini}',"--bind-address=127.0.0.1",f"--port={PORT}","--console"],"mariadb.log")
    for _ in range(50):
        time.sleep(.25)
        if mariadb.poll() is not None: raise RuntimeError("MariaDB stopped "+(BASE/"mariadb.log").read_text(errors="replace")[-500:])
        try:
            if sql("SELECT @@port")==str(PORT):break
        except Exception:continue
    else:raise RuntimeError("MariaDB private startup timeout")
    datadir=sql("SELECT @@datadir").replace("\\","/").rstrip("/").lower()
    while "//" in datadir: datadir=datadir.replace("//","/")
    expected=str(DATA).replace("\\","/").rstrip("/").lower()
    print("QA DATADIR",repr(datadir),"EXPECTED",repr(expected),flush=True)
    check(datadir==expected and sql("SELECT @@port")==str(PORT),"Identidad puerto y datadir privados confirmada")
    sql(f"CREATE DATABASE {DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
    with open(ROOT/"database/schema.sql","rb") as f:
        imported=subprocess.run([MYSQL,"--no-defaults","--protocol=tcp","--host=127.0.0.1",f"--port={PORT}","--user=root",f"--database={DB}"],stdin=f,env=env,capture_output=True,timeout=90)
    if imported.returncode:raise RuntimeError("Schema import failed "+imported.stderr.decode(errors="replace")[-900:])
    check(int(sql(f"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='{DB}'"))>60,"Esquema SQL privado completo")
    pw_hash=subprocess.check_output([PHP,"-r","echo password_hash($argv[1], PASSWORD_DEFAULT);",PASSWORD],text=True,timeout=15).strip()
    sql(f"INSERT INTO {DB}.users(id,name,email,password_hash,email_verified,session_version,role) VALUES (197,'Admin privado','auditoria-admin-qa@example.test','{pw_hash}',1,1,'admin'),(198,'Cliente privado','auditoria-cliente-qa@example.test','{pw_hash}',1,1,'customer')")
    router=BASE/"router.php"
    router.write_text('<?php $p=preg_replace("~/{2,}~","/",parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH) ?: "/");$f=__DIR__."/site/public_html".$p;if($p!=="/" && is_file($f)){$ext=strtolower(pathinfo($f,PATHINFO_EXTENSION));$mime=["css"=>"text/css","js"=>"application/javascript","svg"=>"image/svg+xml","png"=>"image/png","jpg"=>"image/jpeg","jpeg"=>"image/jpeg","webp"=>"image/webp","woff2"=>"font/woff2","woff"=>"font/woff","ico"=>"image/x-icon"][$ext]??"application/octet-stream";header("Content-Type: ".$mime);readfile($f);return;}$_SERVER["SCRIPT_NAME"]="/index.php";require __DIR__."/site/public_html/index.php";',encoding="utf-8")
    http=make_proc([PHP,"-d",f"session.save_path={sessions}","-d","SMTP=127.0.0.1","-d","smtp_port=1","-S",f"127.0.0.1:{HTTP}","-t",str(SITE/"public_html"),str(router)],"php.log")
    session=requests.Session()
    for _ in range(30):
        time.sleep(.25)
        try:
            if session.get(URL+"/login",timeout=2).status_code==200:break
        except requests.RequestException:pass
    else:raise RuntimeError("Private HTTP failed "+(BASE/"php.log").read_text(errors="replace")[-700:])
    css_probe=session.get(URL+"/assets/css/admin-v3/layout.css",timeout=10)
    print("CSS_PROBE",css_probe.status_code,css_probe.url,css_probe.headers.get("content-type",""),len(css_probe.content),css_probe.text[:50],flush=True)
    check(css_probe.status_code==200 and "text/css" in css_probe.headers.get("content-type",""),"CSS shared served at HTTP root")
    check(True,"HTTP servidor privado real responde /login")
    # Sin escrituras comerciales: el árbol se clonó sin .env, y SQL apunta al puerto privado.
    anon=requests.Session()
    for route in ["/admin/audit","/admin/audit/logins","/admin/audit/show?id=1"]:
        response=anon.get(URL+route,allow_redirects=False,timeout=15)
        check(response.status_code in (302,401,403),"Acceso anonimo denegado "+route)
    form=get_form_fields(session.get(URL+"/login",timeout=15).text)
    check("_csrf" in form,"CSRF nativo login")
    bad=session.post(URL+"/login",data=dict(form,email="auditoria-admin-qa@example.test",password=PASSWORD,_csrf="malo"),allow_redirects=False,timeout=15)
    check(bad.status_code==419,"CSRF login rechazado")
    form=get_form_fields(session.get(URL+"/login",timeout=15).text)
    form.update(email="auditoria-admin-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    logged=session.post(URL+"/login",data=form,allow_redirects=False,timeout=20)
    check(logged.status_code==302,"Sesion admin real")
    # Datos sintéticos forzados: admin audit y login events nunca provenientes del comercio.
    sql(f"INSERT INTO {DB}.admin_audit_log(id,user_id,request_id,action,entity_type,entity_id,metadata,created_at) VALUES (990101,197,NULL,'product_updated','product',null,'{{}}','2020-01-01 00:00:00'), (990102,197,'12345678901234567890123456789012','product_created','product',null,'{{}}','2026-10-23 14:00:00')")
    for i,event,stamp in [(990111,"login_success","2020-01-01 00:00:00"),(990112,"login_failed","2020-01-01 00:00:00"),(990113,"logout","2020-01-01 00:00:00"),(990114,"login_failed","2099-01-01 00:00:00"),(990115,"other_event","2020-01-01 00:00:00")]:
        sql(f"INSERT INTO {DB}.auth_login_events(id,event,method,user_id,created_at) VALUES ({i},'{event}','password',197,'{stamp}')")
    count_before=int(sql(f"SELECT COUNT(*) FROM {DB}.admin_audit_log WHERE id IN(990101,990102)"))
    check(count_before==2,"Audit fixtures existen")
    mainpage=session.get(URL+"/admin/audit",params={"range":"all","action":"product_updated"},timeout=20)
    check(mainpage.status_code==200 and "Detalles" in mainpage.text,"Actividad devuelve evento historico sin request_id")
    detail=session.get(URL+"/admin/audit/show",params={"id":990101},timeout=20)
    check(detail.status_code==200 and ("990101" in detail.text),"Detalle de evento real en SQL privado")
    for invalid in ["abc","-1","0","1x"]:
        resp=session.get(URL+"/admin/audit/show",params={"id":invalid},timeout=20)
        check(resp.status_code==404,"ID invalido rechazado "+invalid)
    loginpage=session.get(URL+"/admin/audit/logins",params={"range":"all","event":"login_failed"},timeout=20)
    check(loginpage.status_code==200 and ("login" in loginpage.text.lower()),"Historial login filtrado")
    loginpage2=session.get(URL+"/admin/audit/logins",params={"range":"all","q":"2099"},timeout=20)
    check(loginpage2.status_code==200,"Filtro query HTTP")
    def remaining():
        return int(sql(f"SELECT COUNT(*) FROM {DB}.auth_login_events WHERE id IN (990111,990112,990113,990114,990115)"))
    csrf=token(loginpage.text)
    bad=session.post(URL+"/admin/audit/logins/cleanup",data={"confirm":"1","_csrf":"malo"},allow_redirects=False,timeout=20)
    check(bad.status_code==419 and remaining()==5,"POST cleanup CSRF inválido conserva 5 registros")
    notconfirmed=session.post(URL+"/admin/audit/logins/cleanup",data={"_csrf":csrf},allow_redirects=False,timeout=20)
    check(notconfirmed.status_code==302 and remaining()==5,"Sin confirmación no limpia")
    cleaned=session.post(URL+"/admin/audit/logins/cleanup",data={"confirm":"1","_csrf":csrf,"_back":"/admin/audit/logins"},allow_redirects=False,timeout=20)
    check(cleaned.status_code==302 and remaining()==2,"Cleanup HTTP real elimina solo 3 expirados")
    residual=sql(f"SELECT GROUP_CONCAT(id ORDER BY id) FROM {DB}.auth_login_events WHERE id IN (990111,990112,990113,990114,990115)")
    check(residual=="990114,990115","Reciente y evento ajeno preservados")
    check(int(sql(f"SELECT COUNT(*) FROM {DB}.admin_audit_log WHERE id IN (990101,990102)"))==2,"Evidencia audit intacta")
    check(int(sql(f"SELECT COUNT(*) FROM {DB}.admin_audit_log WHERE action='logins_cleanup_executed'"))>=1,"Limpieza registrada en auditoría")
    customer=requests.Session()
    cform=get_form_fields(customer.get(URL+"/login",timeout=15).text)
    cform.update(email="auditoria-cliente-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    clogin=customer.post(URL+"/login",data=cform,allow_redirects=False,timeout=20)
    check(clogin.status_code==302,"Sesion customer real")
    cget=customer.get(URL+"/admin/audit/logins",allow_redirects=False,timeout=15)
    check(cget.status_code in (302,401,403),"Usuario customer no consulta logins")
    cpost=customer.post(URL+"/admin/audit/logins/cleanup",data={"confirm":"1","_csrf":csrf},allow_redirects=False,timeout=15)
    check(cpost.status_code in (302,401,403,419) and remaining()==2,"Usuario customer no limpia")
    # Browser real headless, sin tocar ventanas ajenas.
    lib=pathlib.Path(tempfile.gettempdir())/"mi_proveedores_browser_libs"
    if (lib/"playwright").is_dir():
        sys.path.insert(0,str(lib))
        from playwright.sync_api import sync_playwright
        edge=r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
        evidence=pathlib.Path(tempfile.gettempdir())/'mi_auditoria_visual_qa_20261023'
        evidence.mkdir(exist_ok=True)
        with sync_playwright() as pw:
            browser=pw.chromium.launch(executable_path=edge,headless=True,args=["--no-first-run","--disable-extensions"])
            try:
                for width in [363,390,768,1024,1600]:
                    context=browser.new_context(viewport={"width":width,"height":850})
                    try:
                        context.add_cookies([{"name":k,"value":v,"url":URL} for k,v in session.cookies.get_dict().items()])
                        page=context.new_page()
                        try:
                            for route in ["/admin/audit?range=all","/admin/audit/show?id=990101","/admin/audit/logins?range=all"]:
                                resp=page.goto(URL+route,wait_until="networkidle",timeout=30000)
                                check(resp is not None and resp.status==200,"Browser HTTP "+str(width)+" "+route)
                                h=page.locator("body").inner_text()
                                check("Auditor" in h or "sesión" in h,"Browser contenido admin "+str(width))
                                metrics=page.evaluate("() => ({vw:window.innerWidth,sw:document.documentElement.scrollWidth,css:Array.from(document.styleSheets).some(s=>s.href?.includes('audit.css') && s.cssRules.length>0)})")
                                if metrics["sw"]>width+2:
                                    print("OVERFLOW_ELEMENTS",page.evaluate("() => Array.from(document.querySelectorAll(\'*\')).map(e=>({tag:e.tagName,cls:String(e.className).slice(0,80),right:Math.round(e.getBoundingClientRect().right),scroll:e.scrollWidth,client:e.clientWidth})).filter(x=>x.right>innerWidth+2||x.scroll>x.client+2).sort((a,b)=>b.right-a.right).slice(0,20)"),flush=True)
                                check(metrics["sw"]<=width+2,"Browser sin overflow "+str(width)+" "+route+" "+repr(metrics))
                                check(metrics["css"],"Browser CSS cargado "+str(width))
                                shared=page.evaluate("() => ({styles:Array.from(document.styleSheets).filter(s=>s.href?.includes('admin-v3/')).map(s=>({name:s.href.split('/').pop(),count:(()=>{try{return s.cssRules.length}catch(e){return -1}})()})),shell:getComputedStyle(document.querySelector('.av3-master-shell')).display})")
                                if not any(x["count"]>0 and ("master-shell.css" in x["name"] or "layout.css" in x["name"]) for x in shared["styles"]):
                                    print("CSS_DEBUG",page.evaluate("() => ({origin:location.origin,links:Array.from(document.querySelectorAll('link[rel=stylesheet]')).slice(0,20).map(x=>({href:x.href,raw:x.getAttribute('href')})),sheets:Array.from(document.styleSheets).slice(0,20).map(x=>({href:x.href,error:(()=>{try{return x.cssRules.length}catch(e){return e.name+':'+e.message}})()}))})"),flush=True)
                                check(any(x["count"]>0 and ("master-shell.css" in x["name"] or "layout.css" in x["name"]) for x in shared["styles"]),"CSS compartido cargado "+str(width)+" "+str(shared)[:400])
                                filename=f"audit_{width}_{route.split('?')[0].strip('/').replace('/','_')}.png"
                                page.screenshot(path=str(evidence/filename),full_page=True)
                        finally: page.close()
                    finally: context.close()
            finally: browser.close()
    else: print("BROWSER_QA_NOT_AVAILABLE: missing private playwright",flush=True)
    print(f"AUDITORIA_AUTHENTIC_HTTP_MARIADB_PASS {checks}/{checks}",flush=True)
    print('EVIDENCE screenshots',str(evidence),'count',len(list(evidence.glob('*.png'))),flush=True)

if __name__=="__main__":
    ok=False
    try:
        main();ok=True
    except Exception as e:
        print("QA_FAILED:",str(e),flush=True)
        import traceback;traceback.print_exc()
    finally:
        for proc,fh in reversed(children):
            if proc.poll() is None:
                proc.terminate()
                try:proc.wait(timeout=8)
                except subprocess.TimeoutExpired:
                    proc.kill();proc.wait(timeout=5)
            fh.close()
        print("TEARDOWN processes:",[(p.pid,p.poll()) for p,_ in children],flush=True)
        if ok and BASE.exists():
            try:shutil.rmtree(BASE);print("TEARDOWN private dir removed",flush=True)
            except OSError as e:print("TEARDOWN cleanup warning",str(e),flush=True)
        else:print("TEARDOWN private dir kept for troubleshooting" if BASE.exists() else "TEARDOWN no private dir",flush=True)
    sys.exit(0 if ok else 1)
