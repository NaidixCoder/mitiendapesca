#!/usr/bin/env python3
"""I05 final HTTP acceptance on private Admin/MariaDB only. Never commercial."""
import csv, io, json, pathlib, re, subprocess, time
import requests
from bs4 import BeautifulSoup

ROOT=pathlib.Path(r"C:\Users\matii\AppData\Local\Temp\mi_import_i05_final_isolated")
SITE=ROOT/"site"
BASE="http://127.0.0.1:18838"
MYSQL=[r"C:\xampp\mysql\bin\mysql.exe","--protocol=tcp","--host=127.0.0.1",
       "--port=18979","--user=root","--database=mi_import_i05_test",
       "--batch","--skip-column-names"]
session=requests.Session()
checks=0
def ck(v,msg):
    global checks
    checks+=1
    if not v:raise AssertionError(msg)
    print("PASS",msg,flush=True)
def db(sql):
    return subprocess.check_output(MYSQL+["--execute="+sql],text=True,encoding="utf8").strip()
def counts():
    return {t:int(db("SELECT COUNT(*) FROM "+t)) for t in
       ("products","product_variants","inventory_variants","attributes",
        "attribute_values","product_variant_values","product_attributes",
        "product_attribute_values","categories","brands","suppliers")}
def inputs(form):
    out={}
    for e in form.select("input[name]"):
        if e.get("type")=="hidden":out[e["name"]]=e.get("value","")
        elif e.get("type") in ("radio","checkbox") and e.has_attr("checked"):
            out[e["name"]]=e.get("value","1")
    return out
def req(path):
    r=session.get(BASE+path,timeout=30)
    ck(r.status_code==200,"GET 200 "+path)
    return r
def csvdata(rows):
    output=io.StringIO(newline="")
    writer=csv.DictWriter(output,fieldnames=HEAD,delimiter=";",lineterminator="\r\n",
                          extrasaction="ignore")
    writer.writeheader();writer.writerows(rows)
    return ("\ufeff"+output.getvalue()).encode("utf8")
def variant(sku,color):
    return {"tipo_fila":"variante","sku":sku,"sku_padre":"QA-I05-HTTP-PARENT",
        "internal_code":"IN-"+sku[-10:],"nombre":"Variante I05 "+color,
        "precio":"2200","precio_lista":"2400","costo":"950","stock":"5",
        "umbral":"2","publicado":"1","attr_1":"Color QA","valor_1":color}
def upload(tag,rows,endpoint):
    soup=BeautifulSoup(req("/admin/imports").text,"html.parser")
    form=soup.select_one("form[action*=preview]")
    ck(form is not None,"Upload form")
    fields=inputs(form)
    fields.update({"create_missing_products_present":"1","create_missing_products":"1",
                   "u_price":"1","u_list_price":"1","u_cost":"1",
                   "u_stock":"1","u_attrs":"1","u_publish":"1"})
    res=session.post(BASE+endpoint,data=fields,
                     files={"file":("i05_"+tag+".csv",csvdata(rows),"text/csv")},
                     timeout=45,allow_redirects=endpoint.endswith("/preview"))
    return res
def parse_job(res):
    loc=res.headers.get("Location","").replace("\\","/")
    ck(res.status_code==302 and "/admin/imports/show?id=" in loc,"Job detail redirect 302")
    return int(re.search(r"id=(\d+)",loc).group(1))
def job(job_id):
    value=db("SELECT status,revert_status,file_path FROM import_jobs WHERE id="+str(job_id))
    parts=value.split("\t")
    ck(len(parts)==3,"Job SQL exists "+str(job_id))
    return parts
def audit(job_id,action):
    return int(db(f"SELECT COUNT(*) FROM admin_audit_log WHERE entity_type='import_job' "
                  f"AND entity_id={job_id} AND action='{action}'"))
def data_files(job_id):
    _,_,csv_path=job(job_id)
    f=pathlib.Path(csv_path)
    return f.with_name(f.stem+"_errors.json"),f.with_name(f.stem+"_rollback.json")
def actual_apply(form):
    r=session.post(BASE+"/admin/imports/apply",data=inputs(form),
                   allow_redirects=False,timeout=50)
    return parse_job(r)
def csrf():
    soup=BeautifulSoup(req("/admin/imports").text,"html.parser")
    control=soup.select_one("input[name=_csrf]")
    ck(control is not None,"CSRF present")
    return control.get("value","")
def rollback(job_id,token=None):
    return session.post(BASE+"/admin/imports/rollback",
        data={"id":str(job_id),"_csrf":csrf() if token is None else token},
        allow_redirects=False,timeout=35)

