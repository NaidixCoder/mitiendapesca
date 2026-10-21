from pathlib import Path
import datetime,zipfile
r=Path(r'C:\xampp\htdocs\mi-pesca');h=r/'app/Views/partials/head.php'
s=h.read_text(encoding='utf8')
assert s.count('<script src="https://cdn.tailwindcss.com"></script>')==1
assert s.count("    'css/v2/tokens.css',")==2
reset=r/'public_html/assets/css/shared/reset.css';reset.parent.mkdir(parents=True,exist_ok=True)
reset.write_text('''/* Project-owned normalization replacing Tailwind Preflight. No Tailwind runtime required. */
*,::before,::after{box-sizing:border-box;border-width:0;border-style:solid;border-color:#e5e7eb}
html{line-height:1.5;-webkit-text-size-adjust:100%;tab-size:4}
body{margin:0;line-height:inherit}
hr{height:0;color:inherit;border-top-width:1px}
abbr:where([title]){text-decoration:underline dotted}
h1,h2,h3,h4,h5,h6{font-size:inherit;font-weight:inherit}
a{color:inherit;text-decoration:inherit}
b,strong{font-weight:bolder}
code,kbd,samp,pre{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:1em}
small{font-size:80%}
sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}
sub{bottom:-.25em}sup{top:-.5em}
table{text-indent:0;border-color:inherit;border-collapse:collapse}
button,input,optgroup,select,textarea{font-family:inherit;font-feature-settings:inherit;font-variation-settings:inherit;font-size:100%;font-weight:inherit;line-height:inherit;letter-spacing:inherit;color:inherit;margin:0;padding:0}
button,select{text-transform:none}
button,[type=button],[type=reset],[type=submit]{-webkit-appearance:button;background-color:transparent;background-image:none}
button{cursor:pointer}
:-moz-focusring{outline:auto}
progress{vertical-align:baseline}
::-webkit-inner-spin-button,::-webkit-outer-spin-button{height:auto}
[type=search]{-webkit-appearance:textfield;outline-offset:-2px}
::-webkit-search-decoration{-webkit-appearance:none}
::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}
summary{display:list-item}
blockquote,dl,dd,h1,h2,h3,h4,h5,h6,hr,figure,p,pre{margin:0}
fieldset{margin:0;padding:0}
legend{padding:0}
ol,ul,menu{list-style:none;margin:0;padding:0}
dialog{padding:0}
textarea{resize:vertical}
input::placeholder,textarea::placeholder{opacity:1;color:#9ca3af}
button,[role=button]{cursor:pointer}
:disabled{cursor:default}
img,svg,video,canvas,audio,iframe,embed,object{display:block;vertical-align:middle}
img,video{max-width:100%;height:auto}
[hidden]{display:none!important}
''',encoding='utf8')
s=s.replace('  <script src="https://cdn.tailwindcss.com"></script>\n','')
s=s.replace("    'css/v2/tokens.css',","    'css/shared/reset.css',\n    'css/v2/tokens.css',")
h.write_text(s,encoding='utf8')
print('TAILWIND_CDN_REMOVED_RESET_ACTIVE')
