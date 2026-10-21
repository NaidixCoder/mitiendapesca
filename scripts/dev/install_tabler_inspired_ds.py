from pathlib import Path
r=Path(r'C:\xampp\htdocs\mi-pesca')
p=r/'public_html/assets/css/admin-v3/components/design-system.css'
p.write_text("""/* MiTiendaPesca Admin UI contract. Patterns inspired by Tabler UI (MIT); implemented independently. */
/* Opt-in components only. No global element overrides and no sidebar selectors. */
body[data-page^="admin"] {
  --av3-ui-font: "Segoe UI", Inter, system-ui, sans-serif;
  --av3-ui-primary:#006b6b;
  --av3-ui-primary-hover:#005657;
  --av3-ui-ink:#182b39;
  --av3-ui-subtle:#647786;
  --av3-ui-border:#dfe6e9;
  --av3-ui-bg:#f6f8fa;
  --av3-ui-white:#fff;
  --av3-ui-radius:10px;
  --av3-ui-radius-lg:12px;
  --av3-ui-shadow:0 1px 3px rgba(21,42,54,.055);
  --av3-ui-height:38px;
}
body[data-page^="admin"] .av3-ui-page{min-width:0;font-family:var(--av3-ui-font);color:var(--av3-ui-ink)}
body[data-page^="admin"] .av3-ui-page-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:20px}
body[data-page^="admin"] .av3-ui-title{margin:0;font-size:24px;line-height:1.2;letter-spacing:-.025em;font-weight:700}
body[data-page^="admin"] .av3-ui-subtitle{margin:6px 0 0;color:var(--av3-ui-subtle);font-size:13px;line-height:1.5}
body[data-page^="admin"] .av3-ui-card{min-width:0;background:var(--av3-ui-white);border:1px solid var(--av3-ui-border);border-radius:var(--av3-ui-radius-lg);box-shadow:var(--av3-ui-shadow);overflow:hidden}
body[data-page^="admin"] .av3-ui-card-header{padding:16px 18px;border-bottom:1px solid #e9eef0;display:flex;align-items:center;justify-content:space-between;gap:12px}
body[data-page^="admin"] .av3-ui-card-title{margin:0;font-size:14px;line-height:1.4;font-weight:700}
body[data-page^="admin"] .av3-ui-card-body{padding:18px}
body[data-page^="admin"] .av3-ui-actions{display:flex;align-items:center;flex-wrap:wrap;gap:8px}
body[data-page^="admin"] .av3-ui-btn{appearance:none;display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:var(--av3-ui-height);padding:8px 13px;border:1px solid var(--av3-ui-border);border-radius:8px;background:#fff;color:#273f4e;font-family:inherit;font-size:13px;font-weight:600;line-height:1.25;text-decoration:none;cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s}
body[data-page^="admin"] .av3-ui-btn:hover{background:#f5f8f9;border-color:#aebfc6;text-decoration:none}
body[data-page^="admin"] .av3-ui-btn--primary{background:var(--av3-ui-primary);border-color:var(--av3-ui-primary);color:white}
body[data-page^="admin"] .av3-ui-btn--primary:hover{background:var(--av3-ui-primary-hover);border-color:var(--av3-ui-primary-hover);color:white}
body[data-page^="admin"] .av3-ui-btn--danger{border-color:#fecaca;background:#fff7f7;color:#b42318}
body[data-page^="admin"] .av3-ui-btn--danger:hover{background:#fef0f0;border-color:#fda29b}
body[data-page^="admin"] .av3-ui-btn--small{min-height:32px;padding:6px 10px;font-size:12px}
body[data-page^="admin"] .av3-ui-btn:focus-visible,body[data-page^="admin"] .av3-ui-control:focus-visible{outline:2px solid #006b6b;outline-offset:2px}
body[data-page^="admin"] .av3-ui-btn:disabled{opacity:.52;cursor:not-allowed}
body[data-page^="admin"] .av3-ui-field{display:flex;flex-direction:column;gap:6px;min-width:0}
body[data-page^="admin"] .av3-ui-field-label{font-size:12px;font-weight:650;color:#384e5b}
body[data-page^="admin"] .av3-ui-field-hint{font-size:11px;color:var(--av3-ui-subtle)}
body[data-page^="admin"] .av3-ui-control{display:block;width:100%;min-width:0;min-height:var(--av3-ui-height);padding:8px 11px;border:1px solid #cbd7dc;border-radius:8px;background:#fff;color:var(--av3-ui-ink);font-family:inherit;font-size:13px}
body[data-page^="admin"] .av3-ui-control:disabled{background:#f2f5f6;color:#84949d}
body[data-page^="admin"] .av3-ui-filters{display:flex;align-items:flex-end;flex-wrap:wrap;gap:10px}
body[data-page^="admin"] .av3-ui-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 9px;border:1px solid #d9e5e5;border-radius:999px;background:#f1f8f6;color:#006b6b;font-size:11px;font-weight:650;line-height:1.3}
body[data-page^="admin"] .av3-ui-badge--neutral{color:#556b78;background:#f3f6f7;border-color:#e1e8eb}
body[data-page^="admin"] .av3-ui-badge--warning{color:#92400e;background:#fff8e7;border-color:#f6dea3}
body[data-page^="admin"] .av3-ui-badge--danger{color:#9b2c2c;background:#fff0f0;border-color:#facaca}
body[data-page^="admin"] .av3-ui-table-scroll{width:100%;overflow-x:auto}
body[data-page^="admin"] .av3-ui-table{width:100%;border-collapse:collapse;text-align:left;font-size:13px}
body[data-page^="admin"] .av3-ui-table th{font-size:11px;text-transform:uppercase;letter-spacing:.035em;font-weight:650;color:#748794;background:#f7f9fa;padding:11px 14px;border-bottom:1px solid #e3ebee}
body[data-page^="admin"] .av3-ui-table td{padding:12px 14px;line-height:1.4;color:#304958;border-bottom:1px solid #ebf0f2}
body[data-page^="admin"] .av3-ui-table tbody tr:last-child td{border-bottom:0}
body[data-page^="admin"] .av3-ui-table tbody tr:hover{background:#fafcfc}
body[data-page^="admin"] .av3-ui-empty{padding:26px;color:var(--av3-ui-subtle);text-align:center;font-size:13px}
body[data-page^="admin"] .av3-ui-divider{height:1px;background:#e7ecef}
@media(max-width:700px){body[data-page^="admin"] .av3-ui-card-header{padding:14px}body[data-page^="admin"] .av3-ui-card-body{padding:14px}body[data-page^="admin"] .av3-ui-title{font-size:22px}}
""",encoding='utf8')
head=r/'app/Views/partials/head.php'
s=head.read_text(encoding='utf8')
anchor="    'css/admin-v3/components/common.css',"
assert s.count(anchor)==1
head.write_text(s.replace(anchor,anchor+"\n    'css/admin-v3/components/design-system.css',"),encoding='utf8')
print('DESIGN_SYSTEM_CREATED')