ck(ROOT.is_dir() and SITE.is_dir(),"Private application clone")
ck(db("SELECT DATABASE()")=="mi_import_i05_test","Private MariaDB identity")
credentials=json.loads((ROOT/"private_auth.json").read_text(encoding="utf8"))
login=BeautifulSoup(req("/login").text,"html.parser")
lf=login.select_one("form.auth-login__form")
ck(lf is not None,"Real login")
auth=inputs(lf);auth.update({"email":credentials["email"],"password":credentials["password"],
                            "next":"/admin/imports"})
time.sleep(2.3)
r=session.post(BASE+"/login",data=auth,timeout=30,allow_redirects=False)
ck(r.status_code==302,"Authenticated login HTTP 302")
ck("av3-imports" in req("/admin/imports").text,"Admin Imports accessible")
r=req("/admin/imports/template")
HEAD=r.content.decode("utf-8-sig").splitlines()[0].split(";")
ck(len(HEAD)==32 and "attr_1" in HEAD,"Official CSV 32 columns")

# Invalid CSRF must not create jobs nor catalogue.
before=counts()
r=session.post(BASE+"/admin/imports/apply",data={"_csrf":"bad"},
               timeout=20,allow_redirects=False)
ck(r.status_code==419 and counts()==before,"Invalid Apply CSRF HTTP 419, no writes")

# Real two-row import + detail persisted + rollback.
rows=[variant("QA-I05-HTTP-OK-1","Azul"),variant("QA-I05-HTTP-OK-2","Verde")]
prior=counts()
preview=upload("two_good",rows,"/admin/imports/preview")
ck(preview.status_code==200 and counts()==prior,"Two-row preview has no SQL writes")
form=BeautifulSoup(preview.text,"html.parser").select_one("form[action*=apply]")
ck(form is not None,"Preview allows Apply")
id_good=actual_apply(form)
status,rev,_=job(id_good)
ck(status=="finished" and rev=="not_reverted","Two-row job committed/finished")
diag,back=data_files(id_good)
ck(diag.is_file() and back.is_file(),"Diagnostics AND snapshot v3 persisted")
payload=json.loads(diag.read_text(encoding="utf8"))
ck(payload["apply_outcome"]=="committed" and payload["err"]==0 and len(payload["rows"])==2,
    "Detailed rows persisted and transaction confirmed")
ck(audit(id_good,"import_finished")==1,"Audit import_finished exactly once")
detail=req(f"/admin/imports/show?id={id_good}").text
ck("Cambios ejecutados" in detail and "Diferencias y detalle por fila" in detail,
    "Detail displays both blocks")
ck("2 filas" in detail and "QA-I05-HTTP-OK-1" in detail and
   "QA-I05-HTTP-OK-2" in detail,"Real detail includes two rows and SKU")
ck("Productos y variantes" in detail,"Type products translated in detail")
ck("QA I05 Admin" in detail,"Operator resolved from authentic admin audit")
ck("No se registraron altas ni actualizaciones" not in detail,
    "No false empty change state when diagnostic exists")
ck(rollback(id_good,token="bad").status_code==419,"Rollback bad CSRF 419")
r=rollback(id_good)
ck(r.status_code==302 and job(id_good)[1]=="reverted","Rollback POST 302 reverted")
ck(audit(id_good,"import_reverted")==1 and counts()==prior,
   "Eight-related changes undone, no residual master/variant/inventory")
reverted=req(f"/admin/imports/show?id={id_good}").text
ck("Revertida" in reverted and "2 filas" in reverted and "QA-I05-HTTP-OK-1" in reverted,
   "Reverted job preserves historical per-row diagnostics")
ck("Cambios ejecutados" in reverted and
   "No se registraron altas ni actualizaciones" not in reverted,
   "Historical changes not reinterpreted as zero after revert")

# Simulate old/incomplete QA fixture: _errors.json missing (private only).
saved=diag.read_bytes()
diag.unlink()
try:
    missing=req(f"/admin/imports/show?id={id_good}").text
    ck("Diagnóstico por fila no disponible" in missing and
       "No se conserva el archivo" in missing,"Missing historical diagnostic explicitly announced")
    ck("El resumen por fila no está disponible" in missing,
       "Missing summary never presented as 'no changes' ")
    ck("Revertida" in missing and "2" in missing,
       "Legacy job SQL status/count remain visible without fabricated rows")
finally:
    diag.write_bytes(saved)

# A stale Preview JSON must not masquerade as actual COMMITTED diagnostics.
stale=json.loads(saved.decode("utf8"))
stale["apply_outcome"]="not_started"
diag.write_text(json.dumps(stale,ensure_ascii=False),encoding="utf8")
try:
    html=req(f"/admin/imports/show?id={id_good}").text
    ck("Diagnóstico por fila no disponible" in html and
       "No se registraron altas ni actualizaciones" not in html,
       "Stale Preview diagnostic is ignored for a committed historical job")
