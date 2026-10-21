from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/admin/product_images/preview.js'
s=p.read_text(encoding='utf8')
assert s.count(' grid av3-grid-cols-2')==2
s=s.replace(' grid av3-grid-cols-2',' av3-grid av3-grid-cols-2')
assert s.count("classList.contains('grid')")==1
s=s.replace("classList.contains('grid')","classList.contains('av3-grid')")
p.write_text(s,encoding='utf8')
p=r/'public_html/assets/js/admin/product_variants/state.js'
s=p.read_text(encoding='utf8')
assert "el.className = 'fixed inset-0 z-[80] hidden';" in s
s=s.replace("el.className = 'fixed inset-0 z-[80] hidden';","el.className = 'av3-variant-wait-overlay is-hidden';")
s=s.replace("overlayEl.classList.remove('hidden')","overlayEl.classList.remove('is-hidden')")
s=s.replace("overlayEl.classList.add('hidden')","overlayEl.classList.add('is-hidden')")
p.write_text(s,encoding='utf8')
css=r/'public_html/assets/css/admin-v3/components/common.css'
with css.open('a',encoding='utf8') as f:
 f.write('\n/* Product variant blocking overlay: state is owned by JS. */\nbody[data-page^="admin"] .av3-variant-wait-overlay{position:fixed;inset:0;z-index:80}\nbody[data-page^="admin"] .av3-variant-wait-overlay.is-hidden{display:none}\n')
print('GRID_AND_VARIANT_OVERLAY_MIGRATED')
