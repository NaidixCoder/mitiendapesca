#!/usr/bin/env python3
"""Authenticated private fault-injection E2E. Never run against a real database."""
import csv
import os
import io
import json
import pathlib
import re
import subprocess
import time
import requests
from bs4 import BeautifulSoup

BASE = "http://127.0.0.1:18808"
ROOT = pathlib.Path(r"C:\Users\matii\AppData\Local\Temp\mi_imports_v1fa2_isolated")
SOURCE = ROOT / "site/app/Services/Import/VariantImporter.php"
MYSQL = [r"C:\xampp\mysql\bin\mysql.exe",
         "--protocol=tcp", "--host=127.0.0.1", "--port=18939",
         "--user=root", "--database=mi_import_v1fa2_test",
         "--batch", "--skip-column-names"]
LOGIN = "qa-1fa2@example.test"
PASS = os.environ["MI_IMPORTS_V1FA2_QA_PASSWORD"]
client = requests.Session()
asserts = 0


def ck(value, message):
    global asserts
    asserts += 1
    if not value:
        raise AssertionError("FAIL " + message)
    print("PASS", message, flush=True)


def db(sql):
    return subprocess.check_output(MYSQL + ["--execute=" + sql],
                                   encoding="utf8", text=True).strip()


def counts():
    return {name: int(db("SELECT COUNT(*) FROM " + name))
            for name in ("products", "product_variants", "brands",
                         "categories", "suppliers", "inventory_variants")}


def frm(f):
    return {e["name"]: e.get("value", "") for e in f.select("input[type=hidden][name]")}


def find_job(jobid):
    line = db("SELECT status,revert_status,file_path FROM import_jobs WHERE id=" + str(jobid))
    return line.split("\t")


def auth():
    res = client.get(BASE + "/login", timeout=25)
    ck(res.status_code == 200, "Synthetic admin login GET")
    form = BeautifulSoup(res.text, "html.parser").select_one("form.auth-login__form")
    ck(form is not None, "Login form exists")
    body = frm(form)
    body.update({"email": LOGIN, "password": PASS, "next": "/admin/imports"})
    time.sleep(2.1)
    resp = client.post(BASE + "/login", data=body,
                       timeout=25, allow_redirects=False)
    ck(resp.status_code == 302, "Authenticated admin POST")
    admin = client.get(BASE + "/admin/imports", timeout=25)
    ck(admin.status_code == 200 and "av3-imports" in admin.text,
       "Admin authenticated shell")


ck(db("SELECT DATABASE()") == "mi_import_v1fa2_test", "Private MariaDB only")
ck(SOURCE.exists(), "Fault target exists ONLY in private code clone")
auth()
headers_response = client.get(BASE + "/admin/imports/template", timeout=30)
ck(headers_response.status_code == 200, "Official CSV GET")
headers = headers_response.content.decode("utf-8-sig").splitlines()[0].split(";")
ck(len(headers) == 32, "Official CSV 32 columns")
before = counts()
source_text = SOURCE.read_text(encoding="utf8")
literal = "                $parentRollbackOps[] = $parentCreate;\n                $parentSnapshotComplete = true;"
ck(source_text.count(literal) == 1, "Unique source marker for private-only fault")
modified = source_text.replace(literal,
    "                // Synthetic QA-only loss of product:create operation.\n"
    "                $parentSnapshotComplete = true;", 1)

