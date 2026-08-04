<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="search-bar">
  <input class="search-input" data-filter-table="ledgerTable" placeholder="Search movements...">
</div>

<article class="panel">
  <div class="panel-head">
    <h2>Stock ledger</h2>
    <a class="link" href="<?= base_url('ledger/export') ?>">Export CSV</a>
  </div>
  <table id="ledgerTable">
    <thead>
      <tr><th>Date &amp; time</th><th>Type</th><th>Item</th><th>Qty</th><th>Customer</th><th>Reference</th><th>Recorded by</th></tr>
    </thead>
    <tbody>
    <?php foreach ($movements as $m): ?>
      <?php $in = in_array($m['movement_type'], ['INWARD', 'RETURN_IN', 'ADJUSTMENT_IN'], true); ?>
      <tr>
        <td><?= esc(date('d M Y, g:i a', strtotime($m['occurred_at']))) ?></td>
        <td><span class="badge <?= $in ? 'in' : 'out' ?>"><?= esc(str_replace('_', ' ', $m['movement_type'])) ?></span></td>
        <td><b><?= esc($m['item_name']) ?></b><small><?= esc($m['item_barcode']) ?></small></td>
        <td><?= $in ? '+' : '−' ?><?= $m['quantity'] + 0 ?> <?= esc($m['item_unit']) ?></td>
        <td><?= esc($m['customer_name'] ?? '—') ?: '—' ?></td>
        <td><?= esc($m['reference_number'] ?? '—') ?: '—' ?></td>
        <td><?= esc($m['recorded_by_name'] ?? '—') ?: '—' ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php if ($movements === []): ?><div class="empty">No transactions yet.</div><?php endif ?>
</article>

<?= $this->endSection() ?>
