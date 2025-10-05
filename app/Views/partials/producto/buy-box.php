<div class="rounded-2xl border border-gray-100 p-4 space-y-3">
  <form id="buy-form" action="#" method="post" class="space-y-3">
    <div class="flex items-center gap-2">
      <label for="qty" class="text-sm text-gray-500">Cantidad</label>

      <div class="inline-flex border border-gray-200 rounded-xl overflow-hidden">
        <button type="button" class="qty-btn px-3 py-2" data-delta="-1" aria-label="Restar"><span aria-hidden="true">-</span></button>

        <input id="qty" name="qty" type="number" value="1" min="1" step="1"
              class="no-spin w-14 text-center focus:outline-none" inputmode="numeric"/>

        <button type="button" class="qty-btn px-3 py-2" data-delta="1" aria-label="Sumar"><span aria-hidden="true">+</span></button>
      </div>

    </div>

    <button class="w-full px-4 py-3 rounded-xl bg-brand text-white font-medium hover:opacity-90">
      Agregar al carrito
    </button>
    <button type="button" class="w-full px-4 py-3 rounded-xl border border-gray-200 hover:border-gray-300">
      Comprar ahora
    </button>
  </form>

  <ul class="text-sm text-gray-600 space-y-1">
    <li>✓ Envíos a todo el país</li>
    <li>✓ 3 y 6 cuotas sin interés (demo)</li>
    <li>✓ Devolución en 10 días</li>
  </ul>
</div>
