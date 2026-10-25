import urllib.request, urllib.error
routes=['/','/productos','/producto?slug=qa-local-main-producto','/producto?slug=qa-local-main-oculto','/carrito','/login','/checkout','/qa-no-existe']
for route in routes:
    try:
        req=urllib.request.Request('http://127.0.0.1:18855'+route,headers={'User-Agent':'MiPesca-QA-Synthetic'})
        with urllib.request.urlopen(req,timeout=7) as r:
            body=r.read()
            print(f'{route} HTTP={r.status} length={len(body)} final={r.url}')
    except urllib.error.HTTPError as e:
        print(f'{route} HTTP={e.code} location={e.headers.get("Location","")}')
    except Exception as e:
        print(f'{route} ERROR={type(e).__name__}: {e}')
