(function () {
  const form = document.getElementById('buy-form');
  if (!form) return;
  const input = form.querySelector('#qty');
  form.addEventListener('click', (e) => {
    const btn = e.target.closest('.qty-btn');
    if (!btn) return;
    const delta = parseInt(btn.dataset.delta || '0', 10);
    const min = parseInt(input.min || '1', 10);
    const cur = parseInt(input.value || '1', 10);
    const next = Math.max(min, cur + delta);
    input.value = next;
  });
})();
