/* Batch generation dialog + batch history page. (Filled in incrementally.) */
(function () {
  "use strict";

  // --- Generate dialog (Items page) ---
  let currentItem = null;

  document.querySelectorAll(".js-batch-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      currentItem = {
        id: btn.dataset.itemId,
        name: btn.dataset.itemName,
        barcode: btn.dataset.itemBarcode,
      };
      document.getElementById("batchModalTitle").textContent = "Generate batch barcodes";
      document.getElementById("batchItemName").value = currentItem.name;
      document.getElementById("batchPrefix").placeholder = currentItem.barcode + "-{batch reference}-";
      document.getElementById("batchForm").hidden = false;
      document.getElementById("batchResult").hidden = true;
      openModal("batchModal");
    });
  });

  const form = document.getElementById("batchForm");
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      if (!currentItem) return;
      const btn = document.getElementById("batchGenerateBtn");
      btn.disabled = true;
      btn.textContent = "Generating...";
      try {
        const data = await apiFetch("/api/batches", {
          method: "POST",
          body: {
            item_id: currentItem.id,
            batch_reference: document.getElementById("batchReference").value.trim(),
            quantity: parseInt(document.getElementById("batchQuantity").value, 10),
            barcode_prefix: document.getElementById("batchPrefix").value.trim() || null,
          },
        });
        flash("✓ Generated " + data.total_generated_full + " barcodes for batch " + data.batch_reference);
        document.getElementById("batchModalTitle").textContent = "Batch generated";
        document.getElementById("batchForm").hidden = true;
        const result = document.getElementById("batchResult");
        result.hidden = false;
        document.getElementById("resultReference").textContent = data.batch_reference;
        document.getElementById("resultItem").textContent = data.item_name;
        document.getElementById("resultTotal").textContent = data.total_generated_full;
        document.getElementById("resultPreviewLabel").textContent =
          "Preview: showing first " + data.barcodes.length + " of " + data.total_generated_full;
        const preview = document.getElementById("resultPreview");
        preview.innerHTML = "";
        data.barcodes.forEach((b) => {
          const chip = document.createElement("code");
          chip.textContent = b.barcode;
          preview.appendChild(chip);
        });
        document.getElementById("batchPrintBtn").onclick = () => {
          window.open("/batches/" + data.batch_id + "/print-labels", "_blank");
        };
      } catch (err) {
        flash("❌ " + err.message);
      } finally {
        btn.disabled = false;
        btn.textContent = "Generate barcodes";
      }
    });
  }

  // --- Batch History page ---
  const statusFilter = document.getElementById("batchStatusFilter");
  if (statusFilter) {
    statusFilter.addEventListener("change", () => {
      const v = statusFilter.value;
      document.querySelectorAll("#batchTable tbody tr").forEach((tr) => {
        tr.hidden = v !== "all" && tr.dataset.status !== v;
      });
    });
  }

  let detailBatchId = null;

  document.querySelectorAll(".js-batch-info").forEach((btn) => {
    btn.addEventListener("click", async () => {
      detailBatchId = btn.dataset.batchId;
      document.getElementById("batchDetailLoading").hidden = false;
      document.getElementById("batchDetailBody").hidden = true;
      openModal("batchDetailModal");
      try {
        const d = await apiFetch("/api/batches/" + detailBatchId);
        const b = d.batch;
        document.getElementById("bdReference").textContent = b.batch_reference;
        document.getElementById("bdItem").textContent = b.item_name;
        document.getElementById("bdGenerated").textContent = b.quantity_generated + " / " + b.quantity_total;
        document.getElementById("bdStatus").textContent = (b.status_detail || "CREATED").replace(/_/g, " ");
        document.getElementById("bdTotal").textContent = d.scan_stats.total;
        document.getElementById("bdScanned").textContent = d.scan_stats.scanned;
        document.getElementById("bdUnscanned").textContent = d.scan_stats.unscanned;

        const max = parseInt(b.quantity_generated, 10) || 1;
        const from = document.getElementById("bdRangeFrom");
        const to = document.getElementById("bdRangeTo");
        if (from && to) {
          from.value = 1;
          from.max = max;
          to.value = max;
          to.max = max;
          updateRangeCount();
        }

        const ph = document.getElementById("bdPrintHistory");
        ph.innerHTML = "";
        if (d.print_history.length) {
          d.print_history.forEach((h) => {
            const p = document.createElement("p");
            p.textContent =
              h.action + " · " + (h.printed_quantity || 0) + " labels · " +
              (h.printed_by_name || "Unknown") + " · " + h.created_at;
            ph.appendChild(p);
          });
        } else {
          ph.textContent = "No prints logged yet.";
        }

        const eh = document.getElementById("bdExportHistory");
        eh.innerHTML = "";
        if (d.export_history.length) {
          d.export_history.forEach((h) => {
            const p = document.createElement("p");
            p.textContent = h.export_format + " · " + (h.exported_by_name || "Unknown") + " · " + h.created_at;
            eh.appendChild(p);
          });
        } else {
          eh.textContent = "No exports yet.";
        }

        document.getElementById("batchDetailLoading").hidden = true;
        document.getElementById("batchDetailBody").hidden = false;
      } catch (err) {
        document.getElementById("batchDetailLoading").textContent = "❌ " + err.message;
      }
    });
  });

  function updateRangeCount() {
    const from = document.getElementById("bdRangeFrom");
    const to = document.getElementById("bdRangeTo");
    const count = document.getElementById("bdRangeCount");
    if (!from || !to || !count) return;
    const a = parseInt(from.value, 10) || 1;
    const b = parseInt(to.value, 10) || 1;
    count.textContent = "Total: " + Math.max(0, b - a + 1) + " barcodes";
  }
  ["bdRangeFrom", "bdRangeTo"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("input", updateRangeCount);
  });

  const printRangeBtn = document.getElementById("bdPrintRangeBtn");
  if (printRangeBtn) {
    printRangeBtn.addEventListener("click", () => {
      if (!detailBatchId) return;
      const from = parseInt(document.getElementById("bdRangeFrom").value, 10) || 1;
      const to = parseInt(document.getElementById("bdRangeTo").value, 10) || from;
      window.open("/batches/" + detailBatchId + "/print-labels?from=" + from + "&to=" + to, "_blank");
    });
  }
})();
