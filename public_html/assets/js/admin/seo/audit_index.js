// La auditoría usa un formulario GET explícito. No se navega ni se pierde
// el foco durante la escritura; Enter y el botón Buscar envían la consulta.
export function init() {
  const form = document.getElementById('seo-audit-filters');
  if (!form) return;
  const q = form.querySelector('[data-seo-audit-q]');
  if (!q) return;
  // El resto del filtrado y los diagnósticos expandibles son HTML nativo.
  q.setAttribute('autocomplete', 'off');
}
