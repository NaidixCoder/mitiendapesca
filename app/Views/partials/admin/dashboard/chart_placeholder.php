<?php ?>
<div class="rounded-2xl border border-slate-700/60 bg-slate-900/60 p-4">
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-slate-100 font-semibold">Tendencia (30 días)</h2>
      <p class="text-slate-500 text-sm">Placeholder de gráfico — hook JS: <code>data-chart</code></p>
    </div>
    <div class="flex items-center gap-2">
      <select class="bg-slate-800 text-slate-200 text-xs rounded-lg px-2 py-1">
        <option>Ventas</option><option>Visitas</option><option>Conversiones</option>
      </select>
      <select class="bg-slate-800 text-slate-200 text-xs rounded-lg px-2 py-1">
        <option>Últimos 30 días</option><option>Últimos 7 días</option><option>Este año</option>
      </select>
    </div>
  </div>
  <div data-chart="line" class="mt-4 h-56 w-full rounded-xl bg-slate-800/60 border border-slate-700/60 flex items-center justify-center text-slate-500 text-sm">
    Gráfico (implementación JS futura)
  </div>
</div>
