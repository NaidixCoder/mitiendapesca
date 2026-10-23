#!/usr/bin/env python3
"""Local-only authentic HTTP smoke with real repo controllers, auth/CSRF, MariaDB.
Private bootstrap and SQL fixtures are owned by run_checkout_http_private.ps1.
"""
import json
import os
import re
import subprocess
import sys
import time

import requests
from bs4 import BeautifulSoup

ROOT = r"C:\xampp\htdocs\mi-pesca"
PHP = r"C:\xampp\php\php.exe"
FIXTURE = ROOT + r"\scripts\dev\checkout_http_real_fixture.php"
BASE = "http://127.0.0.1:18897"
EXPECTED = {"MI_QA_DB_HOST":"127.0.0.1",
            "MI_QA_DB_PORT":"18997",
            "MI_QA_DB_NAME":"mi_checkout_http_private_qa"}
for key,value in EXPECTED.items():
    if os.getenv(key)!=value:
        sys.exit("REFUSED: private QA identity mismatch "+key)
if len(os.getenv("MI_QA_DB_PASS",""))<20 or len(os.getenv("MI_QA_HTTP_PASSWORD",""))<20:
    sys.exit("REFUSED: QA credentials missing")
checks=0
session=requests.Session()

def ck(ok,label):
    global checks
    if not ok:
        raise AssertionError(label)
    checks+=1
    print("PASS",label,flush=True)

def fixture(mode, **env_changes):
    env=os.environ.copy();env.update(env_changes)
    output=subprocess.check_output([PHP,FIXTURE,mode],env=env,text=True,timeout=20)
    return output.strip()

def snapshot():
    return json.loads(fixture("snapshot"))

def csrf_form(html):
    soup=BeautifulSoup(html,"html.parser")
    frm=soup.select_one("form.auth-login__form")
    if frm is None:
        raise AssertionError("Login HTML form missing")
    result={}
    for inp in frm.select("input[name]"):
        typ=inp.get("type","text").lower()
        if typ=="hidden":
            result[inp["name"]]=inp.get("value","")
    return result

ck(snapshot()=={"order":0,"items":0,"stock":5,"status":"none"},
   "MariaDB private baseline, no commercial records")
anonymous=requests.Session()
a=anonymous.post(BASE+"/checkout/transferencia",
                 data={"address_id":"197","_csrf":"invalid"},allow_redirects=False,timeout=15)
ck(a.status_code in (302,401,403,419),"Anonymous checkout is denied")
landing=session.get(BASE+"/login",timeout=15)
ck(landing.status_code==200,"Real GET login page")
fields=csrf_form(landing.text)
token=fields.get("_csrf") or fields.get("csrf")
ck(bool(token),"Real CSRF token present")
bad=session.post(BASE+"/login",data={"email":"comprador-checkout@example.test",
  "password":os.environ["MI_QA_HTTP_PASSWORD"],"_csrf":"invalid"},
  allow_redirects=False,timeout=15)
ck(bad.status_code==419,"Real login CSRF rejects invalid token")
landing=session.get(BASE+"/login",timeout=15)
fields=csrf_form(landing.text)
fields["email"]="comprador-checkout@example.test"
fields["password"]=os.environ["MI_QA_HTTP_PASSWORD"]
time.sleep(2.3)
good=session.post(BASE+"/login",data=fields,allow_redirects=False,timeout=20)
ck(good.status_code==302,"Real synthetic customer login 302")
sid=session.cookies.get("MIE2EPRIVATE")
ck(bool(sid),"Authenticated session cookie")
ck("PRIVATE_QA_SESSION_OK" in fixture("session",MI_QA_HTTP_SESSION_ID=sid),
   "Private free-shipping session prepared")
# Real CSRF token is tied to the authenticated session (regeneration retained).
headers={"X-CSRF-TOKEN":fields.get("_csrf",fields.get("csrf",""))}
bad_preflight=session.post(BASE+"/checkout/stock/preflight",json={"address_id":197},
    headers={"X-CSRF-TOKEN":"invalid"},timeout=20)
ck(bad_preflight.status_code==419,"Real preflight CSRF rejects altered token")
bad_addr=session.post(BASE+"/checkout/stock/preflight",json={"address_id":999},
    headers=headers,timeout=20)
ck(bad_addr.status_code==422,"Real preflight rejects alien address")
available=session.post(BASE+"/checkout/stock/preflight",json={"address_id":197},
    headers=headers,timeout=20)
ck(available.status_code==200 and available.json().get("ok") is True,
   "Real CheckoutStockPreflightService, MariaDB stock OK")
fixture("hide")
hidden=session.post(BASE+"/checkout/stock/preflight",json={"address_id":197},
    headers=headers,timeout=20)
ck(hidden.status_code==422 and hidden.json().get("ok") is False,
   "Real preflight rejects inactive product")
fixture("show")
fixture("stock_low")
low=session.post(BASE+"/checkout/stock/preflight",json={"address_id":197},
    headers=headers,timeout=20)
ck(low.status_code==409 and low.json().get("code")=="stock_changed",
   "Real preflight rejects insufficient stock")
fixture("stock_restore")
fixture("cart_restore")
available2=session.post(BASE+"/checkout/stock/preflight",json={"address_id":197},
    headers=headers,timeout=20)
ck(available2.status_code==200,"Preflight recovers with stock restored")
order=session.post(BASE+"/checkout/transferencia",
    data={"address_id":"197","_csrf":fields.get("_csrf",fields.get("csrf",""))},
    allow_redirects=False,timeout=30)
ck(order.status_code==302,
    "Authenticated transfer confirmation returns redirect")
confirmed=snapshot()
ck(confirmed["order"]==1 and confirmed["items"]==1 and confirmed["stock"]==3,
   "Real order, order item and stock 5 to 3 persisted")
ck(confirmed["status"]=="pending_payment",
   "Transfer order remains pending payment")
# Repeated submission must NEVER create a second order or decrement stock again.
replay=session.post(BASE+"/checkout/transferencia",
    data={"address_id":"197","_csrf":fields.get("_csrf",fields.get("csrf",""))},
    allow_redirects=False,timeout=30)
after=snapshot()
ck(after==confirmed,"HTTP replay leaves single order and stock unchanged")
ck(replay.status_code in (302,409,422),
   "HTTP replay has bounded client response")
print("CHECKOUT_AUTHENTIC_HTTP_MARIADB_PASS",checks,flush=True)
