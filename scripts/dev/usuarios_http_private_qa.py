#!/usr/bin/env python3
"""Prueba HTTP de Usuarios en copia y MariaDB privados. Reutiliza bootstrap aislado validado."""
from pathlib import Path
import tempfile
import subprocess
import sys

root=Path(r"C:\xampp\htdocs\mi-pesca")
source=(root/"scripts/dev/proveedores_http_mariadb_private.py").read_text(encoding="utf-8")
source=source.replace("mi_proveedores_http_qa_20261023","mi_usuarios_http_qa_20261023")
source=source.replace("mi_proveedores_private_qa","mi_usuarios_private_qa")
source=source.replace("PORT=18979","PORT=18981").replace("HTTP=18879","HTTP=18881")
source=source.replace("SESSION_NAME=MIPROVEEDORESQA","SESSION_NAME=MIUSUARIOSQA")
source=source.replace("proveedores-admin-qa@example.test","usuarios-admin-qa@example.test")
source=source.replace("proveedores-cliente-qa@example.test","usuarios-cliente-qa@example.test")
# Únicamente en el clon privado, registrar el endpoint reservado a MAIN CHAT.
source=source.replace('    router=BASE/"router.php"', '''
    route_file=SITE/"config/routes.php"
    route_source=route_file.read_text(encoding="utf-8")
    marker="'POST /admin/users/force-logout' =>"
    if marker not in route_source: raise RuntimeError("Private route marker missing")
    route_source=route_source.replace(marker,"'POST /admin/users/rebuild-stats' => 'Admin\\\\UsersController@rebuildStats',\\n  "+marker,1)
    route_file.write_text(route_source,encoding="utf-8")
    router=BASE/"router.php"''')

