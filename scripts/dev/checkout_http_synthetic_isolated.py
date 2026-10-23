#!/usr/bin/env python3
"""Checkout HTTP *synthetic* integration. No commercial DB, .env, real MP or email.
Uses actual CheckoutController, PaymentController, CheckoutOrderService and
CheckoutStockPreflightService; adapters for auth/CSRF and persistence are test-only.
"""
import http.client
import json
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.parse

ROOT = Path(__file__).resolve().parents[2]
PORT = 18898
HOST = "127.0.0.1"
QA_DIR = Path(tempfile.gettempdir()) / "mi_checkout_http_qa_20261022"
QA_DB = QA_DIR / "sqlite.db"
PHP = Path(r"C:\xampp\php\php.exe")
ROUTER = ROOT / "scripts" / "dev" / "checkout_http_private_router.php"
checks = 0
cookie = ""
csrf = ""

def ck(condition, label):
    global checks
    if not condition:
        raise AssertionError(label)
    checks += 1
    print("PASS", label, flush=True)

def request(method, path, payload=None, authorized=True):
    global cookie
    headers = {}
    if authorized and cookie:
        headers["Cookie"] = cookie
    if payload is None:
        body = None
    elif isinstance(payload, dict) and payload.get("_format") == "form":
        data = {k:v for k,v in payload.items() if k != "_format"}
        body = urllib.parse.urlencode(data)
        headers["Content-Type"] = "application/x-www-form-urlencoded"
    else:
        body = json.dumps(payload).encode("utf-8")
        headers["Content-Type"] = "application/json"
    if isinstance(body, str):
        body = body.encode("utf-8")
    connection = http.client.HTTPConnection(HOST, PORT, timeout=15)
    try:
        connection.request(method,path,body,headers)
        res = connection.getresponse()
        data = res.read().decode("utf-8",errors="replace")
        set_cookie = res.getheader("Set-Cookie")
        if set_cookie and authorized:
            cookie = set_cookie.split(";",1)[0]
        return res.status, res.getheader("Location",""), data
    finally:
        connection.close()

def j(data):
    return json.loads(data)

def check_port_free():
    with socket.socket(socket.AF_INET,socket.SOCK_STREAM) as conn:
        return conn.connect_ex((HOST,PORT)) != 0

