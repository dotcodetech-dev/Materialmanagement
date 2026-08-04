/* Items page: server-generated barcode suggestion. */
(function () {
  "use strict";
  const btn = document.getElementById("genBarcodeBtn");
  const input = document.getElementById("barcodeInput");
  if (!btn || !input) return;

  btn.addEventListener("click", async () => {
    btn.disabled = true;
    try {
      const data = await apiFetch(window.location.origin + "/api/items/next-barcode");
      input.value = data.barcode;
    } catch (err) {
      flash("❌ " + err.message);
    } finally {
      btn.disabled = false;
    }
  });
})();
