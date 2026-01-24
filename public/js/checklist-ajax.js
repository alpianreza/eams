document.addEventListener("click", function (e) {
  const btn = e.target.closest(".ajax-nav");
  if (!btn) return;

  e.preventDefault();

  const ym = btn.dataset.ym;
  const id = btn.dataset.id;

  const container = document.getElementById("checklist-container");
  if (!container) return;

  container.innerHTML = `
    <div class="text-center my-4 text-muted">
      <div class="spinner-border spinner-border-sm me-2"></div>
      Memuat...
    </div>
  `;

  fetch(btn.href, {
    headers: { "X-Requested-With": "XMLHttpRequest" },
  })
    .then((res) => res.text())
    .then((html) => {
      container.innerHTML = html;

      // update URL tanpa reload
      const url = new URL(window.location);
      url.searchParams.set("ym", ym);
      window.history.pushState({}, "", url);
    })
    .catch(() => {
      // fallback: reload normal
      window.location.href = btn.href;
    });
});
