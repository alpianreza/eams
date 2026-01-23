document.addEventListener("DOMContentLoaded", function () {
  const container = document.getElementById("checklist-calendar");
  if (!container) return;

  const inventoryId = container.dataset.inventory;
  const frequency = container.dataset.frequency;
  const periods = JSON.parse(container.dataset.periods);

  const table = document.createElement("table");
  table.className = "table table-bordered text-center align-middle";

  const tbody = document.createElement("tbody");
  const row = document.createElement("tr");

  periods.forEach((p) => {
    const cell = document.createElement("td");
    cell.style.cursor = "pointer";
    cell.innerHTML = `<div class="small">${p.label}</div>`;

    // STATUS COLOR
    if (p.status === "done") {
      cell.className = "table-success";
      cell.innerHTML += "<div>✅</div>";
    } else if (p.status === "open") {
      cell.className = "table-warning";
      cell.innerHTML += "<div>✍️</div>";
      cell.onclick = () => {
        window.location.href = `/compliance/checklist/${inventoryId}/${p.period_key}`;
      };
    } else if (p.status === "holiday") {
      cell.className = "table-secondary";
      cell.innerHTML += "<div>🎌</div>";
    } else {
      cell.className = "table-light text-muted";
      cell.innerHTML += "<div>🔒</div>";
    }

    row.appendChild(cell);
  });

  tbody.appendChild(row);
  table.appendChild(tbody);
  container.appendChild(table);
});
