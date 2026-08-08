<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<article class="panel">
  <div class="panel-head">
    <h2>Batch History</h2>
    <label class="inline-label">
      <select id="batchStatusFilter">
        <option value="all">All Batches</option>
        <option value="CREATED">Just Created</option>
        <option value="PARTIALLY_PRINTED">Partially Printed</option>
        <option value="FULLY_PRINTED">Fully Printed</option>
      </select>
    </label>
  </div>

  <table id="batchTable">
    <thead>
      <tr>
        <th>Batch Reference</th><th>Item</th><th>Generated</th><th>Printed</th>
        <th>Scanned</th><th>Status</th><th>Last Printed</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($batches as $b): ?>
      <tr data-status="<?= esc($b['status_detail'] ?? 'CREATED', 'attr') ?>">
        <td><code><?= esc($b['batch_reference']) ?></code></td>
        <td><?= esc($b['item_name']) ?></td>
        <td><?= (int) $b['quantity_generated'] ?> / <?= (int) $b['quantity_total'] ?></td>
        <td><span class="badge"><?= (int) $b['total_printed'] ?></span></td>
        <td><?= (int) $b['scanned_count'] ?> / <?= (int) $b['total_barcodes'] ?></td>
        <td><span class="badge <?= ($b['status_detail'] ?? '') === 'FULLY_PRINTED' ? 'in' : (($b['status_detail'] ?? '') === 'PARTIALLY_PRINTED' ? 'out' : '') ?>">
          <?= esc(str_replace('_', ' ', $b['status_detail'] ?? 'CREATED')) ?>
        </span></td>
        <td><?= esc(mf_local_datetime($b['last_printed_at'])) ?></td>
        <td class="actions">
          <a class="act" href="<?= base_url('batches/' . $b['id'] . '/export') ?>" title="Export CSV">
            <span class="material-symbols-outlined">download</span>
          </a>
          <?php if ($canEdit): ?>
          <a class="act" href="<?= base_url('batches/' . $b['id'] . '/print-labels') ?>" target="_blank" title="Reprint all labels">
            <span class="material-symbols-outlined">print</span>
          </a>
          <?php endif ?>
          <button type="button" class="act js-batch-info" data-batch-id="<?= esc($b['id'], 'attr') ?>" title="Details">
            <span class="material-symbols-outlined">info</span>
          </button>
        </td>
      </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php if ($batches === []): ?><div class="empty">No batches found</div><?php endif ?>
</article>

<!-- Batch details modal -->
<div class="overlay" id="batchDetailModal" hidden>
  <div class="modal" onclick="event.stopPropagation()" style="max-width:640px">
    <div class="modal-head">
      <h2>Batch Details</h2>
      <button type="button" class="close-btn" onclick="closeModal('batchDetailModal')"><span class="material-symbols-outlined">close</span></button>
    </div>
    <div id="batchDetailLoading" class="empty">Loading...</div>
    <div id="batchDetailBody" hidden>
      <div class="grid two" style="gap:10px">
        <p><b>Batch Reference:</b> <span id="bdReference"></span></p>
        <p><b>Item:</b> <span id="bdItem"></span></p>
        <p><b>Generated:</b> <span id="bdGenerated"></span></p>
        <p><b>Status:</b> <span id="bdStatus"></span></p>
      </div>

      <h3 style="margin:14px 0 6px">Scan Status</h3>
      <div class="stats" style="grid-template-columns:repeat(3,1fr)">
        <div class="stat"><div><p>Total</p><strong id="bdTotal">0</strong></div></div>
        <div class="stat"><div><p>Scanned</p><strong id="bdScanned">0</strong></div></div>
        <div class="stat"><div><p>Unscanned</p><strong id="bdUnscanned">0</strong></div></div>
      </div>

      <?php if ($canEdit): ?>
      <h3 style="margin:14px 0 6px">Print Range</h3>
      <div style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
        <label>From Unit <input type="number" id="bdRangeFrom" class="qty-input" value="1" min="1" style="width:90px"></label>
        <label>To Unit <input type="number" id="bdRangeTo" class="qty-input" value="1" min="1" style="width:90px"></label>
        <span class="muted" id="bdRangeCount"></span>
        <button type="button" class="primary" id="bdPrintRangeBtn">Print Selected Range</button>
      </div>
      <?php endif ?>

      <h3 style="margin:14px 0 6px">Print History</h3>
      <div id="bdPrintHistory" class="muted"></div>

      <h3 style="margin:14px 0 6px">Export History</h3>
      <div id="bdExportHistory" class="muted"></div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('pageScripts') ?>
<script src="<?= base_url('js/batches.js') ?>"></script>
<?= $this->endSection() ?>