def run():
    global csrf
    ck(request("POST","/checkout/transferencia",
               {"_format":"form","address_id":"9"},authorized=False)[0] == 302,
       "Unauthenticated transfer HTTP rejected")
    ck(request("GET","/qa/init")[0] == 200,"Synthetic SQLite tables created")
    code,_,body=request("GET","/qa/login")
    csrf=j(body)["csrf"]
    ck(code == 200 and len(csrf) >= 32 and bool(cookie),"Synthetic session established")
    ck(request("POST","/checkout/transferencia",
               {"_format":"form","address_id":"9","_csrf":"bad"})[0] == 419,
       "Transfer CSRF rejects invalid token")
    ck(request("POST","/checkout/stock/preflight",
               {"address_id":9})[0] == 419,
       "Preflight CSRF rejects missing token")
    status,_,body=request("POST","/checkout/stock/preflight",
                          {"address_id":77},True)
    # CSRF is sent as custom header only in the dedicated request below.
    ck(status == 419,"Preflight does not silently accept unverified token")
    # Use the X-CSRF-TOKEN header for JSON, as in the client API.
    def preflight(address=9):
        headers={"Cookie":cookie,"Content-Type":"application/json",
                 "X-CSRF-TOKEN":csrf}
        conn=http.client.HTTPConnection(HOST,PORT,timeout=15)
        try:
            conn.request("POST","/checkout/stock/preflight",
                         json.dumps({"address_id":address}),headers)
            res=conn.getresponse()
            return res.status, json.loads(res.read().decode("utf-8"))
        finally:
            conn.close()
    status, body=preflight(77)
    ck(status == 422 and body["code"] == "invalid_address","Invalid checkout address HTTP 422")
    status, body=preflight(9)
    ck(status == 200 and body["ok"],"Actual preflight service accepts available stock")
    request("GET","/qa/hide?value=1")
    status,body=preflight(9)
    ck(status == 422 and body["code"] == "b1_unavailable","Preflight rejects synthetic hidden item")
    request("GET","/qa/hide?value=0")
    request("GET","/qa/stock?value=1")
    status,body=preflight(9)
    ck(status == 409 and body["code"] == "stock_changed","Preflight HTTP detects shortage")
    request("GET","/qa/stock?value=5")
    status,location,_=request("POST","/checkout/transferencia",
            {"_format":"form","address_id":"9","_csrf":csrf})
    ck(status == 302 and "id=1" in location,
       "Transfer HTTP redirects to newly created order")
    status,_,body=request("GET","/qa/state")
    state=j(body)
    ck(status == 200 and state == {"orders":1,"items":1,"stock":3},
       "Actual CheckoutOrderService persisted single order and stock decrement")
    status,location,_=request("POST","/checkout/transferencia",
            {"_format":"form","address_id":"9","_csrf":csrf})
    ck(status == 302 and "id=1" in location,"Repeat transfer returns original order")
    state=j(request("GET","/qa/state")[2])
    ck(state == {"orders":1,"items":1,"stock":3},
       "Idempotency prevents double order/stock decrement")
    status,location,_=request("POST","/checkout/confirmar",
           {"_format":"form","_csrf":csrf})
    ck(status == 302 and location == "/checkout",
       "Legacy confirm route cannot create order")
    ck(j(request("GET","/qa/state")[2])["orders"] == 1,
       "Legacy confirm leaves order state unchanged")
    request("GET","/qa/logout")
    ck(request("POST","/checkout/transferencia",
            {"_format":"form","address_id":"9","_csrf":csrf})[0] == 302,
       "Logged-out transfer rejected")
    print("[checkout-http-synthetic] OK",checks,"checks",flush=True)

def main():
    if not PHP.is_file() or not ROUTER.is_file():
        raise SystemExit("REFUSED: missing PHP or QA router")
    if not check_port_free() or QA_DIR.exists():
        raise SystemExit("REFUSED: QA port busy or directory already exists")
    QA_DIR.mkdir(mode=0o700)
    env=os.environ.copy()
    for key in list(env):
        if key.startswith(("DB_","MP_","MAIL_")):
            env.pop(key,None)
    env["MI_QA_HTTP_SQLITE"]=str(QA_DB)
    server=None
    try:
        with (QA_DIR/"server.log").open("wb") as log:
            server=subprocess.Popen([str(PHP),"-S",f"{HOST}:{PORT}",
                 "-t",str(ROOT/"public_html"),str(ROUTER)],
                 cwd=str(ROOT),env=env,stdout=log,stderr=subprocess.STDOUT)
            for _ in range(60):
                if server.poll() is not None:
                    raise RuntimeError("QA server exited early")
                if not check_port_free():
                    break
                time.sleep(0.1)
            else:
                raise RuntimeError("QA server never started")
            run()
    except Exception:
        if (QA_DIR/"server.log").exists():
            print("[QA] Private server log tail:",flush=True)
            print((QA_DIR/"server.log").read_text(errors="replace")[-1600:],flush=True)
        raise
    finally:
        if server is not None and server.poll() is None:
            server.terminate()
            try:
                server.wait(timeout=8)
            except subprocess.TimeoutExpired:
                server.kill();server.wait(timeout=5)
        shutil.rmtree(QA_DIR)
        if not check_port_free():
            raise RuntimeError("Private HTTP QA port still busy")
        print("[checkout-http-synthetic] Cleaned private server, port and SQLite",flush=True)

if __name__ == "__main__":
    main()
