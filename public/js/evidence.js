(function () {
  const pageEl = document.getElementById("evidencePage");
  if (!pageEl) {
    return;
  }

  const $year = $("#filterYear");
  const $item = $("#filterItem");
  const $followUp = $("#filterFollowUp");
  const $grid = $("#evidenceAjax");
  const $detailBody = $("#evidenceDetailBody");

  const endpoints = {
    ajax: pageEl.dataset.urlAjax || "/compliance/evidence/ajax",
    detailBase: pageEl.dataset.urlDetailBase || "/compliance/evidence/detail",
    update: pageEl.dataset.urlUpdate || "/compliance/evidence/update-followup",
  };

  const modalEl = document.getElementById("evidenceModal");
  const evidenceModal = modalEl ? new bootstrap.Modal(modalEl) : null;

  let gridRequest = null;
  let detailRequest = null;

  const renderGridLoading = () => {
    return `
      <div class="evidence-grid-state text-center p-4">
        <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
        <div class="mt-2">Memuat evidence...</div>
      </div>
    `;
  };

  const renderGridError = (message) => {
    return `
      <div class="evidence-grid-state text-center p-4 text-danger">
        <div class="fw-semibold">${message}</div>
        <button type="button" class="btn btn-outline-danger btn-sm mt-2" id="btnEvidenceRetry">Coba Lagi</button>
      </div>
    `;
  };

  const getRequestedPage = (href) => {
    if (!href) {
      return 1;
    }

    try {
      const url = new URL(href, window.location.origin);
      const rawPage = parseInt(url.searchParams.get("page") || "1", 10);
      return Number.isFinite(rawPage) && rawPage > 0 ? rawPage : 1;
    } catch (error) {
      return 1;
    }
  };

  const loadEvidence = (page = 1) => {
    if (gridRequest && gridRequest.readyState !== 4) {
      gridRequest.abort();
    }

    $grid.html(renderGridLoading());

    gridRequest = $.ajax({
      url: endpoints.ajax,
      method: "GET",
      data: {
        page,
        year: $year.val(),
        item_type: $item.val(),
        follow_up: $followUp.val(),
      },
      timeout: 20000,
    })
      .done((res) => {
        $grid.html(res);
      })
      .fail((xhr, textStatus) => {
        if (textStatus === "abort") {
          return;
        }

        const message = xhr.status >= 500
          ? "Server sedang sibuk. Silakan coba lagi."
          : "Gagal memuat data evidence.";

        $grid.html(renderGridError(message));
      });
  };

  const loadDetail = (id) => {
    if (!id || !evidenceModal) {
      return;
    }

    if (detailRequest && detailRequest.readyState !== 4) {
      detailRequest.abort();
    }

    evidenceModal.show();
    $detailBody.html('<div class="text-center p-4">Memuat detail evidence...</div>');

    const detailUrl = `${endpoints.detailBase.replace(/\/$/, "")}/${id}`;

    detailRequest = $.ajax({
      url: detailUrl,
      method: "GET",
      timeout: 20000,
    })
      .done((res) => {
        $detailBody.html(res);
      })
      .fail((xhr, textStatus) => {
        if (textStatus === "abort") {
          return;
        }

        const message = xhr.status >= 500
          ? "Terjadi kesalahan saat memuat detail evidence."
          : "Detail evidence tidak dapat ditampilkan.";

        $detailBody.html(`<div class="text-center text-danger p-4">${message}</div>`);
      });
  };

  $year.add($item).add($followUp).on("change", () => {
    loadEvidence(1);
  });

  $("#btnEvidenceReset").on("click", () => {
    $year.val("");
    $item.val("");
    $followUp.val("");
    loadEvidence(1);
  });

  $(document).on("click", "#evidenceAjax .pagination a", function (event) {
    event.preventDefault();
    const page = getRequestedPage($(this).attr("href"));
    loadEvidence(page);
  });

  $(document).on("click", "#btnEvidenceRetry", () => {
    loadEvidence(1);
  });

  $(document).on("click", "#evidenceAjax .evidence-card-button", function () {
    const id = Number($(this).data("id"));
    if (Number.isFinite(id) && id > 0) {
      loadDetail(id);
    }
  });

  $(document).on("submit", "#followUpForm", function (event) {
    event.preventDefault();

    const $form = $(this);
    const $submit = $form.find("button[type='submit']");

    $submit.prop("disabled", true);

    $.ajax({
      url: endpoints.update,
      method: "POST",
      data: $form.serialize(),
      dataType: "json",
      timeout: 20000,
    })
      .done((res) => {
        if (res && res.status === "success") {
          if (evidenceModal) {
            evidenceModal.hide();
          }
          loadEvidence(1);
          safeToast(res.message || "Status berhasil diperbarui.", "success");
          return;
        }

        safeToast((res && res.message) || "Terjadi kesalahan saat menyimpan data.", "error");
      })
      .fail((xhr) => {
        const message = xhr.responseJSON && xhr.responseJSON.message
          ? xhr.responseJSON.message
          : "Gagal menghubungi server.";
        safeToast(message, "error");
      })
      .always(() => {
        $submit.prop("disabled", false);
      });
  });

  if (modalEl) {
    modalEl.addEventListener("hidden.bs.modal", () => {
      if (detailRequest && detailRequest.readyState !== 4) {
        detailRequest.abort();
      }
      $detailBody.html('<div class="text-center p-4">Memuat detail evidence...</div>');
    });
  }

  loadEvidence(1);
})();
