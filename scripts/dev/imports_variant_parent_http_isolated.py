#!/usr/bin/env python3
"""V1F-A2 authenticated HTTP fixture.

Run ONLY with the private PHP server 127.0.0.1:18808, private MariaDB
127.0.0.1:18939/mi_import_v1fa2_test, and synthetic administrator account.
The private copy lives outside the repository; no commercial data is read.
"""
import csv
import os
import hashlib
import io
import json
import pathlib
import re
import subprocess
import time

import requests
from bs4 import BeautifulSoup

BASE = "http://127.0.0.1:18808"
PRIVATE = pathlib.Path(r"C:\Users\matii\AppData\Local\Temp\mi_imports_v1fa2_isolated")
SITE = PRIVATE / "site"
MYSQL = r"C:\xampp\mysql\bin\mysql.exe"
MYSQL_ARGS = [MYSQL, "--protocol=tcp", "--host=127.0.0.1", "--port=18939",
              "--user=root", "--database=mi_import_v1fa2_test",
              "--batch", "--skip-column-names"]
EXPECTED_DB = "mi_import_v1fa2_test"
session = requests.Session()
checks = 0


def check(value, message):
    global checks
    checks += 1
    if not value:
        raise AssertionError(f"FAIL {message}")
    print("PASS", message, flush=True)


def sql(query):
    return subprocess.check_output(MYSQL_ARGS + ["--execute=" + query],
                                   text=True, encoding="utf-8").strip()


def count(table):
    if table not in ("products", "product_variants", "brands", "categories",
                     "suppliers", "inventory_variants", "import_jobs"):
        raise ValueError("Non-fixture table")
    return int(sql("SELECT COUNT(*) FROM " + table))


def counts():
    return {t: count(t) for t in
            ("products", "product_variants", "brands", "categories",
             "suppliers", "inventory_variants", "import_jobs")}


def form_inputs(form):
    result = {}
    for field in form.select("input[name]"):
        kind = field.get("type", "text").lower()
        if kind == "hidden":
            result[field["name"]] = field.get("value", "")
        elif kind in ("radio", "checkbox") and field.has_attr("checked"):
            result[field["name"]] = field.get("value", "1")
    return result


def req(path):
    res = session.get(BASE + path, timeout=35)
    check(res.status_code == 200, "HTTP 200 " + path)
    return res


def make_csv(headers, rows):
    result = io.StringIO(newline="")
    writer = csv.DictWriter(result, fieldnames=headers, delimiter=";",
                            lineterminator="\r\n", extrasaction="ignore")
    writer.writeheader()
    writer.writerows(rows)
    return ("\ufeff" + result.getvalue()).encode("utf-8")


def variant(sku, parent, title, cat="QA F1A2 Category", brand="QA F1A2 Brand",
            supplier="QA F1A2 Supplier", price=2100, stock=4, weight=""):
    return {"tipo_fila": "variante", "sku": sku, "sku_padre": parent,
            "internal_code": "IC-" + sku[-15:], "nombre": title,
            "categoria": cat, "marca": brand, "proveedor": supplier,
            "moneda": "ARS", "costo": "950", "margen_pct": "25",
            "precio": str(price), "precio_lista": str(price + 300),
            "stock": str(stock), "umbral": "2", "publicado": "1",
            "peso_envio_g": weight}


def upload_preview(tag, rows, *, allow_parent=True, ship_dims=False, expect_apply=True):
    home = BeautifulSoup(req("/admin/imports").text, "html.parser")
    form = home.select_one("form[action*=preview]")
    check(form is not None, "Official upload form")
    fields = form_inputs(form)
    fields.update({
        "job_type": "products", "delimiter": "auto",
        "create_missing_products_present": "1",
        "create_missing_products": "1",
        "create_missing_brands": "1",
        "create_missing_categories": "1",
        "create_missing_suppliers": "1",
        "u_internal_code": "1", "u_cost": "1", "u_price": "1",
        "u_stock": "1", "u_list_price": "1", "u_publish": "1",
    })
    if allow_parent:
        fields["allow_parent_from_variants"] = "1"
    else:
        fields.pop("allow_parent_from_variants", None)
    if ship_dims:
        fields["u_ship_dims"] = "1"
    else:
        fields.pop("u_ship_dims", None)
    baseline = counts()
    res = session.post(BASE + "/admin/imports/preview",
                       data=fields,
                       files={"file": ("qa_" + tag + ".csv",
                                       make_csv(HEADERS, rows), "text/csv")},
                       timeout=45)
    check(res.status_code == 200, "Preview real HTTP 200 " + tag)
    actual = counts()
    check(actual == baseline, "Preview has zero commercial and job SQL writes " + tag)
    html = BeautifulSoup(res.text, "html.parser")
    apply_form = html.select_one("form[action*=apply]")
    check(bool(apply_form) == expect_apply, "Preview Apply eligibility " + tag)
    return res, apply_form


