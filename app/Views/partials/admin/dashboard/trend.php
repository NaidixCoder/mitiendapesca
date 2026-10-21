<?php
/**
 * Panel: Tendencia (Dashboard)
 * Estructura base (HTML/SVG) vive en la vista.
 * JS solo hidrata datos y geometria.
 */
?>
<section >
  <header >
    <h3 >Tendencia <span id="trend-title-days" >(7 dias)</span></h3>
    <div id="trend-title-metric" >Ventas ($)</div>
  </header>
  <div id="trend-root" >
    <div >
      <label  for="trend-days">Rango:</label>
      <select id="trend-days" >
        <option value="7" selected>7 dias</option>
        <option value="30">30 dias</option>
        <option value="90">90 dias</option>
      </select>

      <label  for="trend-metric">Metrica:</label>
      <select id="trend-metric" >
        <option value="revenue">Ventas ($)</option>
        <option value="orders">Pedidos (#)</option>
        <option value="avg_ticket">Ticket promedio ($)</option>
      </select>
    </div>

    <div id="trend-chart-wrap" >
      <svg id="trend-svg"  width="100%" height="220" viewBox="0 0 860 220" aria-label="Grafico de tendencia">
        <rect class="tframe" x="1" y="1" width="858" height="218"></rect>
        <g id="trend-grid"></g>
        <path id="trend-area"  d=""></path>
        <path id="trend-line"  d=""></path>
        <g id="trend-dots"></g>
        <text id="trend-x-start"  x="52" y="212"></text>
        <text id="trend-x-end"  x="844" y="212" text-anchor="end"></text>
      </svg>
      <div id="trend-message" class="av3-hidden">Cargando...</div>
    </div>
  </div>
</section>
