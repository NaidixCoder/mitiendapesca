from pathlib import Path
import re,csv,collections
root=Path(r"C:\xampp\htdocs\mi-pesca")
source=(root/"config/routes.php").read_text(encoding="utf-8")
routes=re.findall(r"'(GET|POST|PUT|PATCH|DELETE)\s+([^']+)'\s*=>\s*'([^']+)'",source)
out=root/"docs/preproduccion/INVENTARIO_RUTAS_ADMIN.csv"
with out.open("w",newline="",encoding="utf-8-sig") as f:
 w=csv.writer(f);w.writerow(["metodo","ruta","destino","grupo_preliminar","riesgo_inicial","cobertura","evidencia","pendiente"])
 for method,path,target in routes:
  if not path.startswith("/admin"):continue
  parts=path.strip("/").split("/")
  group=parts[1] if len(parts)>1 else "inicio"
  sensitive=("orders","payments","checkout","users","facturacion","settings","stock","inventory","refund","products","compras")
  risk="P1 revisar" if method!="GET" or any(x in path.lower() for x in sensitive) else "P2 revisar"
  w.writerow([method,path,target,group,risk,"POR MAPEAR","", "Confirmar permisos, CSRF, persistencia y escenario E2E"])
print("GENERADO",out,"ADMIN_ROUTES",sum(x[1].startswith("/admin") for x in routes),"UNIQUE_PATHS",len(set(x[1] for x in routes if x[1].startswith("/admin"))))
