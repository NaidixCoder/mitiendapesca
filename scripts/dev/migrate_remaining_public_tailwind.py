from pathlib import Path
R=Path(r'C:\xampp\htdocs\mi-pesca')
f=R/'app/Views/partials/flash_inline.php'
s=f.read_text(encoding='utf8')
s=s.replace("'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-900'","'ok' => 'is-ok'").replace("'warn' => 'border-amber-200 bg-amber-50 text-amber-900'","'warn' => 'is-warn'").replace("'border-rose-200 bg-rose-50 text-rose-900'","'is-error'")
s=s.replace('class="space-y-2" data-inline-flash','class="site-inline-flash" data-inline-flash')
s=s.replace('class="rounded-xl border px-4 py-3 text-sm <?= e($tone((string)$it[\'type\'])) ?>"','class="site-inline-flash__item <?= e($tone((string)$it[\'type\'])) ?>"')
f.write_text(s,encoding='utf8')
f=R/'app/Views/partials/catalogo/active-filter.php';s=f.read_text(encoding='utf8')
s=s.replace('class="mb-3"','class="site-active-filters"').replace('class="flex flex-wrap items-center gap-2"','class="site-active-filters__row"')
s=s.replace('class="text-sm text-[color:var(--fg)]"','class="site-active-filters__label"')
s=s.replace('class="group inline-flex items-center gap-2 max-w-full\n                rounded-full border border-[color:var(--border)] bg-[color:var(--surface)]\n                px-3 py-1 text-sm text-[color:var(--text)]\n                hover:border-[color:var(--brand)]/40 hover:bg-[color:var(--brand)]/5 transition"','class="site-active-filters__chip"')
s=s.replace('class="truncate"','class="site-active-filters__truncate"')
s=s.replace('class="inline-flex items-center justify-center w-5 h-5 rounded-full\n                    border border-[color:var(--border)] text-[color:var(--fg)]\n                    group-hover:border-[color:var(--brand)]/40"','class="site-active-filters__remove"')
s=s.replace('class="ml-1 text-sm text-[color:var(--brand)] hover:underline"','class="site-active-filters__clear"')
f.write_text(s,encoding='utf8')
f=R/'app/Views/pages/auth/reset_password.php';s=f.read_text(encoding='utf8')
d={'container mx-auto px-4 py-10':'site-reset','max-w-lg mx-auto':'site-reset__inner','card p-6 md:p-8':'card site-reset__card','mb-6':'site-reset__heading','text-2xl font-semibold':'site-reset__title','text-sm text-[color:var(--fg-muted)] mt-2':'site-reset__hint','font-medium':'site-reset__account','space-y-4':'site-reset__form','input w-full':'input site-reset__field','btn btn-brand w-full':'btn btn-brand site-reset__submit','text-sm text-center text-[color:var(--fg-muted)]':'site-reset__return'}
for a,b in d.items():s=s.replace('class="'+a+'"','class="'+b+'"')
f.write_text(s,encoding='utf8')
css=R/'public_html/assets/css/v2/components/no-tailwind-public.css'
css.write_text('''/* Public replacements for former Tailwind markup. */
.site-inline-flash{display:grid;gap:8px}
.site-inline-flash__item{border:1px solid;border-radius:12px;padding:12px 16px;font-size:14px}
.site-inline-flash__item.is-ok{border-color:#a7f3d0;background:#ecfdf5;color:#064e3b}
.site-inline-flash__item.is-warn{border-color:#fcd34d;background:#fffbeb;color:#78350f}
.site-inline-flash__item.is-error{border-color:#fecdd3;background:#fff1f2;color:#881337}
.site-active-filters{margin-bottom:12px}
.site-active-filters__row{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.site-active-filters__label{font-size:14px;color:var(--fg)}
.site-active-filters__chip{display:inline-flex;align-items:center;gap:8px;max-width:100%;border-radius:999px;border:1px solid var(--border);background:var(--surface);padding:4px 12px;font-size:14px;color:var(--text);text-decoration:none;transition:background .2s,border-color .2s}
.site-active-filters__chip:hover{border-color:var(--brand);background:color-mix(in srgb,var(--brand) 5%,var(--surface))}
.site-active-filters__truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.site-active-filters__remove{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;border:1px solid var(--border);color:var(--fg)}
.site-active-filters__chip:hover .site-active-filters__remove{border-color:var(--brand)}
.site-active-filters__clear{margin-left:4px;font-size:14px;color:var(--brand)}
.site-active-filters__clear:hover{text-decoration:underline}
.site-reset{width:100%;max-width:100%;margin-inline:auto;padding:40px 16px}
.site-reset__inner{max-width:512px;margin-inline:auto}
.site-reset__card{padding:24px}
.site-reset__heading{margin-bottom:24px}
.site-reset__title{font-size:24px;line-height:1.3;font-weight:600}
.site-reset__hint{margin-top:8px;font-size:14px;color:var(--fg-muted)}
.site-reset__account{font-weight:500}
.site-reset__form{display:grid;gap:16px}
.site-reset__field,.site-reset__submit{width:100%}
.site-reset__return{text-align:center;font-size:14px;color:var(--fg-muted)}
@media(min-width:768px){.site-reset__card{padding:32px}}
''',encoding='utf8')
h=R/'app/Views/partials/head.php';t=h.read_text(encoding='utf8')
old="    'css/v2/components/buttons.css',"
assert t.count(old)==2
# add once to each of admin/public blocks, preserving shared flash usage
t=t.replace(old,old+"\n    'css/v2/components/no-tailwind-public.css',")
h.write_text(t,encoding='utf8')
print('PUBLIC_TEMPLATES_MIGRATED')
