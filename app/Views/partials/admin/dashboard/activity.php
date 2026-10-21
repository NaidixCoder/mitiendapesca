<?php /** @var array $events */ ?>
<section >
  <header >
    <h3 >Actividad reciente</h3>
    <a  href="<?= url('/admin/audit') ?>">Ver todo</a>
  </header>

  <?php if (empty($events)): ?>
    <p >Sin eventos.</p>
  <?php else: ?>
    <div ><ul >
      <?php foreach ($events as $e): ?>
        <li >
          <span  aria-hidden="true"><?= e((string)($e['icon'] ?? '??')) ?></span>

          <div >
            <div >
              <?php if (!empty($e['url'])): ?>
                <a  href="<?= e((string)$e['url']) ?>"><?= e((string)($e['text'] ?? 'Accion')) ?></a>
              <?php else: ?>
                <span ><?= e((string)($e['text'] ?? 'Accion')) ?></span>
              <?php endif; ?>

              <?php if (!empty($e['count_label'])): ?>
                <span ><?= e((string)$e['count_label']) ?></span>
              <?php endif; ?>
            </div>

            <div >
              <span>por <strong><?= e((string)($e['actor'] ?? 'Sistema')) ?></strong></span>
              <?php if (!empty($e['when'])): ?>
                <span >·</span>
                <time title="<?= e((string)($e['created_at'] ?? '')) ?>"><?= e((string)$e['when']) ?></time>
              <?php endif; ?>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
</section>

