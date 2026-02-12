// ===============================
// LOAD EVIDENCE GRID
// ===============================
function loadEvidence(page = 1) {
  const year = $("#filterYear").val();
  const item = $("#filterItem").val();
  const follow = $("#filterFollowUp").val(); // 🔥 filter baru

  $("#evidenceAjax").html('<div class="text-center p-5">Loading...</div>');

  $.get(
    "/compliance/evidence/ajax?page=" + page,
    {
      year: year,
      item_type: item,
      follow_up: follow, // 🔥 kirim ke controller
    },
    function (res) {
      $("#evidenceAjax").html(res);
    },
  ).fail(function () {
    $("#evidenceAjax").html(
      '<div class="text-center text-danger p-5">Gagal memuat data</div>',
    );
  });
}

// ===============================
// INITIAL LOAD + FILTER + PAGINATION
// ===============================
$(document).ready(function () {
  loadEvidence();

  $("#filterYear, #filterItem, #filterFollowUp").on("change", function () {
    loadEvidence();
  });

  // AJAX pagination
  $(document).on("click", ".pagination a", function (e) {
    e.preventDefault();
    const page = $(this).attr("href").split("page=")[1];
    loadEvidence(page);
  });
});

// ===============================
// OPEN MODAL (Bootstrap 5)
// ===============================
$(document).on("click", ".evidence-card", function () {
  const id = $(this).data("id");

  const modalEl = document.getElementById("evidenceModal");
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  $("#evidenceDetailBody").html(
    '<div class="text-center p-4">Loading...</div>',
  );

  $.get("/compliance/evidence/detail/" + id, function (res) {
    $("#evidenceDetailBody").html(res);
  }).fail(function () {
    $("#evidenceDetailBody").html(
      '<div class="text-center text-danger p-4">Gagal memuat detail</div>',
    );
  });
});

// ===============================
// SUBMIT FOLLOW UP (Bootstrap 5)
// ===============================
$(document).on("submit", "#followUpForm", function (e) {
  e.preventDefault();

  const form = $(this);

  $.post(
    "/compliance/evidence/update-followup",
    form.serialize(),
    function (res) {
      if (res.status === "success") {
        const modalEl = document.getElementById("evidenceModal");
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) modalInstance.hide();

        loadEvidence();

        safeToast(res.message, "success");
      } else {
        safeToast(res.message || "Terjadi kesalahan.", "error");
      }
    },
  ).fail(function () {
    safeToast("Gagal menghubungi server.", "error");
  });
});
