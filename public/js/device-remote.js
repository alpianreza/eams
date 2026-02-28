$(document).on("click", ".remote-btn", function () {
  const id = $(this).data("id");
  const action = $(this).data("action");

  if (!confirm("Execute " + action + " ?")) return;

  $.post("/it/device/remote", { id, action }, (res) => {
    alert("Command sent");
  });
});
