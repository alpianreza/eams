document.addEventListener("click", function (e) {
  const link = e.target.closest('a[href*="compliance/checklist"]');
  if (!link) return;

  e.preventDefault();

  const container = document.getElementById("checklistAjax");
  if (!container) return;

  const url = new URL(link.href);
  url.searchParams.set("ajax", "1");

  // Loading kecil
  container.innerHTML = `
    <div class="text-center py-4 text-muted">
      <div class="spinner-border spinner-border-sm me-2"></div>
      Memuat checklist...
    </div>
  `;

  fetch(url.toString(), {
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((res) => res.text())
    .then((html) => {
      container.innerHTML = html;

      // Hidupkan ulang semua event
      if (window.reInitChecklistUI) {
        window.reInitChecklistUI();
      }

      // Update URL tanpa reload
      window.history.pushState({}, "", link.href);

      // Scroll halus ke atas checklist
      container.scrollIntoView({ behavior: "smooth", block: "start" });
    })
    .catch(() => {
      // fallback normal reload
      window.location.href = link.href;
    });
});