finally:
    diag.write_bytes(saved)

# Mixed invalid CSV direct to Apply: dry-run fails, job dry-failed, no snapshot, row evidence kept.
mixed=[variant("QA-I05-HTTP-MIXGOOD","Azul"),variant("","Rojo")]
pre=counts()
r=upload("mixed_invalid",mixed,"/admin/imports/preview")
ck(r.status_code==200 and counts()==pre,"Mixed preview rejected without SQL changes")
ck(BeautifulSoup(r.text,"html.parser").select_one("form[action*=apply]") is None,
   "Mixed-row preview blocks ordinary Apply")
# Direct HTTP bypass of preview UI: reuse a valid options payload but point to
# the private uploaded invalid CSV. The controller MUST revalidate before writes.
source_uploads=list((SITE/"storage/imports").glob("upload_*.csv"))
ck(bool(source_uploads),"Uploaded private CSV exists")
invalid_path=max(source_uploads,key=lambda file:file.stat().st_mtime_ns)
ck(b"QA-I05-HTTP-MIXGOOD" in invalid_path.read_bytes(),"Selected exact invalid QA CSV")
regular_data=inputs(form)
regular_data["existing_file"]=str(invalid_path)
regular_data["_csrf"]=csrf()
r=session.post(BASE+"/admin/imports/apply",data=regular_data,timeout=35,allow_redirects=False)
id_dry=parse_job(r)
ck(job(id_dry)[0]=="dry-failed" and counts()==pre,
   "Direct HTTP Apply bypass still dry-failed with zero commercial changes")
dry_diag,dry_back=data_files(id_dry)
ck(dry_diag.is_file() and not dry_back.is_file(),
   "Preflight failure stores diagnostics but no snapshot")
d=json.loads(dry_diag.read_text(encoding="utf8"))
ck(d["err"]>=1 and any(int(x["row"])>0 for x in d["errors"]),
   "Individual bad row persisted in dry-failed job")
ck(audit(id_dry,"import_finished")==0,"No false import_finished for mixed rows")
ck(audit(id_dry,"import_dry_failed")==1,"Dry-failed audit records real operator")
failed_detail=req(f"/admin/imports/show?id={id_dry}").text
ck("QA-I05-HTTP-MIXGOOD" in failed_detail,
   "Failed job detail preserves valid row diagnostics")
ck("QA I05 Admin" in failed_detail,"Operator attributed on dry-failed job")

# Force a SQL error AFTER a valid row created during Apply.
fault_rows=[variant("QA-I05-HTTP-VALID-PREFAULT","Azul"),
            variant("QA-I05-HTTP-SQL-FAIL","Rojo")]
preview=upload("fault_apply",fault_rows,"/admin/imports/preview")
ck(preview.status_code==200,"Preflight succeeds before simulated SQL failure")
form=BeautifulSoup(preview.text,"html.parser").select_one("form[action*=apply]")
ck(form is not None,"Preflight passes SQL-fault fixture")
before_fault=counts()
trigger_sql = """DELIMITER $$
CREATE TRIGGER qa_i05_http_injection BEFORE INSERT ON product_variants
FOR EACH ROW BEGIN
  IF NEW.sku='QA-I05-HTTP-SQL-FAIL' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='QA injected SQL failure';
  END IF;
END$$
DELIMITER ;
"""
subprocess.run(MYSQL, input=trigger_sql, text=True, capture_output=True, check=True)
try:
    id_fault=actual_apply(form)
finally:
    db("DROP TRIGGER IF EXISTS qa_i05_http_injection")
ck(job(id_fault)[0]=="failed" and counts()==before_fault,
   "Apply-time SQL error after writes ROLLED BACK completely")
ck(db("SELECT CONCAT(total_rows,':',ok_rows,':',err_rows) FROM import_jobs WHERE id="+str(id_fault))=="2:1:1",
   "Failed job history persists 2 analyzed / 1 valid / 1 error")
ed,rb=data_files(id_fault)
ck(ed.is_file() and not rb.is_file(),"Failed SQL Apply preserves diagnostics, never snapshot")
value=json.loads(ed.read_text(encoding="utf8"))
ck(value["apply_outcome"]=="rolled_back",
   "Failed job has rolled_back outcome in diagnostic")
ck(audit(id_fault,"import_finished")==0 and audit(id_fault,"import_apply_failed")==1,
   "No false success audit, one failure audit")
fault_detail=req(f"/admin/imports/show?id={id_fault}").text
ck("No aplicada" in fault_detail,
   "Failed transaction correctly labeled in detail")
ck("QA I05 Admin" in fault_detail,
   "Apply failed operator resolved from import_apply_failed audit")
print("I05_FINAL_HTTP_AND_DETAIL_PASS",checks,flush=True)
