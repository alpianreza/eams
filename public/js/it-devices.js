function loadDevices(q = "") {
  $("#deviceAjax").load("/it/devices/ajax?q=" + q);
}

$(document).ready(function () {
  loadDevices();

  $("#searchDevice").on("keyup", function () {
    loadDevices(this.value);
  });
});

$(document).on("click", ".cmd", function () {
  $.post("/it/device/command", {
    id: $(this).data("id"),
    cmd: $(this).data("cmd"),
  });
});
