from pathlib import Path
import csv,re,collections
root=Path(__file__).resolve().parents[2]
route_text=(root/'config/routes.php').read_text(encoding='utf-8')
routes=re.findall(r"'(GET|POST|PUT|PATCH|DELETE)\s+([^']+)'\s*=>\s*'([^']+)'",route_text)
evidence={
 'users':('scripts/dev/usuarios_http_private_qa.py','42/42 HTTP/MariaDB; reejecutado MAIN, revocacion inmediata'),
 'audit':('scripts/dev/auditoria_http_mariadb_private.py','105/105 dominio reportado; consultar docs/modulos/auditoria.md'),
 'settings':('scripts/dev/settings_http_mariadb_private.py; scripts/dev/settings_nav_focal_private.py','155/155 reportado, navegacion 132/132 repetida MAIN'),
 'proveedores':('scripts/dev/proveedores_http_mariadb_private.py','36/36 dominio privado'),
 'catalog-tags':('scripts/dev/tags_qa_http.py; scripts/dev/tags_qa_integrity.php','32/32 HTTP, 17/17 integridad'),
 'facturacion':('scripts/dev/facturacion_visual_realviews_edge.mjs','95/95 visual MAIN; NO HTTP fiscal E2E'),
 'attributes':('docs/modulos/atributos.md','25 HTTP privados documentados'),
 'brands':('docs/modulos/marcas.md','HTTP SQL privado documentado'),
 'categories':('docs/modulos/categorias.md','contratos B1/SEO documentados'),
 'imports':('scripts/dev/imports_i05_http_isolated.py','76 HTTP/SQL dominio documentado'),
 'marketing':('docs/modulos/marketing.md','E2E focal previo, limites de alcance'),
 'products':('docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md','contratos y cruces SQL, no CRUD completo'),
 'orders':('docs/CIERRE_TRANSVERSAL_TECNICO_20261022.md','contratos SQL/checkout, NO pedido real E2E'),
}
group_alias={'fiscal':'facturacion','arca':'facturacion','inventario':'products'}
sensitive=['orders','payments','pago','stock','inventario','arca','facturacion','users','role','logout','refund','webhook']
dest=root/'docs/preproduccion/INVENTARIO_RUTAS_ADMIN_DETALLADO.csv'
with dest.open('w',newline='',encoding='utf-8-sig') as f:
 w=csv.writer(f)
 w.writerow(['id','metodo','ruta','destino','grupo','operacion','requiere_auth','validar_permiso_objeto','validar_csrf','impacto_persistencia','validar_idempotencia_concurrencia','prioridad_triage','evidencia_dominio','antecedente','estado_e2e','nota'])
 for n,(method,path,target) in enumerate((x for x in routes if x[1].startswith('/admin')),1):
  bits=path.strip('/').split('/')
  group=bits[1] if len(bits)>1 else 'inicio'
  mapped=group_alias.get(group,group)
  operation='LECTURA/EXPORTACION' if method=='GET' else 'MUTACION/ACCION'
  obj= 'REVISAR' if any(x in path.lower() for x in ('/show','/edit','/delete','/role','/status','/receipt','/descargar','/ver','/shipping','/force-logout')) else 'SEGUN CONTROLADOR'
  csrf='REVISAR CSRF' if method!='GET' else 'NO PARA GET; VERIFICAR SIN ESCRITURAS'
  persistence='REVISAR' if method!='GET' else 'COMPROBAR GET SIN EFECTOS'
  critical=any(x in path.lower() for x in sensitive)
  priority='P0/P1 INVESTIGAR' if critical and method!='GET' else ('P1' if method!='GET' else 'P2')
  prior,summary=evidence.get(mapped,('SIN SUITE MAPEADA','sin evidencia asignada aun'))
  w.writerow([f'AD-{n:03d}',method,path,target,mapped,operation,'REVISAR AUTH Y ROL',obj,csrf,persistence,'SI, SI ES OPERACION CRITICA' if critical else 'SEGUN CASO',priority,prior,summary,'POR ACREDITAR E2E TRANSVERSAL','La evidencia de dominio NO acredita automaticamente esta ruta'])
print('OK',len(routes),'ADMIN',n,'GROUPS',dict(collections.Counter(x[1].split('/')[2] if len(x[1].split('/'))>2 else 'inicio' for x in routes if x[1].startswith('/admin'))))
