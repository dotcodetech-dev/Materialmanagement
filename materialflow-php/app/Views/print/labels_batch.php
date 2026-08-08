<?php
// Plain-A4, hand-cut layout: 3 columns of ~63 x 30 mm labels. Wide enough for a
// compact Code 128 (MFU + 9 digits) at a safe module width and quiet zone.
$total      = count($barcodes);
$perPage    = 27; // 3 columns x 9 rows
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?> Labels</title>
  <style>
    @page{size:A4;margin:8mm}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif;background:#fff}
    .header{padding:6px 8px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:10px}
    .page{display:grid;grid-template-columns:repeat(3,1fr);gap:0;page-break-after:always;width:100%;background:#fff}
    /* Fixed physical size; a thin guide border for hand cutting. */
    .label{height:30mm;border:1px solid #bbb;padding:1.5mm 2mm;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center;page-break-inside:avoid;overflow:hidden;font-family:Arial,sans-serif;background:#fff}
    .label-name{font-size:10px;font-weight:700;line-height:1.05;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%}
    .label-unit{font-size:8px;color:#333;margin:0 0 0.5mm;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:100%}
    /* IMPORTANT: never force the SVG width — that distorts the bars. Let
       JsBarcode size it naturally and just center it. */
    .label svg{display:block;margin:0 auto;max-width:100%}
    .page-info{font-size:6px;color:#999;text-align:right;grid-column:1/-1;padding:2px 8px;border-top:1px solid #ddd;background:#f5f5f5}
    .no-print{display:flex}@media print{.no-print{display:none!important}.header{display:none!important}.page-info{display:none!important}.label{border:1px dashed #ccc}}
  </style>
</head>
<body>
  <div class="header no-print">
    <div><b><?= $total ?> labels — <?= esc($title) ?></b><br><small><?= esc($batch['item_name']) ?> | <?= $totalPages ?> page<?= $totalPages > 1 ? 's' : '' ?> | 3 cols × 9 rows, ~63×30mm</small></div>
    <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:3px;padding:5px 10px;font-weight:700;font-size:10px;cursor:pointer">Print</button>
  </div>
  <div id="pages"></div>
  <script src="<?= base_url('js/vendor/JsBarcode.all.min.js') ?>"></script>
  <script>
    const barcodes = <?= json_encode($barcodes) ?>;
    const itemName = <?= json_encode($batch['item_name']) ?>;
    const batchRef = <?= json_encode($batch['batch_reference']) ?>;
    const perPage = <?= $perPage ?>, totalPages = <?= $totalPages ?>, total = <?= $total ?>;
    let labelCount = 0;

    for (let page = 0; page < totalPages; page++) {
      const pageDiv = document.createElement("div");
      pageDiv.className = "page";

      for (let i = 0; i < perPage; i++) {
        if (labelCount < total) {
          const b = barcodes[labelCount];
          const div = document.createElement("div");
          div.className = "label";
          const name = document.createElement("div"); name.className = "label-name"; name.textContent = itemName;
          const unit = document.createElement("div"); unit.className = "label-unit"; unit.textContent = "Batch " + batchRef + " · Unit " + b.unit_number;
          const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
          div.append(name, unit, svg);
          pageDiv.appendChild(div);
          // Controlled geometry: ~0.34mm module (width 1.3px @96dpi), 3mm quiet
          // zone (margin 12px), and displayValue:true so the printed human text
          // IS exactly the encoded value.
          JsBarcode(svg, b.barcode, {
            format: "CODE128",
            width: 1.3,
            height: 42,
            margin: 12,
            displayValue: true,
            fontSize: 11,
            font: "monospace",
            textMargin: 2
          });
          labelCount++;
        }
      }

      const pageInfo = document.createElement("div");
      pageInfo.className = "page-info";
      pageInfo.textContent = "Page " + (page + 1) + " of " + totalPages + " | ~63×30mm labels | 3 columns × 9 rows";
      pageDiv.appendChild(pageInfo);
      document.getElementById("pages").appendChild(pageDiv);
    }

    // Log this print against the batch's cumulative counter.
    fetch(<?= json_encode(base_url('api/batches/' . $batch['id'] . '/printed')) ?>, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": <?= json_encode(csrf_hash()) ?>,
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify({ action: <?= json_encode($action) ?>, printed_quantity: total })
    }).catch(() => {});
  </script>
</body>
</html>
