from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca'); v=r/'app/Views/partials/admin/dashboard'
for file in ['featured_products.php','top_clients_30.php']:
 p=v/file;s=p.read_text(encoding='utf8')
 s=s.replace('<section >','<section class="av3-panel av3-ranking">',1).replace('<header >','<header class="av3-panel__heading">',1)
 s=s.replace('<a  href=','<a class="av3-action-link" href=')
 s=s.replace('<p >','<p class="av3-empty">')
 s=s.replace('<div >','<div class="av3-ranking__body">',1)
 s=s.replace('<div >','<div class="av3-ranking__scroll">',1)
 s=s.replace('<table >','<table class="av3-ranking__table">',1)
 p.write_text(s,encoding='utf8')
css=r/'public_html/assets/css/admin-v3/pages/dashboard.css'
s=css.read_text(encoding='utf8')
s+='\n/* Rankings comerciales condicionales */\nbody[data-page^="admin"] .av3-ranking__body{min-width:0;max-width:100%}\nbody[data-page^="admin"] .av3-ranking__scroll{min-width:0;max-width:100%;overflow-x:auto}\nbody[data-page^="admin"] .av3-ranking__table{width:100%;border-collapse:collapse;font-size:13px}\nbody[data-page^="admin"] .av3-ranking__table th{font-weight:500;text-align:left;color:#647786;background:#F7F9FA;padding:10px 12px;border-bottom:1px solid #E8EDF0}\nbody[data-page^="admin"] .av3-ranking__table td{padding:10px 12px;border-bottom:1px solid #EFF2F4;color:#304956}\nbody[data-page^="admin"] .av3-ranking__table tr:last-child td{border-bottom:0}\n'
css.write_text(s,encoding='utf8')
print('RANKING_PARTIALS_MIGRATED')
