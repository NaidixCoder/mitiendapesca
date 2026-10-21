<article class="fisc-card">
  <header class="fisc-card-head">
    <div>
      <h2>Atajos</h2>
      <p>Libros y reportes.</p>
    </div>
  </header>
  <div class="fisc-card-body">
    <div class="fisc-quick-links">
      <?php foreach ($quickLinks as $link): ?>
        <a class="fisc-link-card" href="<?= e((string)($link['url'] ?? '#')) ?>"><?= e((string)($link['label'] ?? '')) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</article>
