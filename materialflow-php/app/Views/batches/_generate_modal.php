<!-- Batch barcode generation dialog (opened from the Items table) -->
<div class="overlay" id="batchModal" hidden onclick="if(event.target.id==='batchModal') closeModal('batchModal')">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-head">
      <h2 id="batchModalTitle">Generate batch barcodes</h2>
      <button type="button" class="close-btn" onclick="closeModal('batchModal')" title="Close (Esc)" style="cursor:pointer;padding:8px;min-width:40px;min-height:40px"><span class="material-symbols-outlined">close</span></button>
    </div>

    <form id="batchForm">
      <label>Item name
        <input id="batchItemName" type="text" disabled readonly style="background:#f5f5f5;cursor:not-allowed">
      </label>
      <label>Batch reference<span class="required"> *</span>
        <input id="batchReference" type="text" placeholder="e.g., BATCH-2026-JAN-001" required autocomplete="off">
      </label>
      <label>Quantity<span class="required"> *</span>
        <input id="batchQuantity" type="number" min="1" max="10000" value="100" required>
      </label>
      <label>Barcode prefix (optional)
        <input id="batchPrefix" type="text" placeholder="Leave blank to auto-generate" autocomplete="off">
      </label>
      <button class="primary" id="batchGenerateBtn" type="submit" style="width:100%">Generate barcodes</button>
    </form>

    <div id="batchResult" hidden>
      <p><b>Batch reference:</b> <span id="resultReference"></span></p>
      <p><b>Item:</b> <span id="resultItem"></span></p>
      <p><b>Total generated:</b> <span id="resultTotal"></span></p>
      <p class="muted" id="resultPreviewLabel"></p>
      <div id="resultPreview" style="display:flex;flex-wrap:wrap;gap:6px;margin:10px 0"></div>

      <!-- QA gate: scan a printed sample with the USB scanner before mass printing. -->
      <div style="margin:14px 0;padding:12px;border:1px dashed #bbb;border-radius:8px;background:#fafafa">
        <label style="font-weight:600;font-size:13px">Verify with scanner <span class="muted">(recommended)</span>
          <input id="verifyInput" type="text" placeholder="Scan a printed label…" autocomplete="off" style="margin-top:6px">
        </label>
        <p class="muted" id="verifyHint" style="font-size:12px;margin-top:6px">Print, then scan one label here to confirm the barcode reads correctly.</p>
        <p id="verifyResult" style="font-size:13px;font-weight:600;margin-top:6px" hidden></p>
      </div>

      <div style="display:flex;gap:10px;margin-top:14px">
        <button type="button" class="primary" id="batchPrintBtn" style="flex:1">Print labels</button>
        <button type="button" class="logout-btn" onclick="closeModal('batchModal'); window.location.reload()" style="flex:1">Close</button>
      </div>
    </div>
  </div>
</div>
