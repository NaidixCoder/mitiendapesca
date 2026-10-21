from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/js/tracking/recent_searches.js'
s=p.read_text(encoding='utf8')
old="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-[color:var(--fg)] hover:border-gray-300 hover:bg-gray-50"
assert s.count(old)==1
p.write_text(s.replace(old,'site-recent-search-chip'),encoding='utf8')
q=r/'public_html/assets/css/v2/components/no-tailwind-public.css'
with q.open('a',encoding='utf8') as f:
 f.write('\n.site-recent-search-chip{display:inline-flex;align-items:center;border:1px solid #e5e7eb;border-radius:999px;background:#fff;padding:4px 12px;font-size:12px;font-weight:500;color:var(--fg);text-decoration:none}\n.site-recent-search-chip:hover{border-color:#d1d5db;background:#f9fafb}\n')
print('RECENT_SEARCHES_MIGRATED')
