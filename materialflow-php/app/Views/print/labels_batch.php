<?php
$total      = count($barcodes);
$perPage    = 63;
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?> Labels</title>
  <style>
    @page{size:A4;margin:5mm}
    *{box-sizing:border-box;margin:0;padding:0}body{font-family:Arial,sans-serif;margin:0;background:#fff}
    .header{padding:6px 8px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:10px}
    .page{display:grid;grid-template-columns:repeat(7,1fr);gap:0;padding:0;page-break-after:always;width:100%;background:#fff}
    .label{width:100%;aspect-ratio:1/1;border:1.5px solid #000;padding:2px;text-align:center;display:flex;flex-direction:column;justify-content:space-evenly;align-items:center;page-break-inside:avoid;overflow:hidden;font-family:Arial,sans-serif;background:#fff}
    .label-name{font-size:13px;font-weight:700;line-height:1.05;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:98%}
    .label-unit{font-size:11px;color:#222;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:98%;font-weight:700}
    .label svg{width:94%;height:38px;max-width:96%;flex-shrink:0}
    .barcode-text{font-size:11px;color:#000;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;width:98%;letter-spacing:0.2px;font-weight:800;font-family:'Courier New',monospace}
    .page-info{font-size:6px;color:#999;text-align:right;grid-column:1/-1;padding:2px 8px;border-top:1px solid #ddd;background:#f5f5f5}
    .no-print{display:flex}@media print{.no-print{display:none!important}.header{display:none!important}.page-info{display:none!important}}
  </style>
</head>
<body>
  <div class="header no-print">
    <div><b><?= $total ?> labels — <?= esc($title) ?></b><br><small><?= esc($batch['item_name']) ?> | <?= $totalPages ?> page<?= $totalPages > 1 ? 's' : '' ?> | 30×30mm</small></div>
    <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:3px;padding:5px 10px;font-weight:700;font-size:10px;cursor:pointer">Print</button>
  </div>
  <div id="pages"></div>
  <script src="<?= base_url('js/vendor/JsBarcode.all.min.js') ?>"></script>
  <script>
    const barcodes = <?= json_encode($barcodes) ?>;
    const itemName = <?= json_encode($batch['item_name']) ?>;
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
          const unit = document.createElement("div"); unit.className = "label-unit"; unit.textContent = "Unit " + b.unit_number;
          const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
          const text = document.createElement("div"); text.className = "barcode-text"; text.textContent = b.barcode;
          div.append(name, unit, svg, text);
          pageDiv.appendChild(div);
          JsBarcode(svg, b.barcode, {format:"CODE128", width:1.1, height:36, displayValue:false, margin:0, textMargin:0});
          labelCount++;
        }
      }

      const pageInfo = document.createElement("div");
      pageInfo.className = "page-info";
      pageInfo.textContent = "Page " + (page + 1) + " of " + totalPages + " | 30×30mm labels | 7 columns × 9 rows";
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
