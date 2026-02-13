document.addEventListener("DOMContentLoaded", function () {
  const data = window.dashboardData;

  // ===============================
  // TREND CHART
  // ===============================

  // ===============================
  // STATUS PIE
  // ===============================
  if (document.getElementById("statusChart") && data.statusData) {
    new Chart(document.getElementById("statusChart"), {
      type: "doughnut",
      data: {
        labels: ["✓ Sesuai", "✗ Tidak Sesuai", "– Tidak Berlaku", "Late"],
        datasets: [
          {
            data: data.statusData,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
      },
    });
  }
});

// ===============================
// IMAGE MODAL
// ===============================
window.showImageModal = function (src) {
  document.getElementById("modalImage").src = src;
  new bootstrap.Modal(document.getElementById("imageModal")).show();
};

document.addEventListener("DOMContentLoaded", function () {
  const data = window.dashboardData || {};

  if (document.getElementById("trendChart") && data.complianceTrend) {
    const sesuaiData = data.complianceTrend.map((m) => m.sesuai);
    const lateData = data.complianceTrend.map((m) => m.late);
    const pendingData = data.complianceTrend.map((m) => m.pending);
    const rateData = data.complianceTrend.map((m) => m.rate);

    new Chart(document.getElementById("trendChart"), {
      data: {
        labels: [
          "Jan",
          "Feb",
          "Mar",
          "Apr",
          "Mei",
          "Jun",
          "Jul",
          "Agu",
          "Sep",
          "Okt",
          "Nov",
          "Des",
        ],
        datasets: [
          {
            type: "bar",
            label: "✓ Sesuai",
            data: sesuaiData,
            backgroundColor: "#28a745",
          },
          {
            type: "bar",
            label: "⚠ Late",
            data: lateData,
            backgroundColor: "#ffc107",
          },
          {
            type: "bar",
            label: "⏳ Pending",
            data: pendingData,
            backgroundColor: "#dc3545",
          },
          {
            type: "line",
            label: "Compliance Rate (%)",
            data: rateData,
            borderColor: "#000",
            tension: 0.3,
            yAxisID: "y1",
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index + 1; // bulan 1–12
            const selectedYear =
              new URLSearchParams(window.location.search).get("year") ||
              new Date().getFullYear();

            window.location.href = `?year=${selectedYear}&month=${index}`;
          }
        },
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true },
          y1: { position: "right", max: 100 },
        },

        onClick: function (event, elements) {
          if (elements.length > 0) {
            const monthIndex = elements[0].index + 1;
            const params = new URLSearchParams(window.location.search);
            const year = params.get("year") || new Date().getFullYear();

            fetch(`/compliance/dashboard/data?year=${year}&month=${monthIndex}`)
              .then((res) => res.json())
              .then((response) => {
                updateKPI(response.kpi);
                updateNotifications(response.notifications);
                updatePhotos(response.notOkPhotos);
                updateOverview(response.overview);
              });
          }
        },
      },
    });
  }
});

function updateKPI(kpi) {
  document.querySelectorAll(".card-title")[1].innerText = kpi.sesuai;
  document.querySelectorAll(".card-title")[2].innerText = kpi.tidak_sesuai;
  document.querySelectorAll(".card-title")[3].innerText = kpi.tidak_berlaku;
}

function updateNotifications(notifications) {
  const list = document.querySelector(".list-group");
  list.innerHTML = "";

  if (!notifications.length) {
    list.innerHTML =
      '<li class="list-group-item text-muted">Tidak ada notifikasi</li>';
    return;
  }

  notifications.forEach((n) => {
    const li = document.createElement("li");
    li.className = "list-group-item";

    li.innerHTML = `
            <a href="/compliance/inventory/detail/${n.inventory_id}" class="text-decoration-none d-block">
                <span class="${n.type === "not_ok" ? "text-danger" : "text-warning"}">
                    ${n.type === "not_ok" ? "✗" : "⚠"} ${n.item} - ${n.area} → ${n.message}
                </span>
            </a>
        `;

    list.appendChild(li);
  });
}

function updatePhotos(photos) {
  const container = document.querySelector(".card-body .row");
  container.innerHTML = "";

  if (!photos.length) {
    container.innerHTML = `
            <div class="col-12 text-muted text-center">
                Tidak ada temuan tidak sesuai
            </div>
        `;
    return;
  }

  photos.forEach((log) => {
    const col = document.createElement("div");
    col.className = "col-6 mb-3";

    col.innerHTML = `
            <div class="card">
                <img src="/uploads/checklist/${log.photo}"
                     class="card-img-top"
                     style="height:150px; object-fit:cover; cursor:pointer;"
                     onclick="showImageModal(this.src)">
                <div class="card-body p-2">
                    <small class="text-danger">
                        ✗ ${log.remark ?? ""}
                    </small>
                </div>
            </div>
        `;

    container.appendChild(col);
  });
}

function updateOverview(overview) {
  const tbody = document.querySelector("table tbody");
  tbody.innerHTML = "";

  if (!overview.length) {
    tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    Tidak ada data
                </td>
            </tr>
        `;
    return;
  }

  overview.forEach((row) => {
    const tr = document.createElement("tr");

    if (row.raw_status === "late") {
      tr.classList.add("table-warning");
    }

    tr.innerHTML = `
            <td>${row.item}</td>
            <td>${row.area}</td>
            <td>${row.frequency}</td>
            <td class="text-center">${row.status}</td>
            <td class="text-center">
                <a href="/compliance/inventory/detail/${row.id}"
                   class="btn btn-sm btn-outline-primary">
                    Detail
                </a>
            </td>
        `;

    tbody.appendChild(tr);
  });
}
