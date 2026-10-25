#!/usr/bin/env python3
"""Auth E2E privado: reutiliza bootstrap preexistente, nunca DB comercial."""
from pathlib import Path
import tempfile, subprocess, sys
root=Path(r"C:\xampp\htdocs\mi-pesca")
s=(root/"scripts/dev/proveedores_http_mariadb_private.py").read_text(encoding="utf8")
for a,b in [
("mi_proveedores_http_qa_20261023","mi_auth_security_qa_20261025"),
("mi_proveedores_private_qa","mi_auth_security_qa"),
("PORT=18979","PORT=18987"),("HTTP=18879","HTTP=18887"),
("SESSION_NAME=MIPROVEEDORESQA","SESSION_NAME=MIAUTHSECURITYQA"),
("proveedores-admin-qa@example.test","auth-admin-qa@example.test"),
("proveedores-cliente-qa@example.test","auth-client-qa@example.test")]:
    s=s.replace(a,b)
begin=s.index('    check(True,"HTTP servidor privado real responde /login")')
end=s.index('\nif __name__=="__main__":',begin)
tests=r'''
    # Migración del módulo Auth necesaria: schema.sql no contiene password_resets.
    migration=ROOT/"database/migrations/20251219_01_password_reset.sql"
    with migration.open("rb") as mf:
        done=subprocess.run([MYSQL,"--no-defaults","--protocol=tcp","--host=127.0.0.1",f"--port={PORT}","--user=root",f"--database={DB}"],stdin=mf,env=env,capture_output=True,timeout=30)
    check(done.returncode==0 and sql(f"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='{DB}' AND table_name='password_resets'")=="1","Migración password_resets privada aplicada")
    check(True,"PHP HTTP y MariaDB aislados")
    customer=requests.Session()
    login=customer.get(URL+"/login",timeout=15)
    fields=get_form_fields(login.text)
    fields.update(email="auth-client-qa@example.test",password=PASSWORD)
    time.sleep(2.1)
    check(customer.post(URL+"/login",data=fields,allow_redirects=False,timeout=20).status_code==302,"Login cliente real")
    check(customer.get(URL+"/cuenta",allow_redirects=False).status_code==200,"Cookie previa activa")
    # Perfil y direcciones con sesión real y dos titulares distintos (IDOR).
    profile=customer.get(URL+"/cuenta/perfil",timeout=20)
    addresses=customer.get(URL+"/cuenta/direcciones",timeout=20)
    check(profile.status_code==200 and addresses.status_code==200,"Perfil y direcciones renderizan autenticados")
    badprofile=customer.post(URL+"/cuenta/perfil",data={"name":"Intruso","_csrf":"invalid"},allow_redirects=False,timeout=20)
    check(badprofile.status_code==419,"Perfil POST sin CSRF bloqueado")
    addr_csrf=token(addresses.text)
    sql(f"INSERT INTO {DB}.user_addresses(user_id,full_name,phone,street,number,city,state,zip,is_default,created_at,updated_at) VALUES (197,'Domicilio ajeno','3415559999','Prueba Calle','100','Firmat','Santa Fe','2630',1,NOW(),NOW())")
    foreign_id=sql(f"SELECT id FROM {DB}.user_addresses WHERE user_id=197 ORDER BY id DESC LIMIT 1")
    foreign=customer.get(URL+"/cuenta/direcciones/editar?id="+foreign_id,allow_redirects=False,timeout=20)
    check(foreign.status_code in (302,403,404),"IDOR GET dirección ajena bloqueado")
    foreign_delete=customer.post(URL+"/cuenta/direcciones/eliminar",data={"id":foreign_id,"_csrf":addr_csrf},allow_redirects=False,timeout=20)
    check(foreign_delete.status_code in (200,302,403,404) and sql(f"SELECT COUNT(*) FROM {DB}.user_addresses WHERE id={foreign_id} AND user_id=197")=="1","IDOR POST no borra dirección ajena")
    no_csrf=customer.post(URL+"/cuenta/direcciones/crear",data={"full_name":"Prueba"},allow_redirects=False,timeout=20)
    check(no_csrf.status_code==419,"Dirección alta sin CSRF bloqueada")
    # Token sintético sin envío de correo, únicamente en MariaDB privada.
    import hashlib
    raw=secrets.token_hex(32)
    h=hashlib.sha256(raw.encode()).hexdigest()
    sql(f"INSERT INTO {DB}.password_resets(user_id,email,token_hash,expires_at,ip,ua) VALUES (198,'auth-client-qa@example.test','{h}',DATE_ADD(NOW(), INTERVAL 60 MINUTE),'127.0.0.1','private-qa')")
    reset=requests.Session()
    page=reset.get(URL+"/reset-clave?token="+raw,timeout=20)
    check(page.status_code==200 and 'name="_csrf"' in page.text,"Formulario reset token privado válido")
    wrong=reset.post(URL+"/reset-clave",data={"token":raw,"password":"ClaveSegura2026!","password2":"ClaveSegura2026!","_csrf":"invalido"},allow_redirects=False,timeout=20)
    check(wrong.status_code==419 and sql(f"SELECT used_at IS NULL FROM {DB}.password_resets WHERE token_hash='{h}'")=="1","CSRF inválido no consume token")
    csrf=token(page.text)
    time.sleep(2.1)
    res=reset.post(URL+"/reset-clave",data={"token":raw,"password":"ClaveSegura2026!","password2":"ClaveSegura2026!","_csrf":csrf},allow_redirects=False,timeout=20)
    check(res.status_code==302 and sql(f"SELECT used_at IS NOT NULL FROM {DB}.password_resets WHERE token_hash='{h}'")=="1","Reset HTTP consume token exactamente una vez")
    check(int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))>1,"Reset incrementa versión sesión")
    stale=customer.get(URL+"/cuenta",allow_redirects=False,timeout=20)
    print("RESET_OLD_COOKIE",stale.status_code,flush=True)
    check(stale.status_code in (302,401,403),"Cookie anterior revocada inmediatamente")
    replay=reset.post(URL+"/reset-clave",data={"token":raw,"password":"OtraClave2026!","password2":"OtraClave2026!","_csrf":csrf},allow_redirects=False,timeout=20)
    check(replay.status_code==302 and int(sql(f"SELECT session_version FROM {DB}.users WHERE id=198"))==2,"Replay reset no cambia versión de sesión")
    # Concurrencia SQL/PHP real: dos procesos independientes usando el mismo token.
    raw2=secrets.token_hex(32);h2=hashlib.sha256(raw2.encode()).hexdigest()
    sql(f"INSERT INTO {DB}.password_resets(user_id,email,token_hash,expires_at,ip,ua) VALUES (198,'auth-client-qa@example.test','{h2}',DATE_ADD(NOW(), INTERVAL 60 MINUTE),'127.0.0.1','concurrency')")
    worker=BASE/"auth_token_concurrency.php"
    worker.write_text("""<?php
require $argv[1].'/app/Services/Auth/PasswordResetService.php';
$db=new PDO('mysql:host=127.0.0.1;port=18987;dbname=mi_auth_security_qa;charset=utf8mb4','root',getenv('MYSQL_PWD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
usleep(100000);
try { echo (new App\\Services\\Auth\\PasswordResetService($db))->resetPassword($argv[2],$argv[3]) ? 'SUCCESS':'REJECTED'; }
catch(Throwable $e) {echo 'ERROR:'.$e->getMessage();}
""",encoding="utf8")
    procs=[subprocess.Popen([PHP,str(worker),str(SITE),raw2,("RacePassword"+str(i)+"!")],env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,creationflags=subprocess.CREATE_NO_WINDOW) for i in range(2)]
    vals=[p.communicate(timeout=20) for p in procs]
    print("RESET_CONCURRENCY",vals,flush=True)
    check(sorted(v[0] for v in vals)==["REJECTED","SUCCESS"],"Token concurrente solo un SUCCESS")
    check(sql(f"SELECT COUNT(*) FROM {DB}.password_resets WHERE token_hash='{h2}' AND used_at IS NOT NULL")=="1","Token concurrente marcado usado una vez")
    print(f"AUTH_SECURITY_PRIVATE_PASS {checks}/{checks}",flush=True)
'''
s=s[:begin]+tests+s[end:]
tmp=Path(tempfile.gettempdir())/"mi_auth_security_runner_generated.py"
tmp.write_text(s,encoding="utf8")
print("Runner:",tmp,flush=True)
raise SystemExit(subprocess.call([sys.executable,str(tmp)],cwd=str(root)))