def apply(tag, form):
    fields = form_inputs(form)
    check(any("csrf" in key.lower() for key in fields),
          "Real Apply CSRF included " + tag)
    res = session.post(BASE + "/admin/imports/apply", data=fields,
                       timeout=60, allow_redirects=False)
    location = res.headers.get("Location", "").replace("\\", "/")
    check(res.status_code == 302 and "/admin/imports/show?id=" in location,
          "Apply HTTP 302 to detail " + tag)
    match = re.search(r"id=(\d+)", location)
    check(match is not None, "Job id returned " + tag)
    return int(match.group(1)), location


def job(job_id):
    text = sql(f"SELECT id,status,revert_status,file_path FROM import_jobs WHERE id={int(job_id)}")
    parts = text.split("\t")
    check(len(parts) == 4, f"Job SQL lookup {job_id}")
    return {"id": int(parts[0]), "status": parts[1],
            "revert_status": parts[2], "path": parts[3]}


def snapshot(jobinfo):
    path = pathlib.Path(jobinfo["path"])
    check(path.is_file(), "Stored original CSV exists for job " + str(jobinfo["id"]))
    snapfile = path.with_name(path.stem + "_rollback.json")
    check(snapfile.is_file(), "Snapshot file exists " + str(jobinfo["id"]))
    data = json.loads(snapfile.read_text(encoding="utf-8"))
    errorfile = path.with_name(path.stem + "_errors.json")
    check(errorfile.is_file(), "Execution diagnostics exist")
    details = json.loads(errorfile.read_text(encoding="utf-8"))
    return data, details, snapfile


def id_for(table, field, value):
    if table not in ("products", "product_variants", "brands", "categories", "suppliers"):
        raise ValueError("Unsafe table")
    if field not in ("sku", "name", "nombre"):
        raise ValueError("Unsafe field")
    safe = value.replace("'", "''")
    val = sql(f"SELECT id FROM {table} WHERE {field}='{safe}' LIMIT 1")
    return int(val) if val else 0


def audit(job_id, event):
    return int(sql("SELECT COUNT(*) FROM admin_audit_log WHERE entity_id="
                   + str(int(job_id)) + " AND action='" + event + "'"))


def rollback(job_id, *, csrf=True):
    res = req(f"/admin/imports/show?id={job_id}")
    detail = BeautifulSoup(res.text, "html.parser")
    form = detail.select_one("form[action*=rollback]")
    if not form:
        # This is expected for blocked or already-reverted jobs.
        home = BeautifulSoup(req("/admin/imports").text, "html.parser")
        token = home.select_one("input[name=_csrf]")
        check(token is not None, "Fallback real CSRF input found")
        fields = {"id": str(job_id), "_csrf": token.get("value", "")}
    else:
        fields = form_inputs(form)
    fields["id"] = str(job_id)
    if not csrf:
        fields["_csrf"] = "QA_INVALID_CSRF"
    return session.post(BASE + "/admin/imports/rollback", data=fields,
                        timeout=50, allow_redirects=False)


# Hard guard before any write.
check(sql("SELECT DATABASE()") == EXPECTED_DB, "Private MariaDB identity")
check(PRIVATE.is_dir() and SITE.is_dir(), "Isolated Admin copy")
login = BeautifulSoup(req("/login").text, "html.parser")
form = login.select_one("form.auth-login__form")
check(form is not None, "Real login form")
values = form_inputs(form)
values.update(email="qa-1fa2@example.test",
              password=os.environ["MI_IMPORTS_V1FA2_QA_PASSWORD"],
              next="/admin/imports")
time.sleep(2.3)
auth = session.post(BASE + "/login", data=values,
                    timeout=30, allow_redirects=False)
