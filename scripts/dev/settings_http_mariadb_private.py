#!/usr/bin/env python3
"""Ajustes QA real aislado. Clona código sin .env, levanta MariaDB+PHP privados,
prueba sesión/CSRF nativos y retira solo procesos de esta ejecución."""
import os, sys, re, time, shutil, secrets, subprocess, tempfile, pathlib, signal, json, urllib.parse
import requests
from bs4 import BeautifulSoup
ROOT=pathlib.Path(r"C:\xampp\htdocs\mi-pesca")
BASE=pathlib.Path(tempfile.gettempdir())/"mi_settings_http_qa_20261023"
SITE=BASE/"site"
DATA=BASE/"data"
PORT=18987
HTTP=18887
PHP=r"C:\xampp\php\php.exe"
MYSQL=r"C:\xampp\mysql\bin\mysql.exe"
BOOT=r"C:\xampp\mysql\bin\mysql_install_db.exe"
MYSQLD=r"C:\xampp\mysql\bin\mysqld.exe"
DB="mi_settings_private_qa"
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
    (SITE/"storage").mkdir(exist_ok=True)
    sessions=BASE/"sessions";sessions.mkdir()
    lines=["APP_ENV=local","APP_DEBUG=false",f"APP_URL={URL}","DB_HOST=127.0.0.1",f"DB_PORT={PORT}",f"DB_NAME={DB}","DB_USER=root",f"DB_PASS={SECRET}","SESSION_NAME=MISETTINGSQA","MAIL_MAILER=log","MAIL_DRIVER=log","MP_ACCESS_TOKEN=","MP_WEBHOOK_SECRET=","MP_PUBLIC_KEY="]
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
    sql(f"INSERT INTO {DB}.users(id,name,email,password_hash,email_verified,session_version,role) VALUES (197,'Admin privado','settings-admin-qa@example.test','{pw_hash}',1,1,'admin'),(198,'Cliente privado','settings-cliente-qa@example.test','{pw_hash}',1,1,'customer')")
    router=BASE/"router.php"
    router.write_text('''<?php
$p=parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH) ?: "/";
$p=preg_replace("#/+#","/",$p);
$root=realpath(__DIR__."/site/public_html");
$f=realpath($root.$p);
if($p!=="/" && $f && str_starts_with(strtolower($f),strtolower($root.DIRECTORY_SEPARATOR)) && is_file($f)){
  $ext=strtolower(pathinfo($f,PATHINFO_EXTENSION));
  $types=["css"=>"text/css","js"=>"text/javascript","svg"=>"image/svg+xml","png"=>"image/png","jpg"=>"image/jpeg","jpeg"=>"image/jpeg","woff2"=>"font/woff2","woff"=>"font/woff","ico"=>"image/x-icon","webp"=>"image/webp"];
  header("Content-Type: ".($types[$ext]??"application/octet-stream"));
  readfile($f);
  return true;
}
$_SERVER["SCRIPT_NAME"]="/index.php";
require __DIR__."/site/public_html/index.php";''',encoding="utf-8")
    http=make_proc([PHP,"-d",f"session.save_path={sessions}","-d","SMTP=127.0.0.1","-d","smtp_port=1","-S",f"127.0.0.1:{HTTP}","-t",str(SITE/"public_html"),str(router)],"php.log")
    session=requests.Session()
    for _ in range(30):
        time.sleep(.25)
        try:
            if session.get(URL+"/login",timeout=2).status_code==200:break
        except requests.RequestException:pass
    else:raise RuntimeError("Private HTTP failed "+(BASE/"php.log").read_text(errors="replace")[-700:])
    check(True,"HTTP servidor privado real responde /login")
    anon=requests.Session()
    check(anon.get(URL+"/admin/settings",allow_redirects=False,timeout=15).status_code in (302,401,403),"GET settings anónimo denegado")
    check(anon.post(URL+"/admin/settings?tab=general",data={},allow_redirects=False,timeout=15).status_code in (302,401,403,419),"POST settings anónimo denegado")
    login=session.get(URL+"/login",timeout=15)
    fields=get_form_fields(login.text)
    check("_csrf" in fields,"CSRF nativo de login")
    bad=session.post(URL+"/login",data={"email":"settings-admin-qa@example.test","password":PASSWORD,"_csrf":"malo"},allow_redirects=False,timeout=15)
    check(bad.status_code==419,"Login token inválido 419")
    fields=get_form_fields(session.get(URL+"/login",timeout=15).text)
    fields.update(email="settings-admin-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    check(session.post(URL+"/login",data=fields,allow_redirects=False,timeout=20).status_code==302,"Autenticación admin real")
    paths=[
      ("general","/admin/settings?tab=general","contact_support_email","contact_support_email","settings-qa@example.test"),
      ("pagos","/admin/settings?tab=pagos","pricing_installments_count","pricing_installments_count","5"),
      ("mayoristas","/admin/settings?tab=mayoristas","wh1_discount_percent","wh1_discount_percent","12"),
      ("envios","/admin/settings?tab=envios","shipping_sender_city","shipping_sender_city","Ciudad QA"),
      ("fiscal","/admin/settings?tab=fiscal","seller_business_name","fiscal_seller_business_name","Empresa QA"),
      ("email-marketing","/admin/settings?tab=email-marketing","cap_per_day","email_marketing_cap_per_day","3"),
      ("home-hero","/admin/settings/design/home-hero","home_hero_title","home_hero_title","Hero QA synthetic"),
      ("home-sections","/admin/settings/design/home-sections","youtube_product_ids","home_sections_config","JSON")
    ]
    responses={}
    for tab,path,input_name,key,expected in paths:
      page=session.get(URL+path,timeout=20)
      check(page.status_code==200 and 'name="_csrf"' in page.text,f"{tab}: GET y CSRF original")
      responses[tab]=page.text
      if tab not in ("home-hero","home-sections"):
        check('name="'+input_name+'"' in page.text,f"{tab}: campo real presente")
    center=session.get(URL+"/admin/settings",timeout=15)
    check(center.status_code==200 and "Centro de ajustes" in center.text and "Ventas y precios" in center.text,"Centro funcional")
    csrf=token(responses["general"])
    missing=session.post(URL+"/admin/settings?tab=general",data={"contact_support_email":"intruso"},allow_redirects=False,timeout=20)
    check(missing.status_code==419,"CSRF rechazado sin mutación")
    check(sql(f"SELECT COUNT(*) FROM {DB}.app_settings WHERE setting_key='contact_support_email'")=="0","Sin persistencia tras CSRF inválido")
    # Formularios reales aislados. Sin llamar servicios externos.
    for tab,path,field,key,expected in paths:
      if tab=="home-sections": data={"youtube_product_ids":""}
      elif tab=="home-hero": data={"home_hero_title":expected,"home_hero_description":"QA"}
      else: data={field:expected}
      csrf=token(responses[tab])
      saved=session.post(URL+path,data=dict(data,_csrf=csrf),allow_redirects=False,timeout=20)
      check(saved.status_code==302,f"{tab}: POST autenticado")
      stored=sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='{key}'")
      if tab=="home-hero" and stored!=expected:
        from bs4 import BeautifulSoup
        diagnose=BeautifulSoup(session.get(URL+path,timeout=15).text,"html.parser")
        print("HERO_DIAG:",stored[:50],[(e.get_text(" ",strip=True))[:180] for e in diagnose.select(".av3-card.av3-pad-3")][:3],flush=True)
      check((stored==expected if expected!="JSON" else '"sections"' in stored),f"{tab}: persistencia MariaDB")
    # Secreto existente: POST sin password no debe borrar el valor persistido.
    sql(f"INSERT INTO {DB}.app_settings(setting_key,setting_value,updated_at) VALUES ('correo_api_password','sintetico-no-revelar',NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
    envpage=session.get(URL+"/admin/settings?tab=envios",timeout=15)
    check("sintetico-no-revelar" not in envpage.text,"Credencial no expuesta en HTML")
    tokenpage=session.get(URL+"/admin/settings?tab=email-marketing",timeout=15)
    check('name="web_runner_token"' not in tokenpage.text and "token=" not in tokenpage.text,"Token runner no expuesto")
    t=token(envpage.text)
    saved=session.post(URL+"/admin/settings?tab=envios",data={"_csrf":t,"correo_api_username":"QA","shipping_sender_city":"Prueba"},allow_redirects=False,timeout=20)
    check(saved.status_code==302 and sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='correo_api_password'")=="sintetico-no-revelar","Password previo conservado")
    # Trigger fuerza error después de una escritura previa; deben revertirse TODAS.
    sql(f"CREATE TRIGGER {DB}.settings_qa_fail BEFORE UPDATE ON {DB}.app_settings FOR EACH ROW SET NEW.setting_value = IF(NEW.setting_key = 'social_facebook_url',NULL,NEW.setting_value)")
    old=sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='contact_support_email'")
    pg=session.get(URL+"/admin/settings?tab=general",timeout=15)
    badsave=session.post(URL+"/admin/settings?tab=general",data={"_csrf":token(pg.text),"contact_support_email":"debe-rollback@example.test","social_facebook_url":"https://example.test"},allow_redirects=False,timeout=20)
    check(badsave.status_code==302,"Falla SQL devuelve respuesta controlada")
    check(sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='contact_support_email'")==old,"Rollback InnoDB del formulario")
    sql(f"DROP TRIGGER {DB}.settings_qa_fail")
    customer=requests.Session()
    user=get_form_fields(customer.get(URL+"/login",timeout=15).text)
    user.update(email="settings-cliente-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    check(customer.post(URL+"/login",data=user,allow_redirects=False,timeout=15).status_code==302,"Autenticación cliente real")
    check(customer.get(URL+"/admin/settings",allow_redirects=False,timeout=15).status_code in (302,401,403),"Cliente sin permisos GET")
    check(customer.post(URL+"/admin/settings?tab=pagos",data={"_csrf":user["_csrf"],"pricing_installments_count":"7"},allow_redirects=False,timeout=15).status_code in (302,401,403,419),"Cliente sin permisos POST")
    # Aprovisionamiento por consola: token solo en archivo privado, nunca en HTML/log.
    private_file=BASE/"runner_url_private.txt"
    provision=subprocess.run([PHP,str(SITE/"scripts/dev/settings_provision_email_token.php"),str(private_file)],capture_output=True,text=True,timeout=15,cwd=str(SITE))
    if provision.returncode!=0: print("CLI_DIAG",provision.returncode,provision.stderr[:350],provision.stdout[:200],flush=True)
    check(provision.returncode==0 and private_file.is_file(),"CLI aprovisiona URL fuera del proyecto")
    from urllib.parse import urlparse,parse_qs
    secret=parse_qs(urlparse(private_file.read_text(encoding="utf-8").strip()).query).get("token",[""])[0]
    check(bool(re.fullmatch(r"[0-9a-f]{64}",secret)),"Runner token CSPRNG 256 bits")
    # Comparar solo en memoria, sin imprimir secretos.
    check(sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='email_marketing_web_runner_token'")==secret,"Runner token persistido en BD privada")
    check(secret not in session.get(URL+"/admin/settings?tab=email-marketing",timeout=15).text,"Runner token no se filtra en HTML")
    duplicate=subprocess.run([PHP,str(SITE/"scripts/dev/settings_provision_email_token.php"),str(BASE/"otro_runner.txt")],capture_output=True,text=True,timeout=15,cwd=str(SITE))
    check(duplicate.returncode!=0 and not (BASE/"otro_runner.txt").exists(),"CLI evita rotación accidental")
    cli_out=provision.stdout+provision.stderr+duplicate.stdout+duplicate.stderr
    check(secret not in cli_out,"CLI no imprime token en salida")
    # Upload del hero real en copia privada: referencia activa debe impedir su eliminación.
    import base64
    png=base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jw1kAAAAASUVORK5CYII=")
    hero_page=session.get(URL+"/admin/settings/design/home-hero",timeout=15)
    hero_csrf=token(hero_page.text)
    image=session.post(URL+"/admin/settings/design/home-hero/upload-image",data={"_csrf":hero_csrf},files={"image":("qa.png",png,"image/png")},timeout=15)
    check(image.status_code==200 and image.json().get("ok") is True,"Upload hero autenticado y protegido con CSRF")
    uri=image.json()["url"]
    image_path=urlparse(uri).path
    check((SITE/"public_html"/image_path.lstrip("/")).is_file(),"Archivo hero persiste en clon privado")
    slide={"_csrf":hero_csrf,"home_hero_title":"QA","slide_title[0]":"Slide sintético","slide_image_desktop[0]":uri,"slide_url[0]":"/catalogo","slide_active[0]":"1"}
    save_slide=session.post(URL+"/admin/settings/design/home-hero",data=slide,allow_redirects=False,timeout=15)
    check(save_slide.status_code==302 and sql(f"SELECT COUNT(*) FROM {DB}.home_hero_slides")=="1","Hero imagen referenciada se persiste")
    denied_delete=session.post(URL+"/admin/settings/design/home-hero/delete-image",data={"_csrf":hero_csrf,"url":uri},timeout=15)
    if denied_delete.status_code!=409: print("HERO_DELETE_DIAG",denied_delete.status_code,repr(uri)[:220],image_path,denied_delete.text[:190],flush=True)
    check(denied_delete.status_code==409 and (SITE/"public_html"/image_path.lstrip("/")).is_file(),"Bloqueada eliminación de imagen hero en uso")
    emptied=session.post(URL+"/admin/settings/design/home-hero",data={"_csrf":hero_csrf,"home_hero_title":"Hero sin diapositivas"},allow_redirects=False,timeout=15)
    check(emptied.status_code==302 and sql(f"SELECT COUNT(*) FROM {DB}.home_hero_slides")=="0","Hero guarda reemplazo sin diapositivas")
    accepted=session.post(URL+"/admin/settings/design/home-hero/delete-image",data={"_csrf":hero_csrf,"url":uri},timeout=15)
    check(accepted.status_code==200 and not (SITE/"public_html"/image_path.lstrip("/")).exists(),"Eliminación segura posterior a desasociar")
    # Imagen de secciones: archivo + JSON deben coincidir y eliminación posterior a commit.
    sections_page=session.get(URL+"/admin/settings/design/home-sections",timeout=15)
    section_csrf=token(sections_page.text)
    section_upload=session.post(URL+"/admin/settings/design/home-sections",data={"_csrf":section_csrf},files={"youtube_avatar_fallback":("qa.png",png,"image/png")},allow_redirects=False,timeout=15)
    config=json.loads(sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='home_sections_config'"))
    fallback=config.get("youtube",{}).get("avatar_fallback_url","")
    check(section_upload.status_code==302 and fallback.startswith("/uploads/home-sections/youtube/"),"Avatar del inicio referenciado en JSON")
    fallback_path=SITE/"public_html"/fallback.lstrip("/")
    check(fallback_path.is_file(),"Avatar subido existe físicamente")
    section_remove=session.post(URL+"/admin/settings/design/home-sections",data={"_csrf":section_csrf,"youtube_avatar_fallback_remove":"1"},allow_redirects=False,timeout=15)
    removed=json.loads(sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='home_sections_config'"))
    check(section_remove.status_code==302 and removed.get("youtube",{}).get("avatar_fallback_url","")=="","Remoción del avatar persistida")
    check(not fallback_path.exists(),"Avatar antiguo eliminado tras confirmación")
    # Validaciones y compatibilidad de consumidores con configuración sintética.
    pg=session.get(URL+"/admin/settings?tab=pagos",timeout=15)
    check(session.post(URL+"/admin/settings?tab=pagos",data={"_csrf":token(pg.text),"pricing_installments_count":"999","pricing_debit_discount_percent":"-15"},allow_redirects=False,timeout=15).status_code==302,"Pagos valida límites de entrada")
    check(sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='pricing_installments_count'")=="24","Cuotas limitadas a 24")
    check(sql(f"SELECT setting_value FROM {DB}.app_settings WHERE setting_key='pricing_debit_discount_percent'")=="0","Descuento negativo normalizado a cero")
    for label,path in [("tienda pública","/"),("carrito","/carrito"),("checkout","/checkout"),("pedidos","/admin/orders"),("facturación","/admin/facturacion/control")]:
      resp=session.get(URL+path,allow_redirects=False,timeout=20)
      check(resp.status_code in (200,302,303),f"Regresión lectura {label}, sin HTTP 500")
    # Navegador real con cookie del admin, perfil aislado y cierre garantizado.
    evidence=pathlib.Path(tempfile.gettempdir())/"mi_settings_visual_evidence_20261023"
    if evidence.exists(): raise RuntimeError("Evidencia visual preexistente: conservar, no sobreescribir")
    evidence.mkdir()
    lib=pathlib.Path(tempfile.gettempdir())/"mi_proveedores_browser_libs"
    if not (lib/"playwright").exists():
      print("BROWSER_NOT_AVAILABLE private Playwright missing",flush=True)
    else:
      sys.path.insert(0,str(lib))
      from playwright.sync_api import sync_playwright
      edge=r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
      with sync_playwright() as pw:
        browser=pw.chromium.launch(executable_path=edge,headless=True,args=["--no-first-run","--disable-extensions"])
        try:
          for width in [363,390,768,1024,1600]:
            context=browser.new_context(viewport={"width":width,"height":850})
            try:
              context.add_cookies([{"name":n,"value":v,"url":URL} for n,v in session.cookies.get_dict().items()])
              page=context.new_page()
              try:
                for tab,path,*_ in paths:
                  resp=page.goto(URL+path,wait_until="networkidle",timeout=30000)
                  check(resp is not None and resp.status==200,f"Browser {width} {tab}: HTTP 200")
                  metrics=page.evaluate("() => ({w:document.documentElement.scrollWidth,viewport:innerWidth,nav:!!document.querySelector('.av3-settings-detail__toc'),css:Array.from(document.styleSheets).some(s=>s.href&&s.href.includes('pages/settings.css'))})")
                  if not (metrics["nav"] and metrics["css"] and metrics["w"]<=width+2):
                    print("STYLES",page.evaluate("() => Array.from(document.querySelectorAll('link[rel=stylesheet]')).map(l=>l.href).filter(x=>x.includes('settings'))"),flush=True)
                    print("BROWSER_DIAG",width,tab,metrics,"offenders",page.evaluate("() => Array.from(document.querySelectorAll('*')).filter(e=>e.getBoundingClientRect().right>innerWidth+2).slice(0,10).map(e=>({tag:e.tagName,cls:String(e.className).slice(0,85),right:e.getBoundingClientRect().right}))"),flush=True)
                    page.screenshot(path=str(BASE/f"settings_failure_{width}_{tab}.png"),full_page=True)
                  check(metrics["nav"] and metrics["css"] and metrics["w"]<=width+2,f"Browser {width} {tab}: sin overflow, CSS aplicado")
                  page.screenshot(path=str(evidence/f"{tab}_{width}.png"),full_page=(width in (363,1600)))
              finally:
                page.close()
            finally:
              context.close()
        finally:
          browser.close()
    print(f"SETTINGS_AUTHENTIC_HTTP_MARIADB_PASS {checks}/{checks}",flush=True)

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
