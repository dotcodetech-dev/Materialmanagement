<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="transaction">
  <article class="panel">
    <div class="panel-head"><h2>Record <?= esc($direction) ?></h2></div>
    <p class="muted">Scan barcodes to quickly <?= $direction === 'inward' ? 'add materials to' : 'issue materials from' ?> stock. Each scan records 1 unit.</p>

    <div class="barcode-scanner-section">
      <label>
        Movement type<span class="required"> *</span>
        <select id="movementType">
          <?php foreach ($types as $t): ?>
            <option value="<?= esc($t, 'attr') ?>"><?= esc($t) ?></option>
          <?php endforeach ?>
        </select>
      </label>
      <label>
        📱 Barcode Scanner<span class="required"> *</span>
        <input type="text" class="scanner-input" id="scanInput" placeholder="Scan barcode or enter code..." autocomplete="off" autofocus>
      </label>
    </div>

    <div class="scanned-items-section" id="scansSection" hidden>
      <h3>✓ Recent Scans</h3>
      <table class="scanned-items-table">
        <thead>
          <tr><th class="time-col">Time</th><th>Item Name</th><th class="barcode-col">Barcode</th><th class="qty-col">Qty</th><th class="status-col">Status</th></tr>
        </thead>
        <tbody id="scansBody"></tbody>
      </table>
      <p class="scan-summary" id="scanSummary"></p>
      <div class="scan-actions">
        <button type="button" class="link" id="clearScansBtn">Clear List</button>
        <a class="link" href="<?= base_url() ?>">End Session</a>
      </div>
    </div>
  </article>

  <aside class="hint">
    <b>Quick Scanning</b>
    <p>Focus the scanner box and scan a batch barcode or an item barcode. Batch barcodes can be scanned only once. Use <a href="<?= base_url('movements/new') ?>">manual entry</a> to record a movement with a customer, quantity, or reference.</p>
  </aside>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/scan.js') ?>"></script>
<?= $this->endSection() ?>