try:
    SOURCE.write_text(modified, encoding="utf8")
    # Failure appears after ProductImporter created parent and three catalogs.
    start = BeautifulSoup(client.get(BASE + "/admin/imports", timeout=25).text,
                          "html.parser").select_one("form[action*=preview]")
    ck(start is not None, "Upload form present")
    body = frm(start)
    body.update({"job_type": "products", "delimiter": "auto",
                 "create_missing_products_present": "1",
                 "create_missing_products": "1",
                 "allow_parent_from_variants": "1",
                 "create_missing_brands": "1", "create_missing_categories": "1",
                 "create_missing_suppliers": "1",
                 "u_cost": "1", "u_price": "1", "u_stock": "1"})
    row = {"tipo_fila": "variante", "sku": "QA-A2-FAULT-V",
           "sku_padre": "QA-A2-FAULT-P", "nombre": "Padre fault QA",
           "categoria": "QA-A2-FAULT-CAT", "marca": "QA-A2-FAULT-BRAND",
           "proveedor": "QA-A2-FAULT-SUP", "precio": "2300",
           "costo": "800", "stock": "2", "umbral": "1"}
    output = io.StringIO(newline="")
    writer = csv.DictWriter(output, fieldnames=headers, delimiter=";",
                            lineterminator="\r\n", extrasaction="ignore")
    writer.writeheader()
    writer.writerow(row)
    raw = ("\ufeff" + output.getvalue()).encode("utf8")
    preview = client.post(BASE + "/admin/imports/preview", data=body,
                          files={"file": ("qa_a2_fault.csv", raw, "text/csv")},
                          timeout=35)
    ck(preview.status_code == 200, "Fault preview HTTP 200")
    form = BeautifulSoup(preview.text, "html.parser").select_one("form[action*=apply]")
    ck(form is not None, "Fault accepted in dry-run before real SQL")
    ck(counts() == before, "Fault preview no SQL commercial writes")
    send = client.post(BASE + "/admin/imports/apply", data=frm(form),
                       timeout=50, allow_redirects=False)
    location = send.headers.get("Location", "").replace("\\", "/")
    ck(send.status_code == 302 and "/admin/imports/show?id=" in location,
       "Fault apply HTTP 302 to job detail")
    job_id = int(re.search(r"id=(\d+)", location).group(1))
    status, revert_status, path = find_job(job_id)
    ck(status == "failed", "Fault produced failed (never finished)")
    diag = pathlib.Path(path).with_name(pathlib.Path(path).stem + "_errors.json")
    ck(diag.exists(), "Fault diagnostic persisted")
    values = json.loads(diag.read_text(encoding="utf8"))
    ck(values.get("apply_outcome") == "rolled_back",
       "Fault SQL rolled_back outcome in HTTP flow")
    ck(counts() == before, "Fault rolled back all commercial entities")
    snap = pathlib.Path(path).with_name(pathlib.Path(path).stem + "_rollback.json")
    ck(not snap.exists(), "No partial snapshot published")
    ck(db("SELECT COUNT(*) FROM admin_audit_log WHERE entity_id="
          + str(job_id) + " AND action='import_finished'") == "0",
       "No false success audit event")
    ck(db("SELECT COUNT(*) FROM admin_audit_log WHERE entity_id="
          + str(job_id) + " AND action='import_apply_failed'") == "1",
       "Real failed audit event")
    detail = client.get(BASE + f"/admin/imports/show?id={job_id}", timeout=25)
    ck(detail.status_code == 200 and "Importación no aplicada" in detail.text,
       "Failed import detail informs operator")

    # QA-only status transition simulates an unresolved COMMIT. Existing
    # LIFO backend must reject a POST direct to a different job.
    check_job = int(db("SELECT MAX(id) FROM import_jobs WHERE status='finished' "
                       "AND revert_status='blocked_conflict'"))
    ck(check_job > 0, "Existing synthetic finished job to test global LIFO")
    db("UPDATE import_jobs SET status='review_required' WHERE id=" + str(job_id))
    try:
        page = BeautifulSoup(client.get(BASE + "/admin/imports", timeout=25).text,
                             "html.parser")
        token = page.select_one("input[name=_csrf]")
        ck(token is not None, "Real CSRF token for direct rollback")
        before_block = counts()
        response = client.post(BASE + "/admin/imports/rollback",
                               data={"id": str(check_job),
                                     "_csrf": token.get("value", "")},
                               timeout=35, allow_redirects=False)
        ck(response.status_code == 302, "Review guard direct POST HTTP 302")
        ck(counts() == before_block, "Global pending review blocks all commercial writes")
        res = client.get(BASE + f"/admin/imports/show?id={check_job}", timeout=25)
        ck("conciliación" in res.text.lower(),
           "Operator sees global pending reconciliation notice")
    finally:
        # PRIVATE fixture state only; no automatic reconciliation in app.
        db("UPDATE import_jobs SET status='failed' WHERE id=" + str(job_id))
    print("V1FA2_AUTHENTICATED_FAULT_AND_REVIEW_PASS", asserts, flush=True)
finally:
    SOURCE.write_text(source_text, encoding="utf8")
    print("PRIVATE_CLONE_VARIANT_IMPORTER_RESTORED", flush=True)
