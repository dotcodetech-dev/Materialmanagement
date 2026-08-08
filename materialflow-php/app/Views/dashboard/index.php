<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="stats">
  <div class="stat">
    <div class="stat-icon"><span class="material-symbols-outlined">inventory_2</span></div>
    <div><p>Total items</p><strong><?= count($items) ?></strong></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><span class="material-symbols-outlined">widgets</span></div>
    <div><p>Units in stock</p><strong><?= round($stockTotal) ?></strong></div>
  </div>
  <div class="stat warning">
    <div class="stat-icon"><span class="material-symbols-outlined">warning</span></div>
    <div><p>Low-stock alerts</p><strong><?= count($low) ?></strong></div>
  </div>
  <div class="stat">
    <div class="stat-icon"><span class="material-symbols-outlined">sync_alt</span></div>
    <div><p>Today's movements</p><strong><?= $todayCount ?></strong></div>
  </div>
</div>

<div class="grid two">
  <article class="panel">
    <div class="panel-head">
      <h2>Low stock</h2>
      <a class="link" href="<?= base_url('items') ?>">View items</a>
    </div>
    <?php if ($low !== []): ?>
      <table>
        <thead><tr><th>Item</th><th>Available</th><th>Reorder level</th></tr></thead>
        <tbody>
        <?php foreach ($low as $i): ?>
          <tr>
            <td><b><?= esc($i['name']) ?></b><small><?= esc($i['barcode']) ?></small></td>
            <td class="danger"><?= $i['available_quantity'] + 0 ?> <?= esc($i['unit']) ?></td>
            <td><?= $i['reorder_level'] + 0 ?> <?= esc($i['unit']) ?></td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty">All items are above reorder level.</div>
    <?php endif ?>
  </article>

  <article class="panel">
    <div class="panel-head"><h2>Recent activity</h2></div>
    <?php if ($recent !== []): ?>
      <div class="activity">
      <?php foreach ($recent as $m): ?>
        <?php $in = in_array($m['movement_type'], ['INWARD', 'RETURN_IN', 'ADJUSTMENT_IN'], true); ?>
        <div>
          <span class="round <?= $in ? 'in' : 'out' ?>">
            <span class="material-symbols-outlined"><?= $in ? 'south_west' : 'north_east' ?></span>
          </span>
          <p><b><?= esc(str_replace('_', ' ', $m['movement_type'])) ?></b> · <?= esc($m['item_name']) ?>
            <small><?= esc(mf_local_datetime($m['occurred_at'])) ?> · <?= $m['quantity'] + 0 ?> <?= esc($m['item_unit']) ?></small>
          </p>
        </div>
      <?php endforeach ?>
      </div>
    <?php else: ?>
      <div class="empty">No transactions yet.</div>
    <?php endif ?>
  </article>
</div>

<?= $this->endSection() ?>
