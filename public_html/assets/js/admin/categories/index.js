// Controles exclusivamente del módulo Categorías.
document.querySelectorAll('[data-categories-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    const message = form.getAttribute('data-categories-confirm') || '¿Confirmar cambio?';
    if (!window.confirm(message)) event.preventDefault();
  });
});

document.querySelectorAll('[data-categories-image]').forEach((img) => {
  const replaceMissing = () => {
    if (!img.isConnected) return;
    const isPreview = !!img.closest('.av3-categories__media-preview');
    const replacement = document.createElement('span');
    if (isPreview) {
      replacement.textContent = 'Portada no disponible';
    } else {
      replacement.className = 'av3-categories__placeholder';
      replacement.textContent = 'C';
      replacement.setAttribute('aria-hidden', 'true');
    }
    img.replaceWith(replacement);
  };
  img.addEventListener('error', replaceMissing, { once: true });
  if (img.complete && img.naturalWidth === 0) replaceMissing();
});
