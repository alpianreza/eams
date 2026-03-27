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

  const mobileMedia = window.matchMedia("(max-width: 992px)");
  const closeSidebar = () => {
    document.body.classList.remove("sidebar-open");
  };

  document.addEventListener("click", (event) => {
    if (!mobileMedia.matches) return;

    const sidebar = document.querySelector(".app-sidebar");
    if (!sidebar) return;

    const navLink = event.target.closest(".app-sidebar .nav-link[href]");
    const isCollapseTrigger = navLink?.getAttribute("data-bs-toggle") === "collapse";

    if (navLink && !isCollapseTrigger) {
      closeSidebar();
      return;
    }

    const isSidebarToggle = event.target.closest('[data-lte-toggle="sidebar"]');
    if (isSidebarToggle) return;

    const clickInsideSidebar = event.target.closest(".app-sidebar");
    if (document.body.classList.contains("sidebar-open") && !clickInsideSidebar) {
      closeSidebar();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (!mobileMedia.matches) return;
    if (event.key === "Escape") {
      closeSidebar();
    }
  });
});