check(auth.status_code == 302, "Authenticated login HTTP 302")
admin = req("/admin/imports")
check("av3-imports" in admin.text and "/admin/imports/preview" in admin.text,
      "Real Admin shell and Importaciones available")
template = req("/admin/imports/template")
HEADERS = template.content.decode("utf-8-sig").splitlines()[0].split(";")
check(len(HEADERS) == 32 and "sku_padre" in HEADERS,
      "Official 32-column CSV downloaded")

# Preserve existing synthetics, which must never be erased by a new import.
sql("INSERT INTO categories (name,slug,is_active) VALUES "
    "('QA PREEXISTING CATEGORY','qa-preexisting-f1a2',1)")
sql("INSERT INTO brands (name,slug) VALUES "
    "('QA PREEXISTING BRAND','qa-preexisting-f1a2')")
baseline = counts()

# CSRF rejection on Apply, before any job is created.
bad = session.post(BASE + "/admin/imports/apply",
                   data={"_csrf": "QA_INVALID_CSRF"},
                   timeout=20, allow_redirects=False)
check(bad.status_code == 419, "Invalid Apply CSRF HTTP 419")
check(counts() == baseline, "Invalid CSRF made zero SQL writes")

# Disabled parent option, no parent and no variant.
_, no_parent_form = upload_preview(
    "parent_off",
    [variant("QA-A2-OFF", "QA-A2-UNKNOWN", "Padre deshabilitado")],
    allow_parent=False, expect_apply=False)

# Dimensions ON: reject BEFORE creating anything.
_, ship_form = upload_preview(
    "dims_on",
    [variant("QA-A2-DIMS", "QA-A2-UNKNOWN2", "Peso incompatible", weight="850")],
    ship_dims=True, expect_apply=False)

# Main 2-row CSV creates eight entities via implicit parent.
rows = [
    variant("QA-A2-V1", "QA-A2-PARENT", "Primera variante"),
    variant("QA-A2-V2", "QA-A2-PARENT", "Segunda variante"),
]
before = counts()
preview, apply_form = upload_preview("parent_two_variants", rows)
check("Crear padre" in preview.text or "variante" in preview.text.lower(),
      "Preview mentions variant and parent")
job_id, _ = apply("implicit_parent", apply_form)
current = job(job_id)
check(current["status"] == "finished", "Successful implicit parent job finished")
snap, diagnostic, snapfile = snapshot(current)
check(diagnostic["apply_outcome"] == "committed", "Execution outcome committed")
expected = ["brand:create", "category:create", "supplier:create",
            "product:create", "variant:create", "inventory_variant:create",
            "variant:create", "inventory_variant:create"]
operations = [op["entity"] + ":" + op["action"] for op in snap["ops"]]
check(snap.get("version") == 3 and snap.get("complete") is True,
      "Complete snapshot v3")
check(operations == expected, "8 operations in FK-safe creation order")
for operation in snap["ops"]:
    check(re.fullmatch(r"[0-9a-f]{64}", operation.get("after_sha256", "")) is not None
          and re.fullmatch(r"[0-9a-f]{64}", operation.get("after_rel_sha256", "")) is not None,
          "Full entity+dependency fingerprint " + operation["entity"])
parent_id = id_for("products", "sku", "QA-A2-PARENT")
v1 = id_for("product_variants", "sku", "QA-A2-V1")
v2 = id_for("product_variants", "sku", "QA-A2-V2")
check(parent_id > 0 and v1 > 0 and v2 > 0, "Created parent and both variants SQL")
check(int(sql("SELECT COUNT(*) FROM product_variants WHERE product_id="
              + str(parent_id))) == 2,
      "Both variants belong to one parent")
check(int(sql("SELECT COUNT(*) FROM inventory_variants WHERE variant_id IN "
              f"({v1},{v2})")) == 2,
      "Both variant inventory rows")
check(audit(job_id, "import_finished") == 1,
      "Exactly one import_finished audit for successful job")
check("Revertir importación" in req(f"/admin/imports/show?id={job_id}").text,
      "Detail presents reversible finished job")
check("QA-A2-PARENT" in req("/admin/imports").text or
      str(job_id) in req("/admin/imports").text,
      "Importations history shows job")

# CSRF protection of rollback, still no data modification.
state_before_bad_csrf = counts()
wrong_rb = rollback(job_id, csrf=False)
check(wrong_rb.status_code == 419, "Invalid rollback CSRF HTTP 419")
check(counts() == state_before_bad_csrf, "Invalid rollback CSRF no SQL writes")

