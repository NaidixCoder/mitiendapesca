#!/usr/bin/env python3
"""HTTP autenticado únicamente en clon privado + MariaDB sintética (nunca comercial)."""
import csv
import hashlib
import io
import json
import os
from pathlib import Path
import re
import subprocess
import time
import requests
from bs4 import BeautifulSoup

ROOT = Path(r"C:\Users\matii\AppData\Local\Temp\mi_imports_integral_isolated")
SITE = ROOT / "site"
BASE = "http://127.0.0.1:18828"
MYSQL = [r"C:\xampp\mysql\bin\mysql.exe", "--protocol=tcp", "--host=127.0.0.1",
         "--port=18969", "--user=root", "--database=mi_import_integral_test",
         "--batch", "--skip-column-names"]
ses = requests.Session()
checks = 0


def ck(ok, label):
    global checks
    checks += 1
    if not ok:
        raise AssertionError(label)
    print("PASS", label, flush=True)


def db(query):
    return subprocess.check_output(MYSQL + ["--execute=" + query],
                                   encoding="utf8", text=True).strip()


def cnt(t):
    assert t in ("products", "product_variants", "attributes", "attribute_values",
                 "product_variant_values", "product_attributes",
                 "product_attribute_values", "import_jobs")
    return int(db("SELECT COUNT(*) FROM " + t))


def hidden(form):
    fields = {}
    for e in form.select("input[name]"):
        if e.get("type") == "hidden":
            fields[e["name"]] = e.get("value", "")
        elif e.get("type") in ("checkbox", "radio") and e.has_attr("checked"):
            fields[e["name"]] = e.get("value", "1")
    return fields


def get(path):
    response = ses.get(BASE + path, timeout=30)
    ck(response.status_code == 200, "GET 200 " + path)
    return response


def csrf():
    soup = BeautifulSoup(get("/admin/imports").text, "html.parser")
    token = soup.select_one("input[name=_csrf]")
    ck(token is not None, "CSRF present")
    return token.get("value", "")


def csvbytes(rows):
    s = io.StringIO(newline="")
    writer = csv.DictWriter(s, fieldnames=HEAD, delimiter=";",
                            extrasaction="ignore", lineterminator="\r\n")
    writer.writeheader()
    writer.writerows(rows)
    return ("\ufeff" + s.getvalue()).encode("utf8")


def variant(sku, parent, attr, value):
    return {"tipo_fila": "variante", "sku": sku, "sku_padre": parent,
            "nombre": "Prueba " + sku, "precio": "2100", "costo": "900",
            "attr_1": attr, "valor_1": value}


def preview(tag, rows, attrs=True):
    form = BeautifulSoup(get("/admin/imports").text, "html.parser").select_one(
        "form[action*=preview]")
    ck(form is not None, "Upload form")
    fields = hidden(form)
    fields.update({"create_missing_products_present": "1",
                   "create_missing_products": "1", "u_price": "1",
                   "u_cost": "1", "u_stock": "1", "u_attrs": "1"})
    if not attrs:
        fields.pop("u_attrs", None)
    before = [cnt(t) for t in TABLES]
    response = ses.post(BASE + "/admin/imports/preview", data=fields,
                        files={"file": (tag + ".csv", csvbytes(rows), "text/csv")},
                        timeout=40)
    ck(response.status_code == 200, "Preview HTTP 200 " + tag)
    ck([cnt(t) for t in TABLES] == before, "Preview no SQL writes " + tag)
    return BeautifulSoup(response.text, "html.parser").select_one(
        "form[action*=apply]"), response.text


def apply(tag, form):
    ck(form is not None, "Apply form exists " + tag)
    data = hidden(form)
    ck("_csrf" in data, "Apply CSRF " + tag)
    res = ses.post(BASE + "/admin/imports/apply", data=data,
                   allow_redirects=False, timeout=45)
    loc = res.headers.get("Location", "").replace("\\", "/")
    ck(res.status_code == 302 and "/admin/imports/show?id=" in loc,
       "Apply 302 detail " + tag)
    return int(re.search(r"id=(\d+)", loc).group(1))


def job(job_id):
    cols = db(f"SELECT status,revert_status,file_path FROM import_jobs WHERE id={int(job_id)}").split("\t")
    ck(len(cols) == 3, "Job SQL " + str(job_id))
    return cols


def snapshot(job_id):
    st, revert_status, file = job(job_id)
    path = Path(file)
    snap = path.with_name(path.stem + "_rollback.json")
    diag = path.with_name(path.stem + "_errors.json")
    ck(snap.is_file() and diag.is_file(), "Snapshot and diagnostics exist")
    return json.loads(snap.read_text(encoding="utf8")), json.loads(diag.read_text(encoding="utf8"))


