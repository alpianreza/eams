document.addEventListener("DOMContentLoaded", function () {
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-month-nav");
    if (!btn) return;

    e.preventDefault();

    const ym = btn.dataset.ym;
    if (!ym) return;

    const container = document.getElementById("detailMonthContainer");
    if (!container) return;

    const url = new URL(window.location.href);
    url.searchParams.set("ym", ym);

    container.style.opacity = "0.5";

    fetch(url.toString(), {
      headers: { "X-Requested-With": "XMLHttpRequest" },
    })
      .then((res) => res.text())
      .then((html) => {
        container.innerHTML = html;
        container.style.opacity = "1";
        history.pushState(null, "", url.toString());
        updateExportPdfLink(ym);
      })
      .catch(() => {
        container.style.opacity = "1";
        alert("Gagal memuat data bulan");
      });
  });

  function updateExportPdfLink(ym) {
    const btn = document.getElementById("btnExportPdf");
    if (!btn) return;

    const baseUrl = btn.dataset.baseUrl;
    const frequency = btn.dataset.frequency;

    let periodKey;
    if (frequency === "daily") {
      periodKey = ym + "-01";
    } else if (frequency === "weekly") {
      periodKey = ym + "-W1";
    } else {
      periodKey = ym;
    }

    btn.href = `${baseUrl}/${periodKey}`;
  }

  
});
