#!/usr/bin/env python3
"""Proveedores QA real aislado. Clona código sin .env, levanta MariaDB+PHP privados,
prueba sesión/CSRF nativos y retira solo procesos de esta ejecución."""
import os, sys, re, time, shutil, secrets, subprocess, tempfile, pathlib, signal, json, urllib.parse
import requests
from bs4 import BeautifulSoup
ROOT=pathlib.Path(r"C:\xampp\htdocs\mi-pesca")
BASE=pathlib.Path(tempfile.gettempdir())/"mi_proveedores_http_qa_20261023"
SITE=BASE/"site"
DATA=BASE/"data"
PORT=18979
HTTP=18879
PHP=r"C:\xampp\php\php.exe"
MYSQL=r"C:\xampp\mysql\bin\mysql.exe"
BOOT=r"C:\xampp\mysql\bin\mysql_install_db.exe"
MYSQLD=r"C:\xampp\mysql\bin\mysqld.exe"
DB="mi_proveedores_private_qa"
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
    lines=["APP_ENV=local","APP_DEBUG=false",f"APP_URL={URL}","DB_HOST=127.0.0.1",f"DB_PORT={PORT}",f"DB_NAME={DB}","DB_USER=root",f"DB_PASS={SECRET}","SESSION_NAME=MIPROVEEDORESQA","MAIL_MAILER=log","MAIL_DRIVER=log","MP_ACCESS_TOKEN=","MP_WEBHOOK_SECRET=","MP_PUBLIC_KEY="]
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
    sql(f"INSERT INTO {DB}.users(id,name,email,password_hash,email_verified,session_version,role) VALUES (197,'Admin privado','proveedores-admin-qa@example.test','{pw_hash}',1,1,'admin'),(198,'Cliente privado','proveedores-cliente-qa@example.test','{pw_hash}',1,1,'customer')")
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
    a=anon.get(URL+"/admin/proveedores",allow_redirects=False,timeout=15)
    check(a.status_code in (302,401,403),"No autenticado rechazado")
    bad=anon.post(URL+"/admin/proveedores/save",data={"nombre":"intruso"},allow_redirects=False,timeout=15)
    check(bad.status_code in (302,401,403,419),"POST anónimo rechazado")
    login=session.get(URL+"/login",timeout=15)
    fields=get_form_fields(login.text)
    check("_csrf" in fields,"Token original formulario login")
    invalid=session.post(URL+"/login",data={"email":"proveedores-admin-qa@example.test","password":PASSWORD,"_csrf":"incorrecto"},allow_redirects=False,timeout=15)
    check(invalid.status_code==419,"Login CSRF nativo bloquea token incorrecto")
    login=session.get(URL+"/login",timeout=15)
    fields=get_form_fields(login.text)
    fields.update(email="proveedores-admin-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    logged=session.post(URL+"/login",data=fields,allow_redirects=False,timeout=20)
    check(logged.status_code==302,"Login admin real con usuario de ensayo")
    listing=session.get(URL+"/admin/proveedores",timeout=20)
    check(listing.status_code==200 and "Nuevo proveedor" in listing.text,"Admin listado autorizado")
    csrf=token(listing.text) if 'name="_csrf"' in listing.text else token(session.get(URL+"/admin/proveedores/new",timeout=20).text)
    new=session.get(URL+"/admin/proveedores/new",timeout=20)
    check(new.status_code==200 and 'name="cuit"' in new.text,"Formulario alta y token real")
    csrf=token(new.text)
    post=lambda path,data:session.post(URL+path,data=dict(data,_csrf=csrf),allow_redirects=False,timeout=20)
    saved_bad=post("/admin/proveedores/save",{"nombre":"Proveedor QA","cuit":"20329642330","is_active":"1","condicion_iva":"intruso"})
    check(saved_bad.status_code==200 and "Condición IVA" in saved_bad.text,"Validación servidor condición IVA")
    no_csrf=session.post(URL+"/admin/proveedores/save",data={"nombre":"X","cuit":"20329642330"},allow_redirects=False,timeout=15)
    check(no_csrf.status_code==419,"CSRF save inválido")
    valid={"nombre":"Proveedor QA","cuit":"20329642330","email":"proveedor-qa@example.test","telefono":"3415551234","condicion_iva":"monotributo","is_active":"1"}
    created=post("/admin/proveedores/save",valid)
    check(created.status_code==302,"HTTP alta proveedor")
    sid=sql(f"SELECT id FROM {DB}.suppliers WHERE cuit='20329642330'")
    check(sid.isdigit(),"Alta persistida en MariaDB real")
    sid=int(sid)
    dup=post("/admin/proveedores/save",valid)
    check(dup.status_code==200 and ("Ya existe" in dup.text or "CUIT" in dup.text),"HTTP CUIT duplicado rechazado")
    filtered=session.get(URL+"/admin/proveedores",params={"q":"203296","status":"active","per":25,"page":1},timeout=20)
    check(filtered.status_code==200 and "Proveedor QA" in filtered.text,"Listado búsqueda CUIT y filtros")
    # 26 extra rows force two real pages; SQL fixture is discarded before browser screenshots.
    fixture_values=",".join(f"('A QA Pagina {i:02d}','3000000{i:04d}','', '', '', 1)" for i in range(26))
    sql(f"INSERT INTO {DB}.suppliers(nombre,cuit,email,telefono,condicion_iva,is_active) VALUES {fixture_values}")
    first=session.get(URL+"/admin/proveedores",params={"per":25,"page":1,"status":"active"},timeout=20)
    check(first.status_code==200 and "A QA Pagina 00" in first.text and "A QA Pagina 25" not in first.text and "Siguiente" in first.text, "Paginacion real pagina 1 de 2")
    second=session.get(URL+"/admin/proveedores",params={"per":25,"page":2,"status":"active"},timeout=20)
    check(second.status_code==200 and "A QA Pagina 25" in second.text and "Proveedor QA" in second.text and "Anterior" in second.text, "Paginacion real pagina 2 de 2")
    page=session.get(URL+"/admin/proveedores",params={"per":25,"page":999,"status":"all"},timeout=20)
    check(page.status_code==200 and "Proveedor QA" in page.text,"Paginación fuera de rango normalizada")
    sql(f"DELETE FROM {DB}.suppliers WHERE nombre LIKE 'A QA Pagina %'")
    check(sql(f"SELECT COUNT(*) FROM {DB}.suppliers")== "1", "Fixtures paginacion removidas de MariaDB privada")
    edit=session.get(URL+"/admin/proveedores/edit",params={"id":sid},timeout=20)
    check(edit.status_code==200 and 'name="id"' in edit.text,"Formulario edición")
    edited=post("/admin/proveedores/save",dict(valid,id=str(sid),nombre="Proveedor QA Modificado"))
    check(edited.status_code==302 and sql(f"SELECT nombre FROM {DB}.suppliers WHERE id={sid}")=="Proveedor QA Modificado","Edición HTTP y persistencia")
    sql(f"INSERT INTO {DB}.purchase_invoices(supplier_id,cbte_tipo,pto_vta,cbte_nro,issued_at,imp_neto,imp_iva,imp_total) VALUES({sid},11,1,197,'2026-10-23',100,21,121)")
    check(sql(f"SELECT COUNT(*) FROM {DB}.purchase_invoices WHERE supplier_id={sid}")=="1","Compra real enlazada a proveedor mediante FK")
    rejected=session.post(URL+"/admin/proveedores/delete",data={"id":sid,"_csrf":"malo"},allow_redirects=False,timeout=15)
    check(rejected.status_code==419 and sql(f"SELECT is_active FROM {DB}.suppliers WHERE id={sid}")=="1","CSRF baja bloquea mutación")
    deleted=post("/admin/proveedores/delete",{"id":sid})
    check(deleted.status_code==302 and sql(f"SELECT is_active FROM {DB}.suppliers WHERE id={sid}")=="0","Baja lógica HTTP real")
    check(sql(f"SELECT COUNT(*) FROM {DB}.purchase_invoices WHERE supplier_id={sid}")=="1","Integridad de Compra conservada tras baja")
    again=post("/admin/proveedores/delete",{"id":sid})
    check(again.status_code==302,"Baja repetida idempotente HTTP")
    off=session.get(URL+"/admin/proveedores",params={"status":"inactive","q":"203296"},timeout=20)
    check(off.status_code==200 and "Proveedor QA Modificado" in off.text,"Listado de inactivos")
    activated=post("/admin/proveedores/save",dict(valid,id=str(sid),nombre="Proveedor QA Reactivado"))
    check(activated.status_code==302 and sql(f"SELECT is_active FROM {DB}.suppliers WHERE id={sid}")=="1","Reactivación HTTP real")
    check(sql(f"SELECT COUNT(*) FROM {DB}.purchase_invoices WHERE supplier_id={sid}")=="1","Compra conservada tras reactivación")
    # Comprador sintético, sesión y CSRF originales, nunca debe operar administración.
    customer=requests.Session()
    custfields=get_form_fields(customer.get(URL+"/login",timeout=15).text)
    custfields.update(email="proveedores-cliente-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    cl=customer.post(URL+"/login",data=custfields,allow_redirects=False,timeout=20)
    check(cl.status_code==302,"Login cliente real")
    denied=customer.get(URL+"/admin/proveedores",allow_redirects=False,timeout=15)
    check(denied.status_code in (302,401,403),"Acceso GET cliente sin rol admin denegado")
    denied_post=customer.post(URL+"/admin/proveedores/delete",data={"id":sid,"_csrf":custfields.get("_csrf","")},allow_redirects=False,timeout=15)
    check(denied_post.status_code in (302,401,403,419) and sql(f"SELECT is_active FROM {DB}.suppliers WHERE id={sid}")=="1","Mutación cliente denegada, datos intactos")
    from proveedores_browser_private import run_browser_qa
    browser_results = run_browser_qa(URL, session.cookies.get_dict())
    check(len(browser_results)==10, "10 vistas responsive reales (5 tamaños x 2 vistas)")
    (BASE/"result.json").write_text(json.dumps({"checks":checks,"db_port":PORT,"http_port":HTTP,"supplier_id":sid,"http_real":True,"purchase_integrity":True},indent=2),encoding="utf-8")
    print(f"PROVEEDORES_AUTHENTIC_HTTP_MARIADB_PASS {checks}/{checks}",flush=True)
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
