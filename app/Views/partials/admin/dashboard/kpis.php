<?php /** @var array $kpis */ ?>
<div >
  <?php foreach (($kpis ?? []) as $k): ?>
    <?php render_partial('admin/components/metric_card', ['metric' => $k]); ?>
  <?php endforeach; ?>
</div>
