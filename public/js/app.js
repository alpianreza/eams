document.addEventListener("DOMContentLoaded", function () {
  window.safeToast = function (message, type = "success") {
    Swal.fire({
      toast: true,
      position: "top-right",
      icon: type,
      title: message,
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      customClass: {
        popup: "colored-toast",
      },
      didOpen: (toast) => {
        toast.style.marginTop = "60px";
        toast.style.marginLeft = "15px";
      },
    });
  };

  // Alias for legacy modules.
  window.appToast = window.safeToast;

  const WRAP_CLASS = "table-responsive-mobile";

  const wrapTable = (table) => {
    if (!table) return;
    if (table.closest(".table-responsive")) return;
    if (table.dataset.noResponsiveWrap === "1") return;

    const wrapper = document.createElement("div");
    wrapper.className = `table-responsive ${WRAP_CLASS}`;

    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);
  };

  const wrapTablesIn = (root) => {
    if (!root) return;

    if (root.matches?.("table.table")) {
      wrapTable(root);
      return;
    }

    root.querySelectorAll?.("table.table").forEach((table) => wrapTable(table));
  };

  const appContent = document.querySelector(".app-content");
  wrapTablesIn(appContent || document);

  if (!appContent) return;

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType !== Node.ELEMENT_NODE) return;
        wrapTablesIn(node);
      });
    });
  });

  observer.observe(appContent, {
    childList: true,
    subtree: true,
  });
});
