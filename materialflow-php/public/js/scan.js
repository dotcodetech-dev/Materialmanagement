/* Inward/Outward scan screen: Enter → transactional commit → recent-scans list. */
(function () {
  "use strict";
  const input = document.getElementById("scanInput");
  if (!input) return;

  const typeSelect = document.getElementById("movementType");
  const section = document.getElementById("scansSection");
  const body = document.getElementById("scansBody");
  const summary = document.getElementById("scanSummary");
  const scans = [];
  let busy = false;

  function render() {
    section.hidden = scans.length === 0;
    body.innerHTML = "";
    scans.forEach((s) => {
      const tr = document.createElement("tr");
      if (!s.saved) tr.className = "scan-failed";
      [s.time, s.name, s.barcode, "1", s.saved ? "✓" : "✗"].forEach((v) => {
        const td = document.createElement("td");
        td.textContent = v;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });
    const saved = scans.filter((s) => s.saved).length;
    summary.textContent = "Total: " + scans.length + " scans | " + saved + " saved | " + (scans.length - saved) + " failed";
  }

  function record(name, barcode, saved) {
    scans.unshift({ time: new Date().toLocaleTimeString(), name: name, barcode: barcode, saved: saved });
    if (scans.length > 50) scans.pop();
    render();
  }

  async function handleScan(code) {
    code = code.trim();
    if (!code || busy) return;
    busy = true;
    try {
      const data = await apiFetch("/api/scan/commit", {
        method: "POST",
        body: { barcode: code, movement_type: typeSelect.value },
      });
      const itemName = String(data.item_name || "").substring(0, 100);
      const unitNumber = String(data.unit_number || "").substring(0, 10);
      const label = data.is_batch ? itemName + " (Unit " + unitNumber + ")" : itemName;
      record(label, code, true);
      flash(data.is_batch ? "✓ " + itemName + " - Unit " + unitNumber + " scanned" : "✓ " + itemName + " saved");
    } catch (err) {
      if (err.status === 409 && err.details) {
        const scannedBy = String(err.details.scanned_by || "unknown user").substring(0, 100);
        const when = err.details.scanned_at ? new Date(err.details.scanned_at.replace(" ", "T") + "Z").toLocaleString() : "";
        const detail = when ? " (by " + scannedBy + " on " + when + ")" : "";
        flash("❌ " + err.message + detail);
      } else {
        flash("❌ " + err.message);
      }
      record("—", code, false);
    } finally {
      busy = false;
      input.value = "";
      input.focus();
    }
  }

  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      handleScan(input.value);
    }
  });

  document.getElementById("clearScansBtn").addEventListener("click", () => {
    scans.length = 0;
    render();
  });
})();
