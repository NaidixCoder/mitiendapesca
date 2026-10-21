<nav class="product-tabs-nav" id="tabs-nav" role="tablist" aria-label="Pasos de edicion">
  <?php foreach ($tabs as $tab):
    $tab = is_array($tab) ? $tab : [];
    $key = (string)($tab['key'] ?? '');
    if ($key === '') { continue; }
    $visible = !empty($tab['visible']);
    $active = !empty($tab['active']);
  ?>
    <button
      type="button"
      class="tab-btn btn-secondary <?= $active ?'is-active' : '' ?>"
      data-tab="<?= e($key) ?>"
      <?= $visible ? '' : 'hidden aria-hidden="true"' ?>>
      <?= e((string)($tab['label'] ?? $key)) ?>
      <?php if ((string)($tab['badge_state'] ?? '') !== ''): ?>
        <span
          data-wiz-badge="<?= e($key) ?>"
          data-wiz-state="<?= e((string)$tab['badge_state']) ?>"
          class="av3-ml-2 av3-inline-block av3-h-2-5 av3-w-2-5 av3-radius-full <?= e((string)($tab['badge_class'] ?? '')) ?>"
          title="<?= e((string)($tab['badge_title'] ?? '')) ?>"></span>
      <?php endif; ?>
    </button>
  <?php endforeach; ?>
</nav>
