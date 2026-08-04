/* Bulk labels screen: selection count + print URL builder. */
(function () {
  "use strict";
  const checks = () => Array.from(document.querySelectorAll(".label-check"));
  const countEl = document.getElementById("labelCount");

  function update() {
    let total = 0;
    checks().forEach((cb) => {
      if (cb.checked) {
        const qty = cb.closest("tr").querySelector(".label-qty");
        total += Math.max(1, parseInt(qty.value, 10) || 1);
      }
    });
    countEl.textContent = total + " labels selected";
  }

  document.addEventListener("change", (e) => {
    if (e.target.classList.contains("label-check") || e.target.classList.contains("label-qty")) update();
  });

  document.getElementById("selectAllBtn").addEventListener("click", () => {
    const all = checks();
    const allChecked = all.every((cb) => cb.checked);
    all.forEach((cb) => (cb.checked = !allChecked));
    update();
  });

  document.getElementById("printLabelsBtn").addEventListener("click", () => {
    const sel = checks()
      .filter((cb) => cb.checked)
      .map((cb) => {
        const qty = Math.max(1, parseInt(cb.closest("tr").querySelector(".label-qty").value, 10) || 1);
        return cb.value + ":" + qty;
      });
    if (!sel.length) {
      flash("Select at least one item to print labels.");
      return;
    }
    const size = document.getElementById("labelSize").value;
    window.open("/labels/print?sel=" + encodeURIComponent(sel.join(",")) + "&size=" + size, "_blank");
  });
})();
