<?php
/** @var array<string,mixed>|null $shipment */
$shipment = $shipment ?? null;

if (!$shipment) return;

$carrier = trim((string)($shipment['carrier'] ?? ''));
$service = trim((string)($shipment['service'] ?? ''));
$tn      = trim((string)($shipment['tracking_number'] ?? ''));
$url     = trim((string)($shipment['tracking_url'] ?? ''));

if ($url === '' && $carrier === 'correo_argentino' && $tn !== '') {
  $url = 'https://www.correoargentino.com.ar/formularios/e-commerce';
}

$operStatus = trim((string)($shipment['status'] ?? 'preparando'));
$trackingStatus = trim((string)($shipment['tracking_status'] ?? ''));
$lastTrackedAt = trim((string)($shipment['last_tracked_at'] ?? ''));

$lastEventLabel = '';
$lastEventDate  = '';
$eventsJson = (string)($shipment['tracking_events_json'] ?? '');
if ($eventsJson !== '') {
  $payload = json_decode($eventsJson, true);
  if (is_array($payload)) {
    $events = [];
    foreach ($payload as $entry) {
      if (!is_array($entry)) continue;
      $es = $entry['events'] ?? [];
      if (!is_array($es)) continue;
      foreach ($es as $ev) {
        if (!is_array($ev)) continue;
        $code = strtoupper(trim((string)($ev['event'] ?? '')));
        $date = trim((string)($ev['date'] ?? ''));
        if ($code === '' && $date === '') continue;
        $events[] = ['code' => $code, 'date' => $date];
      }
    }
    if (!empty($events)) {
      usort($events, function ($a, $b) {
        return strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
      });
      $last = $events[count($events) - 1];
      $lastEventDate = (string)($last['date'] ?? '');
      $code = (string)($last['code'] ?? '');
      if ($code !== '') {
        if (str_contains($code, 'ENTREG')) $lastEventLabel = 'Entregado';
        elseif (str_contains($code, 'SUCURS') || str_contains($code, 'EN_SUCURSAL')) $lastEventLabel = 'En sucursal';
        elseif (str_contains($code, 'TRANSITO') || str_contains($code, 'TRASLADO') || str_contains($code, 'EN_CAMINO')) $lastEventLabel = 'En tránsito';
        elseif (str_contains($code, 'ADMIS') || str_contains($code, 'IMPOS') || str_contains($code, 'CLASIF')) $lastEventLabel = 'Procesado por correo';
        else $lastEventLabel = $code;
      }
    }
  }
}

$status = $trackingStatus !== '' ? $trackingStatus : $operStatus;

$label = match ($status) {
  'preparando' => 'Preparando',
  'despachado' => 'Despachado',
  'en_transito' => 'En tránsito',
  'en_sucursal' => 'En sucursal',
  'entregado' => 'Entregado',
  'fallido' => 'Fallido',
  default => ucfirst(str_replace('_',' ', $status)),
};

$fmt = function (?string $dt): string {
  $dt = is_string($dt) ? trim($dt) : '';
  if ($dt === '') return '';
  $ts = strtotime($dt);
  if ($ts === false) return $dt;
  return date('d/m/Y H:i', $ts);
};

$lastTrackedAtFmt = $fmt($lastTrackedAt);
$lastEventDateFmt = $fmt($lastEventDate);
?>
<section class="order-show-card order-show-card--sidebar">
  <header class="order-show-card__head">
    <h2 class="order-show-card__title">Envío</h2>
  </header>

  <div class="order-show-card__body">
    <div class="order-show-shipping">
      <div class="order-show-shipping__summary">
        <p class="order-show-shipping__carrier">
          <?= $carrier ? e($carrier) : 'Correo Argentino' ?><?= $service ? ' · ' . e($service) : '' ?>
        </p>
        <p class="order-show-shipping__status"><?= e($label) ?></p>
      </div>

      <?php if ($lastEventLabel !== ''): ?>
        <p class="order-show-shipping__meta">
          Último evento: <span class="order-show-emphasis"><?= e($lastEventLabel) ?></span>
          <?php if ($lastEventDateFmt !== ''): ?>
            · <span class="order-show-code"><?= e($lastEventDateFmt) ?></span>
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if ($lastTrackedAtFmt !== ''): ?>
        <p class="order-show-shipping__meta">
          Última actualización: <span class="order-show-code"><?= e($lastTrackedAtFmt) ?></span>
        </p>
      <?php endif; ?>

      <?php if ($tn): ?>
        <p class="order-show-shipping__meta">
          N°: <span class="order-show-code"><?= e($tn) ?></span>
        </p>
      <?php endif; ?>

      <?php if ($url): ?>
        <a class="v2-btn v2-btn--primary" href="<?= e($url) ?>" target="_blank" rel="noopener">Ver seguimiento</a>
      <?php else: ?>
        <p class="order-show-sidebar-note">Te compartiremos el link de seguimiento apenas esté disponible.</p>
      <?php endif; ?>
    </div>
  </div>
</section>



