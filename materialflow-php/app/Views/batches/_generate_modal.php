<!-- Batch barcode generation dialog (opened from the Items table) -->
<div class="overlay" id="batchModal" hidden>
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2 id="batchModalTitle">Generate batch barcodes</h2>
      <button type="button" class="close-btn" onclick="closeModal('batchModal')"><span class="material-symbols-outlined">close</span></button>
    </div>

    <form id="batchForm">
      <div class="form-group">
        <label>Item name</label>
        <input id="batchItemName" disabled readonly>
      </div>
      <div class="form-group">
        <label>Batch reference <span class="required">*</span></label>
        <input id="batchReference" placeholder="e.g., BATCH-2026-JAN-001" required>
      </div>
      <div class="form-group">
        <label>Quantity <span class="required">*</span></label>
        <input id="batchQuantity" type="number" min="1" max="10000" value="100" required>
      </div>
      <div class="form-group">
        <label>Barcode prefix (optional)</label>
        <input id="batchPrefix" placeholder="">
      </div>
      <button class="primary" id="batchGenerateBtn" type="submit">Generate barcodes</button>
    </form>

    <div id="batchResult" hidden>
      <p><b>Batch reference:</b> <span id="resultReference"></span></p>
      <p><b>Item:</b> <span id="resultItem"></span></p>
      <p><b>Total generated:</b> <span id="resultTotal"></span></p>
      <p class="muted" id="resultPreviewLabel"></p>
      <div id="resultPreview" style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0"></div>
      <div style="display:flex;gap:10px;margin-top:14px">
        <button type="button" class="primary" id="batchPrintBtn">Print labels</button>
        <button type="button" class="link" onclick="closeModal('batchModal'); window.location.reload()">Close</button>
      </div>
    </div>
  </div>
</div>
