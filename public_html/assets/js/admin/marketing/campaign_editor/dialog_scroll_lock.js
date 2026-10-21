// Estado de desplazamiento compartido por los diálogos de Email Marketing.
// Un selector de productos puede abrirse encima del asistente de cards.
const opened = new Set();

export function setEmailDialogOpen(dialog, visible) {
  if (!dialog) return;
  if (visible) opened.add(dialog);
  else opened.delete(dialog);
  document.body.classList.toggle('av3-email-dialog-open', opened.size > 0);
}
