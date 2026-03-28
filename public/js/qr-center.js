(function () {
  const page = document.getElementById("qrCenterPage");
  if (!page) {
    return;
  }

  const albumBase = page.dataset.urlAlbum || "/compliance/inventory/qr-album";
  const downloadBase = page.dataset.urlDownload || "/compliance/inventory/qr-album-download";
  const regenBase = page.dataset.urlRegen || "/compliance/inventory/qr-album-regen";
  const printBase = page.dataset.urlPrint || "/compliance/inventory/qr-album-print";

  const albumContent = document.getElementById("albumContent");
  const albumAjax = document.getElementById("albumAjax");
  const albumLoading = document.getElementById("albumLoading");
  const albumGrid = document.getElementById("qrAlbumGrid");
  const searchInput = document.getElementById("qrAlbumSearch");
  const btnReset = document.getElementById("btnQrReset");
  const emptyFilter = document.getElementById("qrAlbumEmptyFilter");
  const albumCountLabel = document.getElementById("qrAlbumCountLabel");

  let activeAlbumName = "";

  const toRelative = (rawUrl) => {
    try {
      const parsed = new URL(rawUrl, window.location.origin);
      return parsed.pathname + parsed.search;
    } catch (error) {
      return rawUrl;
    }
  };

  const buildEndpoint = (base, name) => {
    return `${base.replace(/\/$/, "")}/${encodeURIComponent(name)}`;
  };

  const setAlbumLoading = (isLoading) => {
    albumLoading.classList.toggle("d-none", !isLoading);
  };

  const renderAlbumError = (message) => {
    albumAjax.innerHTML = `<div class="qr-empty-state text-center text-danger py-4">${message}</div>`;
  };

  const openAlbum = async (name) => {
    if (!name) {
      return;
    }

    activeAlbumName = name;
    setAlbumLoading(true);
    albumContent.classList.remove("d-none");
    albumAjax.innerHTML = '<div class="text-muted">Memuat detail album...</div>';

    try {
      const res = await fetch(toRelative(buildEndpoint(albumBase, name)), {
        headers: { "X-Requested-With": "XMLHttpRequest" },
      });

      if (!res.ok) {
        throw new Error(`HTTP ${res.status}`);
      }

      albumAjax.innerHTML = await res.text();
      albumContent.scrollIntoView({ behavior: "smooth", block: "start" });
    } catch (error) {
      renderAlbumError("Gagal memuat album. Silakan coba lagi.");
    } finally {
      setAlbumLoading(false);
    }
  };

  const applyAlbumFilter = () => {
    const query = (searchInput.value || "").trim().toLowerCase();
    const cards = albumGrid.querySelectorAll(".qr-album-col");

    if (cards.length === 0) {
      emptyFilter.classList.add("d-none");
      return;
    }

    let visible = 0;
    cards.forEach((col) => {
      const card = col.querySelector(".qr-album-card");
      const keyword = (card?.dataset.keyword || "").toLowerCase();
      const match = query === "" || keyword.includes(query);
      col.classList.toggle("d-none", !match);
      if (match) {
        visible += 1;
      }
    });

    if (albumCountLabel) {
      albumCountLabel.textContent = String(visible);
    }

    emptyFilter.classList.toggle("d-none", visible > 0);
  };

  const handleDownload = (name) => {
    if (!name) {
      return;
    }

    Swal.fire({
      title: "Download album?",
      text: "Semua QR akan diunduh sebagai ZIP.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Download",
      cancelButtonText: "Batal",
    }).then((res) => {
      if (!res.isConfirmed) {
        return;
      }
      window.location.href = toRelative(buildEndpoint(downloadBase, name));
    });
  };

  const handlePrint = (name) => {
    if (!name) {
      return;
    }
    window.open(toRelative(buildEndpoint(printBase, name)), "_blank");
  };

  const handleRegen = (name) => {
    if (!name) {
      return;
    }

    Swal.fire({
      title: "Regenerate QR?",
      text: "Semua QR di album ini akan dibuat ulang.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Ya, regenerate",
      cancelButtonText: "Batal",
    }).then(async (result) => {
      if (!result.isConfirmed) {
        return;
      }

      Swal.fire({
        title: "Memproses...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
      });

      try {
        const res = await fetch(toRelative(buildEndpoint(regenBase, name)), {
          headers: { "X-Requested-With": "XMLHttpRequest" },
        });
        const json = await res.json();

        if (!json.status) {
          throw new Error("Regenerate gagal");
        }

        await Swal.fire({
          icon: "success",
          title: "Berhasil",
          text: json.message || "QR berhasil diregenerate.",
          timer: 1300,
          showConfirmButton: false,
        });

        openAlbum(name);
      } catch (error) {
        Swal.fire({
          icon: "error",
          title: "Gagal",
          text: "Terjadi kesalahan saat regenerate QR.",
        });
      }
    });
  };

  albumGrid.addEventListener("click", (event) => {
    const card = event.target.closest(".qr-album-card");
    if (!card) {
      return;
    }

    const name = card.dataset.name || "";
    openAlbum(name);
  });

  albumAjax.addEventListener("click", (event) => {
    const btn = event.target.closest(".qr-album-action");
    if (!btn) {
      return;
    }

    const action = btn.dataset.action || "";
    const name = btn.dataset.album || activeAlbumName;

    if (action === "download") {
      handleDownload(name);
      return;
    }

    if (action === "print") {
      handlePrint(name);
      return;
    }

    if (action === "regen") {
      handleRegen(name);
    }
  });

  btnReset.addEventListener("click", () => {
    searchInput.value = "";
    applyAlbumFilter();
  });

  searchInput.addEventListener("input", applyAlbumFilter);
  applyAlbumFilter();
})();
