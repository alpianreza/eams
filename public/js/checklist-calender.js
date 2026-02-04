document.addEventListener("click", function (e) {
  const link = e.target.closest('a[href*="compliance/checklist"]');
  if (!link) return;

  e.preventDefault();

  const url = new URL(link.href);
  url.searchParams.set("ajax", "1");

  const container = document.getElementById("checklistAjax");
  if (!container) return;

  fetch(url.toString(), {
    headers: {
      "X-Requested-With": "XMLHttpRequest",
    },
  })
    .then((res) => res.text())
    .then((html) => {
      container.innerHTML = html;

      // 🔥 WAJIB: hidupkan ulang checklist UI
      if (window.reInitChecklistUI) {
        window.reInitChecklistUI();
      }

      container.scrollIntoView({ behavior: "smooth" });
    })
    .catch(() => {
      alert("Gagal memuat checklist.");
    });
});

document.querySelectorAll("#calendarCollapse a").forEach((el) => {
  el.addEventListener("click", () => {
    const collapse = bootstrap.Collapse.getOrCreateInstance(
      document.getElementById("calendarCollapse"),
    );
    collapse.hide();
  });
});
