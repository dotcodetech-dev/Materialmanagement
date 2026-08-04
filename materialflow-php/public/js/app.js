/* MaterialFlow shared helpers: CSRF-aware fetch, toasts, modals, drawer, table filter. */
(function () {
  "use strict";

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

  window.apiFetch = async function (url, options = {}) {
    const opts = Object.assign({ method: "GET" }, options);
    opts.headers = Object.assign(
      {
        "X-CSRF-TOKEN": csrfToken,
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
      },
      opts.headers || {}
    );
    if (opts.body && typeof opts.body !== "string") {
      opts.headers["Content-Type"] = "application/json";
      opts.body = JSON.stringify(opts.body);
    }
    const res = await fetch(url, opts);
    let data = null;
    try {
      data = await res.json();
    } catch (e) {
      /* non-JSON response */
    }
    if (!res.ok) {
      const err = new Error((data && data.error) || "Request failed (" + res.status + ")");
      err.status = res.status;
      err.details = (data && data.details) || null;
      throw err;
    }
    return data;
  };

  let noticeTimer = null;
  window.flash = function (msg) {
    const el = document.getElementById("jsNotice");
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => {
      el.hidden = true;
    }, 5000);
  };

  window.openModal = function (id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.hidden = false;
  };
  window.closeModal = function (id) {
    const overlay = document.getElementById(id);
    if (overlay) overlay.hidden = true;
  };
  document.addEventListener("click", (e) => {
    if (e.target.classList && e.target.classList.contains("overlay")) {
      e.target.hidden = true;
    }
  });

  // Mobile drawer
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("drawerOverlay");
  function toggleDrawer() {
    if (!sidebar) return;
    const open = sidebar.classList.toggle("open");
    if (overlay) overlay.hidden = !open;
  }
  ["drawerToggle", "drawerToggle2"].forEach((id) => {
    const btn = document.getElementById(id);
    if (btn) btn.addEventListener("click", toggleDrawer);
  });
  if (overlay) overlay.addEventListener("click", toggleDrawer);

  // Client-side table filtering: <input data-filter-table="tableId">
  document.querySelectorAll("[data-filter-table]").forEach((input) => {
    const table = document.getElementById(input.dataset.filterTable);
    if (!table) return;
    input.addEventListener("input", () => {
      const q = input.value.trim().toLowerCase();
      table.querySelectorAll("tbody tr").forEach((tr) => {
        tr.hidden = q !== "" && !tr.textContent.toLowerCase().includes(q);
      });
    });
  });
})();
