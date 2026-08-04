<?php
$titles = ['inward' => 'Inward Report', 'outward' => 'Outward Report', 'stock' => 'Stock Report'];
$title  = $titles[$reportType];
$meta   = [];
if (! empty($filters['from']) || ! empty($filters['to'])) {
    $meta[] = 'Period: ' . ($filters['from'] ?: '…') . ' to ' . ($filters['to'] ?: '…');
}
if (! empty($filters['category'])) {
    $meta[] = 'Category: ' . $filters['category'];
}
if (! empty($filters['recorded_by'])) {
    $meta[] = 'User: ' . $filters['recorded_by'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?></title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;padding:24px;color:#111}
    h1{font-size:20px;margin-bottom:2px}
    h2{font-size:15px;margin:14px 0 4px}
    .meta{color:#555;font-size:11px;margin-bottom:14px}
    .company{font-size:12px;color:#333;margin-bottom:10px}
    table{width:100%;border-collapse:collapse;font-size:11px}
    th,td{border:1px solid #ccc;padding:5px 7px;text-align:left}
    th{background:#f2f2f2}
    tfoot td{font-weight:700;background:#fafafa}
    .no-print{margin-bottom:14px}
    @media print{.no-print{display:none}}
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:8px;padding:10px 24px;font-weight:700;font-size:14px;cursor:pointer">Print</button>
  </div>

  <h1><?= esc($brand['company_name']) ?></h1>
  <div class="company"><?= esc(implode(' · ', array_filter([$brand['address'], $brand['phone'], $brand['email']]))) ?></div>
  <h2><?= esc($title) ?></h2>
  <div class="meta">Generated: <?= date('d M Y, H:i') ?> UTC<?= $meta !== [] ? ' | ' . esc(implode(' | ', $meta)) : '' ?></div>

<?php if ($reportType === 'stock'): ?>
  <table>
    <thead><tr><th>Item</th><th>Barcode</th><th>Category</th><th>Unit</th><th>Available</th><th>Reorder Level</th><th>Status</th></tr></thead>
    <tbody>
    <?php $totalAvail = 0; foreach ($items as $i): $totalAvail += $i['available_quantity']; ?>
      <tr>
        <td><?= esc($i['name']) ?></td>
        <td><?= esc($i['barcode']) ?></td>
        <td><?= esc($i['category']) ?></td>
        <td><?= esc($i['unit']) ?></td>
        <td><?= $i['available_quantity'] + 0 ?></td>
        <td><?= $i['reorder_level'] + 0 ?></td>
        <td><?= $i['available_quantity'] <= $i['reorder_level'] ? 'LOW' : 'OK' ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
    <tfoot><tr><td colspan="4"><?= count($items) ?> items</td><td colspan="3">Total available: <?= $totalAvail + 0 ?></td></tr></tfoot>
  </table>
<?php else: ?>
  <table>
    <thead><tr><th>Date</th><th>Type</th><th>Item</th><th>Category</th><th>Qty</th><th>Customer</th><th>Reference</th><th>Recorded by</th></tr></thead>
    <tbody>
    <?php $totalQty = 0; foreach ($rows as $m): $totalQty += $m['quantity']; ?>
      <tr>
        <td><?= esc(date('d M Y, g:i a', strtotime($m['occurred_at']))) ?></td>
        <td><?= esc(str_replace('_', ' ', $m['movement_type'])) ?></td>
        <td><?= esc($m['item_name']) ?></td>
        <td><?= esc($m['item_category']) ?></td>
        <td><?= $m['quantity'] + 0 ?> <?= esc($m['item_unit']) ?></td>
        <td><?= esc($m['customer_name'] ?? '') ?></td>
        <td><?= esc($m['reference_number'] ?? '') ?></td>
        <td><?= esc($m['recorded_by_name'] ?? '') ?></td>
      </tr>
    <?php endforeach ?>
    </tbody>
    <tfoot><tr><td colspan="4"><?= count($rows) ?> records</td><td colspan="4">Total quantity: <?= $totalQty + 0 ?></td></tr></tfoot>
  </table>
<?php endif ?>
</body>
</html>
