<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Barcode Labels</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Arial,sans-serif}
    .grid{display:flex;flex-wrap:wrap;padding:8px}
    .label{width:<?= $sz['w'] ?>px;height:<?= $sz['h'] ?>px;border:1px dashed #ccc;padding:6px;text-align:center;display:flex;flex-direction:column;justify-content:center;align-items:center;page-break-inside:avoid;overflow:hidden}
    .label h4{font-size:<?= $sz['fs'] ?>px;margin:0 0 2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
    .label p{font-size:<?= max($sz['fs'] - 3, 7) ?>px;color:#666;margin:0 0 3px}
    @media print{.label{border:none}.no-print{display:none !important}}
  </style>
</head>
<body>
  <div class="no-print" style="padding:12px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center">
    <b>Barcode Labels — <?= (int) $total ?> labels</b>
    <button onclick="window.print()" style="background:#006b5f;color:#fff;border:0;border-radius:8px;padding:10px 24px;font-weight:700;font-size:14px;cursor:pointer">Print</button>
  </div>
  <div class="grid" id="labels"></div>
  <script src="<?= base_url('js/vendor/JsBarcode.all.min.js') ?>"></script>
  <script>
    const items = <?= json_encode($labels) ?>;
    const grid = document.getElementById("labels");
    items.forEach(item => {
      for (let q = 0; q < item.qty; q++) {
        const div = document.createElement("div");
        div.className = "label";
        const h4 = document.createElement("h4"); h4.textContent = item.name;
        const p = document.createElement("p"); p.textContent = item.category + " · " + item.unit;
        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        div.append(h4, p, svg);
        grid.appendChild(div);
        JsBarcode(svg, item.barcode, {format:"CODE128",width:<?= $sz['bw'] ?>,height:<?= $sz['bh'] ?>,displayValue:true,fontSize:<?= $sz['fs'] ?>,margin:12,textMargin:2});
      }
    });
  </script>
</body>
</html>