start=source.index('    check(True,"HTTP servidor privado real responde /login")')
end=source.index('\nif __name__=="__main__":',start)
tests=r'''
    check(True,"Servidor HTTP privado y MariaDB real operativos")
    anon=requests.Session()
    check(anon.get(URL+"/admin/users",allow_redirects=False,timeout=15).status_code in (302,401,403),"Anónimo no accede a Usuarios")
    login=session.get(URL+"/login",timeout=15)
    fields=get_form_fields(login.text)
    check("_csrf" in fields,"Login usa CSRF nativo")
    fields.update(email="usuarios-admin-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    logged=session.post(URL+"/login",data=fields,allow_redirects=False,timeout=20)
    check(logged.status_code==302,"Administrador sintético autenticado")
    page=session.get(URL+"/admin/users",timeout=20)
    check(page.status_code==200 and 'name="q"' in page.text and "usuarios-cliente" in page.text,"Listado renderiza cuentas y búsqueda")
    csrf=token(page.text)
    post=lambda route,data:session.post(URL+route,data=dict(data,_csrf=csrf),allow_redirects=False,timeout=20)
    before=sql(f"SELECT role FROM {DB}.users WHERE id=198")
    invalid=post("/admin/users/role",{"user_id":198,"role":"root","next":"/admin/users"})
    check(invalid.status_code==302 and sql(f"SELECT role FROM {DB}.users WHERE id=198")==before,"Rol desconocido rechazado sin degradación silenciosa")
    own=post("/admin/users/role",{"user_id":197,"role":"customer","next":"/admin/users"})
    check(own.status_code==302 and sql(f"SELECT role FROM {DB}.users WHERE id=197")=="admin","Autodegradación bloqueada")
    csrf_fail=session.post(URL+"/admin/users/role",data={"user_id":198,"role":"admin","_csrf":"incorrecto"},allow_redirects=False,timeout=15)
    check(csrf_fail.status_code in (302,419) and sql(f"SELECT role FROM {DB}.users WHERE id=198")=="customer","CSRF incorrecto no muta rol")
    promote=post("/admin/users/role",{"user_id":198,"role":"admin","next":"/admin/users"})
    check(promote.status_code==302 and sql(f"SELECT role FROM {DB}.users WHERE id=198")=="admin","Ascenso permitido y persistido")
    ver1=int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))
    same=post("/admin/users/role",{"user_id":198,"role":"admin","next":"/admin/users"})
    check(same.status_code==302 and int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))==ver1,"Mismo rol no revoca sesiones innecesariamente")
    demote=post("/admin/users/role",{"user_id":198,"role":"customer","next":"/admin/users"})
    check(demote.status_code==302 and sql(f"SELECT role FROM {DB}.users WHERE id=198")=="customer","Degradación y persistencia")
    check(int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))>ver1,"Cambios de rol invalidan sesiones anteriores")
    logout=post("/admin/users/force-logout",{"user_id":198,"next":"/admin/users"})
    check(logout.status_code==302 and int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))>ver1+1,"Revocación de sesión se persiste")
    own_logout=post("/admin/users/force-logout",{"user_id":197,"next":"/admin/users"})
    check(own_logout.status_code==302 and sql(f"SELECT role FROM {DB}.users WHERE id=197")=="admin","No se fuerza cierre propio")
    missing=post("/admin/users/force-logout",{"user_id":999999,"next":"/admin/users"})
    check(missing.status_code==302,"Usuario inexistente tratado sin error HTTP")
    query=session.get(URL+"/admin/users",params={"q":"usuarios-cliente","page":999},timeout=20)
    check(query.status_code==200 and "usuarios-cliente" in query.text,"Paginación fuera de rango conserva datos")
    detail=session.get(URL+"/admin/users/show?id=198",timeout=20)
    check(detail.status_code==200 and "Usuario #198" in detail.text,"Detalle renderiza perfil sintético")
    check('name="user_id"' in detail.text and '/admin/users/rebuild-stats' in detail.text,"Formulario POST recálculo con CSRF")
    old_stats=sql(f"SELECT COUNT(*) FROM {DB}.customer_stats WHERE user_id=198")
    session.get(URL+"/admin/users/show?id=198&rebuild=1",timeout=15)
    check(sql(f"SELECT COUNT(*) FROM {DB}.customer_stats WHERE user_id=198")==old_stats,"GET rebuild=1 no escribe customer_stats")
    wrong=session.post(URL+"/admin/users/rebuild-stats",data={"user_id":198,"_csrf":"invalid"},allow_redirects=False,timeout=15)
    check(wrong.status_code in (302,419) and sql(f"SELECT COUNT(*) FROM {DB}.customer_stats WHERE user_id=198")==old_stats,"POST sin CSRF válido no recalcula")
    rebuilt=post("/admin/users/rebuild-stats",{"user_id":198})
    check(rebuilt.status_code==302 and sql(f"SELECT COUNT(*) FROM {DB}.customer_stats WHERE user_id=198")=="1","POST autenticado recalcula en SQL privado")
    old_update=sql(f"SELECT UNIX_TIMESTAMP(updated_at) FROM {DB}.customer_stats WHERE user_id=198")
    session.get(URL+"/admin/users/show?id=198&rebuild=1",timeout=15)
    check(sql(f"SELECT UNIX_TIMESTAMP(updated_at) FROM {DB}.customer_stats WHERE user_id=198")==old_update,"GET no actualiza marca de recálculo")

    customer=requests.Session()
    field=get_form_fields(customer.get(URL+"/login",timeout=15).text)
    field.update(email="usuarios-cliente-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    check(customer.post(URL+"/login",data=field,allow_redirects=False,timeout=20).status_code==302,"Cliente sintético autenticado")
    check(customer.get(URL+"/admin/users",allow_redirects=False,timeout=15).status_code in (302,401,403),"Cliente no accede a listado")
    denied=customer.post(URL+"/admin/users/role",data={"user_id":198,"role":"admin","_csrf":field.get("_csrf","")},allow_redirects=False,timeout=15)
    check(denied.status_code in (302,401,403,419) and sql(f"SELECT role FROM {DB}.users WHERE id=198")=="customer","Cliente no eleva su rol")
    check(customer.get(URL+"/cuenta",allow_redirects=False,timeout=15).status_code==200,"Cookie cliente es válida antes de revocación")
    promote2=post("/admin/users/role",{"user_id":198,"role":"admin"})
    check(promote2.status_code==302,"Promoción con sesión cliente activa")
    stale_promote=customer.get(URL+"/cuenta",allow_redirects=False,timeout=15)
    print("IMMEDIATE_PROMOTION_COOKIE_STATUS",stale_promote.status_code,flush=True)
    check(int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))>1,"Promoción incrementó versión mientras cookie antigua continúa en memoria")
    newadmin=requests.Session()
    fields2=get_form_fields(newadmin.get(URL+"/login",timeout=15).text)
    fields2.update(email="usuarios-cliente-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    check(newadmin.post(URL+"/login",data=fields2,allow_redirects=False,timeout=20).status_code==302 and newadmin.get(URL+"/admin/users",timeout=20).status_code==200,"Nueva sesión admin obtiene privilegios")
    demote2=post("/admin/users/role",{"user_id":198,"role":"customer"})
    stale_admin=newadmin.get(URL+"/admin/users",allow_redirects=False,timeout=15)
    print("IMMEDIATE_DEMOTION_ADMIN_STATUS",stale_admin.status_code,flush=True)
    check(demote2.status_code==302 and sql(f"SELECT role FROM {DB}.users WHERE id=198")=="customer","Degradación de admin persistida antes del TTL global")
    customer2=requests.Session()
    fields3=get_form_fields(customer2.get(URL+"/login",timeout=15).text)
    fields3.update(email="usuarios-cliente-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    check(customer2.post(URL+"/login",data=fields3,allow_redirects=False,timeout=20).status_code==302 and customer2.get(URL+"/cuenta",timeout=20).status_code==200,"Cuenta cliente reautenticada antes de cierre")
    out=post("/admin/users/force-logout",{"user_id":198})
    stale_logout=customer2.get(URL+"/cuenta",allow_redirects=False,timeout=15)
    print("IMMEDIATE_FORCE_LOGOUT_STATUS",stale_logout.status_code,flush=True)
    check(out.status_code==302 and int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))>1,"Cierre forzado incrementa versión en BD")
    denied_stats=customer2.post(URL+"/admin/users/rebuild-stats",data={"user_id":198,"_csrf":fields3.get("_csrf","")},allow_redirects=False,timeout=15)
    check(denied_stats.status_code in (302,401,403,419),"Cliente no recalcula estadísticas")

    browser_env=env.copy()
    browser_env["USERS_QA_URL"]=URL
    browser_env["USERS_QA_COOKIE"]=json.dumps(session.cookies.get_dict())
    edge=subprocess.run(["node",str(ROOT/"scripts/dev/usuarios_edge_private.mjs")],env=browser_env,capture_output=True,text=True,timeout=100)
    print(edge.stdout,flush=True)
    if edge.returncode: raise AssertionError("Edge authenticated responsive: "+edge.stderr[-700:])
    check("USUARIOS_AUTHENTIC_EDGE_RESPONSIVE_PASS 10/10" in edge.stdout,"10 pruebas responsive auténticas en Edge headless")

    # Concurrencia real: dos procesos PHP y dos conexiones PDO independientes, sin DB comercial.
    sql(f"INSERT INTO {DB}.users(id,name,email,password_hash,email_verified,session_version,role) VALUES(199,'Admin competidor','usuarios-admin2@example.test','{pw_hash}',1,1,'admin')")
    worker=BASE/"users_concurrent_worker.php"
    worker.write_text(r"""<?php
    require $argv[1].'/app/Services/Admin/Users/UserAccessService.php';
    $db=new PDO('mysql:host=127.0.0.1;port=18981;dbname=mi_usuarios_private_qa;charset=utf8mb4','root',getenv('MYSQL_PWD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT=>6]);
    usleep(250000);
    try {
      (new App\Services\Admin\Users\UserAccessService($db))->changeRole((int)$argv[2],(int)$argv[3],'customer');
      echo 'COMMITTED';
    } catch(Throwable $e) {echo 'DENIED_OR_ROLLBACK';}
    """,encoding="utf-8")
    for trial in range(4):
        sql(f"UPDATE {DB}.users SET role='admin' WHERE id IN(197,199)")
        commands=[[PHP,str(worker),str(SITE),'197','199'],[PHP,str(worker),str(SITE),'199','197']]
        processes=[subprocess.Popen(cmd,env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,creationflags=subprocess.CREATE_NO_WINDOW) for cmd in commands]
        results=[p.communicate(timeout=15) for p in processes]
        roles=sql(f"SELECT CONCAT(id,':',role) FROM {DB}.users WHERE id IN (197,199) ORDER BY id").splitlines()
        print('CONCURRENCY_RESULT',trial+1,roles,[(p.returncode,out,err[-180:]) for p,(out,err) in zip(processes,results)],flush=True)
        commit_count=sum('COMMITTED' in out for out,err in results)
        check(commit_count==1 and sorted(r.split(':')[1] for r in roles)==['admin','customer'],f"Concurrencia real intento {trial+1}: exactamente un admin preservado")
    sql(f"UPDATE {DB}.users SET role='admin' WHERE id=197")
    print(f"USUARIOS_HTTP_PRIVATE_PASS {checks}/{checks}",flush=True)
'''
source=source[:start]+tests+source[end:]
target=Path(tempfile.gettempdir())/"mi_usuarios_http_private_runner.py"
target.write_text(source,encoding="utf-8")
print("Runner:",target,flush=True)
raise SystemExit(subprocess.call([sys.executable,str(target)],cwd=str(root)))