def rollback(job_id, token=None):
    return ses.post(BASE + "/admin/imports/rollback",
                    data={"id": str(job_id), "_csrf": csrf() if token is None else token},
                    allow_redirects=False, timeout=40)


TABLES = ["products", "product_variants", "attributes", "attribute_values",
          "product_variant_values", "product_attributes",
          "product_attribute_values", "import_jobs"]
ck(ROOT.is_dir() and SITE.is_dir(), "Private site identity")
ck(db("SELECT DATABASE()") == "mi_import_integral_test", "Private MariaDB identity")
login = BeautifulSoup(get("/login").text, "html.parser")
form = login.select_one("form.auth-login__form")
ck(form is not None, "Real login form")
body = hidden(form)
body.update({"email": "qa-integral@example.test",
             "password": os.environ["MI_IMPORTS_QA_PASSWORD"],
             "next": "/admin/imports"})
time.sleep(2.2)
res = ses.post(BASE + "/login", data=body, allow_redirects=False, timeout=30)
ck(res.status_code == 302, "Real synthetic admin login HTTP 302")
ck("av3-imports" in get("/admin/imports").text, "Admin Imports available")
template = get("/admin/imports/template")
HEAD = template.content.decode("utf-8-sig").splitlines()[0].split(";")
ck(len(HEAD) == 32 and "attr_1" in HEAD, "Official 32 columns with attr_1")
ck(ses.post(BASE + "/admin/imports/apply", data={"_csrf":"invalid"},
            allow_redirects=False, timeout=20).status_code == 419, "Apply CSRF 419")
parent_id = int(db("SELECT id FROM products WHERE sku='QA-INTEGRAL-P'"))
ck(parent_id > 0, "SQL fixture parent exists")

# Fresh attributes from official CSV, then authenticated rollback.
prior = {t:cnt(t) for t in TABLES}
form, preview_html = preview("attr_new", [variant("QA-HTTP-ATTR", "QA-INTEGRAL-P",
                                                 "Material", "Acero")])
ck("Atributos:" in preview_html and "Material" in preview_html,
   "Preview shows attribute names and selected values")
job_id = apply("attr_new", form)
status, _, _ = job(job_id)
ck(status == "finished", "Committed job finished")
snap, diagnostic = snapshot(job_id)
ck(diagnostic.get("apply_outcome") == "committed" and snap.get("complete") is True,
   "Outcome committed + v3 complete")
ops = [o["entity"]+":"+o["action"] for o in snap["ops"]]
for rel in ("relation_variant_values", "relation_product_attrs", "relation_product_attr_values"):
    ck(rel+":update" in ops, "Snapshot tracks "+rel)
ck("attribute:create" in ops and "attribute_value:create" in ops,
   "Snapshot tracks new masters")
ck(int(db("SELECT COUNT(*) FROM product_variant_values WHERE variant_id IN "
          "(SELECT id FROM product_variants WHERE sku='QA-HTTP-ATTR')")) == 1,
   "Actual SQL variant attribute relation")
ck(cnt("product_attributes") >= 1 and cnt("product_attribute_values") >= 1,
   "Actual SQL parent attribute relations")
ck(int(db(f"SELECT COUNT(*) FROM admin_audit_log WHERE entity_id={job_id} AND action='import_finished'")) == 1,
   "Audit import_finished")

ck(rollback(job_id, token="invalid").status_code == 419, "Rollback CSRF 419")
ok_rb = rollback(job_id)
ck(ok_rb.status_code == 302 and job(job_id)[1] == "reverted",
   "Authenticated rollback 302 and reverted")
ck(int(db(f"SELECT COUNT(*) FROM admin_audit_log WHERE entity_id={job_id} AND action='import_reverted'")) == 1,
   "Audit import_reverted")
for t in TABLES:
    if t != "import_jobs":
        ck(cnt(t) == prior[t], "Rollback restores count " + t)
ck(rollback(job_id).status_code == 302 and job(job_id)[1] == "reverted",
   "Idempotent rollback")

# Existing attribute update/replacement and manual conflict tested in SQL fixture.
# HTTP negative: official attr_1 without valor_1 must fail before Apply.
form, html = preview("invalid_attr", [variant("QA-HTTP-BAD", "QA-INTEGRAL-P",
                                             "Color", "")])
ck(form is None and "complet" in html.lower(), "Partial attr pair rejected on Preview")
ck(db("SELECT COUNT(*) FROM product_variants WHERE sku='QA-HTTP-BAD'") == "0",
   "Invalid relation leaves no variant")

print("IMPORTS_INTEGRAL_AUTHENTICATED_ATTR_HTTP_PASS", checks, flush=True)
