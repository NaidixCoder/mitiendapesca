// Tabs + slug autogenerado (form de producto)
(function () {
  // Tabs
  const tabs = document.querySelectorAll('.tab');
  const btns = document.querySelectorAll('.tab-btn');

  function showTab(name) {
    tabs.forEach(t => t.classList.toggle('hidden', t.dataset.tab !== name));
    btns.forEach(b => b.classList.toggle('bg-brand', b.dataset.tab === name));
    if (name) location.hash = 'tab=' + name; // Persistencia simple en hash
  }

  btns.forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab)));
  const initial = (location.hash.match(/tab=([a-z]+)/) || [])[1] || 'basicos';
  showTab(initial);

  // Slug autogenerado si está vacío
  const $name = document.getElementById('product-name');
  const $slug = document.getElementById('product-slug');
  if ($name && $slug) {
    const slugify = s =>
      (s || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    $name.addEventListener('blur', () => {
      if (($slug.value || '').trim() === '') $slug.value = slugify($name.value);
    });
  }
})();
