from pathlib import Path
p=Path(r'C:\xampp\htdocs\mi-pesca\scripts\dev\audit_js_tailwind_classes.py')
s=p.read_text(encoding='utf8')
s=s.replace("r'\\b(?:flex|","r'(?<![\\\\w-])(?:flex|")
# fix regex now r'(?<![\\w-])' meaning two backslashes? print to check via python
p.write_text(s,encoding='utf8')
print(s.splitlines()[8])