# Actual authenticated HTTP rollback removes eight creates, never preexisting.
valid_rb = rollback(job_id)
check(valid_rb.status_code == 302, "Rollback HTTP 302")
check(job(job_id)["revert_status"] == "reverted",
      "Job SQL status reverted after authenticated POST")
check(audit(job_id, "import_reverted") == 1,
      "Successful reversal correctly audited")
for t in before:
    if t != "import_jobs":
        check(count(t) == before[t], "Eight operations restored baseline table " + t)
check(id_for("categories", "name", "QA PREEXISTING CATEGORY") > 0
      and id_for("brands", "name", "QA PREEXISTING BRAND") > 0,
      "Preexisting catalog entries preserved")
again = rollback(job_id)
check(again.status_code == 302 and job(job_id)["revert_status"] == "reverted",
      "Repeat HTTP rollback idempotent")

# Existing parent must survive a variant-only import / rollback.
precat = id_for("categories", "name", "QA PREEXISTING CATEGORY")
prebrand = id_for("brands", "name", "QA PREEXISTING BRAND")
sql("INSERT INTO products (category_id,brand_id,sku,slug,name,sale_mode,price) VALUES "
    f"({precat},{prebrand},'QA-A2-EX-PARENT','qa-a2-ex-parent',"
    "'Preexisting QA Parent','variants',3100)")
existing_id = id_for("products", "sku", "QA-A2-EX-PARENT")
_, existing_form = upload_preview(
    "existing_parent",
    [variant("QA-A2-EX-V", "QA-A2-EX-PARENT", "Variante sin padre nuevo",
             cat="QA PREEXISTING CATEGORY", brand="QA PREEXISTING BRAND",
             supplier="", price=2500)])
existing_job, _ = apply("existing_parent", existing_form)
exjob = job(existing_job)
check(exjob["status"] == "finished", "Existing-parent job finished")
exsnap, _, _ = snapshot(exjob)
check([op["entity"] + ":" + op["action"] for op in exsnap["ops"]]
      == ["variant:create", "inventory_variant:create"],
      "Existing parent snapshot has no false create product")
check(int(sql("SELECT product_id FROM product_variants WHERE sku='QA-A2-EX-V'"))
      == existing_id, "New variant references existing parent PK")
check(rollback(existing_job).status_code == 302
      and job(existing_job)["revert_status"] == "reverted",
      "Existing parent variant safely reverted")
check(id_for("products", "sku", "QA-A2-EX-PARENT") == existing_id,
      "Existing parent never deleted")

# Post-import manual modification must fail-closed.
_, conflict_form = upload_preview(
    "manual_conflict",
    [variant("QA-A2-CON-V", "QA-A2-CON-P", "Conflicto manual",
             cat="QA-A2-CON-CAT", brand="QA-A2-CON-BRAND",
             supplier="QA-A2-CON-SUP", price=2400)])
conflict_job, _ = apply("manual_conflict", conflict_form)
check(job(conflict_job)["status"] == "finished", "Conflict fixture committed")
affected = id_for("product_variants", "sku", "QA-A2-CON-V")
affected_parent = id_for("products", "sku", "QA-A2-CON-P")
sql("UPDATE product_variants SET price=2999 WHERE id=" + str(affected))
blocked_before = counts()
blocked = rollback(conflict_job)
check(blocked.status_code == 302, "Manual edit rollback POST redirected")
check(counts() == blocked_before, "Blocked reversal retained all SQL rows")
check(int(sql(f"SELECT price FROM product_variants WHERE id={affected}")) == 2999,
      "Edited price 2999 preserved")
check(id_for("products", "sku", "QA-A2-CON-P") == affected_parent,
      "Edited job parent not removed")
check(job(conflict_job)["revert_status"] == "blocked_conflict",
      "Conflict persisted as blocked_conflict")
check(audit(conflict_job, "import_revert_blocked") >= 1,
      "Conflict denial audited")

# An even older job cannot be skipped in LIFO despite the conflict.
check(rollback(job_id).status_code == 302
      and job(job_id)["revert_status"] == "reverted",
      "Already-reverted job remains idempotent")

print("V1FA2_AUTHENTICATED_HTTP_E2E_BASE_PASS", checks, flush=True)
