from pathlib import Path
import datetime,zipfile,json
r=Path(r'C:\xampp\htdocs\mi-pesca')
note='\n## Limpieza de CSS públicos y agregadores V2 — '+datetime.date.today().isoformat()+'\n- La hoja de edición de direcciones, antes cargada desde AddressesController.php en css/v2/pages/public, se migró a css/public/pages/account-address-edit.css y se actualizó su controlador.\n- Se eliminaron los agregadores sin consumidores: css/v2/index.css, css/v2/admin.css, css/v2/public.css. Se dejó copia en C:\\xampp\\mi-pesca-backups.\n- No se modificaron controladores salvo la ruta estática de CSS; sin cambios de negocio.\n- Se mantiene pendiente revisar visualmente /public y admin, así como retirar compatibilidad administrativa cuando sus consumidores estén migrados.\n'
for fname in ['MANIFEST.md','PLAN_CSS_UNIFICADO.md']:
 with (r/fname).open('a',encoding='utf8') as f:f.write(note)
mapping=json.loads((r/'docs/REUBICACION_CSS_PUBLICO.json').read_text(encoding='utf8'))
mapping['css/v2/pages/public/account-address-edit.css']='css/public/pages/account-address-edit.css'
(r/'docs/REUBICACION_CSS_PUBLICO.json').write_text(json.dumps(mapping,ensure_ascii=False,indent=2),encoding='utf8')
files=['MANIFEST.md','PLAN_CSS_UNIFICADO.md','app/Views/partials/head.php','app/Controllers/Account/AddressesController.php','docs/REUBICACION_CSS_PUBLICO.json','scripts/build/gate.php','scripts/build/css_unification_contract.php','scripts/build/admin_css_scope.php','scripts/dev/move_public_css_to_domain.py','scripts/dev/move_address_edit_css.py','scripts/dev/cleanup_v2_aggregators.py','scripts/dev/remove_v2_loader_names.py']
files+=['public_html/assets/'+target for target in mapping.values()]
files+=['public_html/assets/css/shared/reset.css','public_html/assets/css/shared/tokens.css','public_html/assets/css/shared/base.css','public_html/assets/css/shared/utilities.css','public_html/assets/css/shared/components/interaction.css','public_html/assets/css/shared/components/ui.css','public_html/assets/css/shared/components/buttons.css']
files=list(dict.fromkeys(files))
old=['public_html/assets/'+x for x in mapping.keys()]+['public_html/assets/css/v2/index.css','public_html/assets/css/v2/admin.css','public_html/assets/css/v2/public.css']
out=r/'CSS_UNIFICACION_PUBLICO_ADMIN_INCREMENTAL.zip'
with zipfile.ZipFile(out,'w',zipfile.ZIP_DEFLATED) as z:
 for file in files:
  assert (r/file).is_file(),file
  z.write(r/file,file)
 z.writestr('DELETIONS.txt','\n'.join(old)+'\n')
with zipfile.ZipFile(out) as z:print('UPDATED_CSS_MIGRATION_ZIP',len(z.namelist()),out.stat().st_size,'valid',z.testzip() is None)
