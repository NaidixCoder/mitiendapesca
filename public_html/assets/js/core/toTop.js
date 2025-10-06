export function init(){
  const btn = document.getElementById('toTop');
  if (!btn) return;
  btn.addEventListener('click', () => { window.scrollTo({top:0, behavior:'smooth'}); });
}
