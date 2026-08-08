<?php
$qs = static function (array $extra) use ($reportType, $filters): string {
    return http_build_query(array_filter(['type' => $reportType] + $extra + $filters));
};
$hasFilter = $filters['from'] || $filters['to'] || $filters['recorded_by'] || $filters['category'];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="report-tabs">
  <a class="report-tab<?= $reportType === 'inward' ? ' active' : '' ?>" href="<?= base_url('reports') ?>?type=inward">Inward Report</a>
  <a class="report-tab<?= $reportType === 'outward' ? ' active' : '' ?>" href="<?= base_url('reports') ?>?type=outward">Outward Report</a>
  <a class="report-tab<?= $reportType === 'stock' ? ' active' : '' ?>" href="<?= base_url('reports') ?>?type=stock">Stock Report</a>
</div>

<form class="report-filters" method="get" action="<?= base_url('reports') ?>">
  <input type="hidden" name="type" value="<?= esc($reportType, 'attr') ?>">
  <?php if ($reportType !== 'stock'): ?>
    <label class="filter-field">From <input type="date" name="from" value="<?= esc($filters['from'], 'attr') ?>"></label>
    <label class="filter-field">To <input type="date" name="to" value="<?= esc($filters['to'], 'attr') ?>"></label>
    <label class="filter-field">User
      <select name="recorded_by">
        <option value="">All users</option>
        <?php foreach ($users as $u): ?>
          <option<?= $filters['recorded_by'] === $u ? ' selected' : '' ?>><?= esc($u) ?></option>
        <?php endforeach ?>
      </select>
    </label>
  <?php endif ?>
  <label class="filter-field">Category
    <select name="category">
      <option value="">All categories</option>
      <?php foreach ($categories as $c): ?>
        <option<?= $filters['category'] === $c ? ' selected' : '' ?>><?= esc($c) ?></option>
      <?php endforeach ?>
    </select>
  </label>
  <button class="filter-apply" type="submit">Apply</button>
  <?php if ($hasFilter): ?>
    <a class="link" href="<?= base_url('reports') ?>?type=<?= esc($reportType, 'url') ?>">Clear</a>
  <?php endif ?>
  <div class="filter-actions">
    <a class="link" href="<?= base_url('reports/print') ?>?<?= $qs([]) ?>" target="_blank">Print</a>
    <a class="link" href="<?= base_url('reports/export') ?>?<?= $qs([]) ?>">Export CSV</a>
  </div>
</form>

<article class="panel">
<?php if ($reportType === 'stock'): ?>
  <table>
    <thead><tr><th>Item</th><th>Barcode</th><th>Category</th><th>Unit</th><th>Available</th><th>Reorder Level</th><th>Status</th></tr></thead>
    <tbody>
    <?php $totalAvail = 0; foreach ($items as $i): $totalAvail += $i['available_quantity']; $isLow = $i['available_quantity'] <= $i['reorder_level']; ?>
      <tr>
        <td><b><?= esc($i['name']) ?></b><?php if ($i['sku']): ?><small><?= esc($i['sku']) ?></small><?php endif ?></td>
        <td><code><?= esc($i['barcode']) ?></code></td>
        <td><?= esc($i['category']) ?></td>
        <td><?= esc($i['unit']) ?></td>
        <td class="<?= $isLow ? 'danger' : '' ?>"><?= $i['available_quantity'] + 0 ?></td>
        <td><?= $i['reorder_level'] + 0 ?></td>
        <td><span class="badge <?= $isLow ? 'out' : 'in' ?>"><?= $isLow ? 'LOW' : 'OK' ?></span></td>
      </tr>
    <?php endforeach ?>
    </tbody>
    <tfoot><tr><td colspan="4"><b><?= count($items) ?> items</b></td><td colspan="3"><b>Total available: <?= $totalAvail + 0 ?></b></td></tr></tfoot>
  </table>
  <?php if ($items === []): ?><div class="empty">No items match the filters.</div><?php endif ?>
<?php else: ?>
  <table>
    <thead><tr><th>Date</th><th>Type</th><th>Item</th><th>Category</th><th>Qty</th><th>Customer</th><th>Reference</th><th>Recorded by</th></tr></thead>
    <tbody>
    <?php $totalQty = 0; foreach ($rows as $m): $totalQty += $m['quantity']; ?>
      <tr>
        <td><?= esc(mf_local_datetime($m['occurred_at'])) ?></td>
        <td><span class="badge <?= $reportType === 'inward' ? 'in' : 'out' ?>"><?= esc(str_replace('_', ' ', $m['movement_type'])) ?></span></td>
        <td><?= esc($m['item_name']) ?></td>
        <td><?= esc($m['item_category']) ?></td>
        <td><?= $m['quantity'] + 0 ?> <?= esc($m['item_unit']) ?></td>
        <td><?= esc($m['customer_name'] ?? '—') ?: '—' ?></td>
        <td><?= esc($m['reference_number'] ?? '—') ?: '—' ?></td>
        <td><?= esc($m['recorded_by_name'] ?? '—') ?: '—' ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
    <tfoot><tr><td colspan="4"><b><?= count($rows) ?> records</b></td><td colspan="4"><b>Total quantity: <?= $totalQty + 0 ?></b></td></tr></tfoot>
  </table>
  <?php if ($rows === []): ?><div class="empty">No movements match the filters.</div><?php endif ?>
<?php endif ?>
</article>

<?= $this->endSection() ?>
