#!/usr/bin/env python3
"""Two independent PHP HTTP servers, two authenticated sessions, one private MariaDB.
Must run ONLY under scripts/dev/run_checkout_http_private.ps1 in scratch environment.
"""
import concurrent.futures
import json
import os
from pathlib import Path
import subprocess
import threading
import time
import requests
from bs4 import BeautifulSoup

BASE_A="http://127.0.0.1:18897"
BASE_B="http://127.0.0.1:18896"
FIXTURE=Path(r"C:\xampp\htdocs\mi-pesca\scripts\dev\checkout_http_real_fixture.php")
EXPECTED={"MI_QA_DB_HOST":"127.0.0.1","MI_QA_DB_PORT":"18997",
          "MI_QA_DB_NAME":"mi_checkout_http_private_qa"}
for k,v in EXPECTED.items():
    if os.getenv(k)!=v:
        raise RuntimeError("REFUSED private runner env mismatch "+k)
if len(os.getenv("MI_QA_DB_PASS",""))<20 or len(os.getenv("MI_QA_HTTP_PASSWORD",""))<20:
    raise RuntimeError("REFUSED: missing synthetic credentials")

def db(mode):
    return subprocess.check_output([r"C:\xampp\php\php.exe",str(FIXTURE),mode],
            timeout=30,text=True).strip()

def login(base):
    s=requests.Session()
    res=s.get(base+"/login",timeout=20)
    if res.status_code!=200:
        raise RuntimeError("QA login page not available")
    soup=BeautifulSoup(res.text,"html.parser")
    form=soup.select_one("form.auth-login__form")
    if form is None:
        raise RuntimeError("QA auth form missing")
    data={x["name"]:x.get("value","") for x in form.select("input[name]")
          if x.get("type")=="hidden"}
    token=data.get("_csrf",data.get("csrf",""))
    if not token: raise RuntimeError("Missing genuine CSRF token")
    data.update(email="comprador-checkout@example.test",
                password=os.environ["MI_QA_HTTP_PASSWORD"])
    time.sleep(2.2)
    result=s.post(base+"/login",data=data,allow_redirects=False,timeout=20)
    if result.status_code!=302 or not s.cookies.get("MIE2EPRIVATE"):
        raise RuntimeError("Synthetic real login failed")
    return s,token

db("race_reset")
initial=json.loads(db("snapshot"))
if initial!={"order":0,"items":0,"stock":5,"status":"none"}:
    raise AssertionError("Race baseline not fresh "+str(initial))
s1,csrf1=login(BASE_A)
s2,csrf2=login(BASE_B)
if s1.cookies.get("MIE2EPRIVATE")==s2.cookies.get("MIE2EPRIVATE"):
    raise AssertionError("Sessions must have distinct cookies")
# Attach shipment state to each genuine authenticated session, independently.
for s in (s1,s2):
    sid=s.cookies.get("MIE2EPRIVATE")
    if subprocess.check_output(
       [r"C:\xampp\php\php.exe",str(FIXTURE),"session"],
       env={**os.environ,"MI_QA_HTTP_SESSION_ID":sid},text=True).strip()!="PRIVATE_QA_SESSION_OK":
        raise RuntimeError("Cannot initialize private shipping")
barrier=threading.Barrier(2)
def submit(session,csrf,base):
    barrier.wait(timeout=10)
    response=session.post(base+"/checkout/transferencia",
                          data={"address_id":"197","_csrf":csrf},
                          allow_redirects=False,timeout=40)
    return response.status_code,response.headers.get("Location","")
with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
    first=pool.submit(submit,s1,csrf1,BASE_A)
    second=pool.submit(submit,s2,csrf2,BASE_B)
    answers=[first.result(timeout=50),second.result(timeout=50)]
state=json.loads(db("snapshot"))
if state!={"order":1,"items":1,"stock":3,"status":"pending_payment"}:
    raise AssertionError("FAIL concurrent order / inventory integrity "+json.dumps({"responses":answers,"db":state}))
print("PASS independent authenticated sessions and parallel HTTP requests",flush=True)
print("PASS single committed order, one item, stock decremented once",flush=True)
print("PASS no duplicate order/oversell despite concurrent submissions",flush=True)
print("CHECKOUT_REAL_HTTP_CONCURRENCY_PASS 3",flush=True)
