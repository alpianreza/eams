function loadEvidence(page = 1) {
  const year = $("#filterYear").val();
  const item = $("#filterItem").val();
  const area = $("#filterArea").val();

  $("#evidenceAjax").html('<div class="text-center p-5">Loading...</div>');

  $.get(
    "/compliance/evidence/ajax?page=" + page,
    {
      year: year,
      item_type: item,
      area: area,
    },
    function (res) {
      $("#evidenceAjax").html(res);
    },
  );
}

$(document).ready(function () {
  loadEvidence();

  $("#filterYear, #filterItem, #filterArea").change(function () {
    loadEvidence();
  });

  // handle pagination click (AJAX)
  $(document).on("click", ".pagination a", function (e) {
    e.preventDefault();
    const page = $(this).attr("href").split("page=")[1];
    loadEvidence(page);
  });
});
