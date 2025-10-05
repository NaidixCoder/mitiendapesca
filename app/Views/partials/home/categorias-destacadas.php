<section class="py-12 md:py-16">
  <div class="max-w-7xl mx-auto px-4 md:px-8">
    <h2 class="text-2xl md:text-3xl font-bold">Explorá por categoría</h2>
    <p class="text-gray-600">Encontrá rápido lo que buscás</p>

    <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
      <?php foreach ([
        ['img'=>'img/cat/reels.jpg',     'title'=>'Reels',       'href'=>base_url('productos').'?categoria=reels'],
        ['img'=>'img/cat/canas.jpg',     'title'=>'Cañas',       'href'=>base_url('productos').'?categoria=canas'],
        ['img'=>'img/cat/senuelos.jpg',  'title'=>'Señuelos',    'href'=>base_url('productos').'?categoria=senuelos'],
        ['img'=>'img/cat/lineas.jpg',    'title'=>'Líneas',      'href'=>base_url('productos').'?categoria=lineas'],
      ] as $c): ?>
      <a class="group rounded-2xl overflow-hidden border border-gray-200 bg-white hover:shadow-md transition"
        href="<?= e($c['href']) ?>">
        <div class="aspect-video bg-gray-50 overflow-hidden">
          <img src="<?= asset($c['img']) ?>" alt="<?= e($c['title']) ?>"
            class="h-full w-full object-cover group-hover:scale-105 transition">
        </div>
        <div class="p-4">
          <h3 class="font-semibold"><?= e($c['title']) ?></h3>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>