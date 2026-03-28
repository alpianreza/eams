"use strict";

(function () {
  const $container = $("#deviceAjax");
  const $loading = $("#deviceLoadingState");
  const $search = $("#searchDevice");
  const $perPage = $("#devicePerPage");

  if (!$container.length) {
    return;
  }

  const state = {
    q: "",
    perPage: Number($perPage.val() || 20),
  };

  let request = null;
  let typingTimer = null;

  function setLoading(isLoading) {
    if ($loading.length) {
      $loading.prop("hidden", !isLoading);
    }
    $container.toggleClass("is-loading", isLoading);
  }

  function buildAjaxUrl(paginationUrl = "") {
    const params = new URLSearchParams();
    params.set("perPage", String(state.perPage || 20));

    if (state.q) {
      params.set("q", state.q);
    }

    if (paginationUrl) {
      const url = new URL(paginationUrl, window.location.origin);
      const page = url.searchParams.get("page");
      if (page) {
        params.set("page", page);
      }
    }

    return `/it/devices/ajax?${params.toString()}`;
  }

  function renderError() {
    $container.html(
      '<div class="alert alert-danger mb-0">Gagal memuat data device. Silakan coba lagi.</div>'
    );
  }

  function loadDevices(paginationUrl = "") {
    const ajaxUrl = buildAjaxUrl(paginationUrl);

    if (request && request.readyState !== 4) {
      request.abort();
    }

    setLoading(true);

    request = $.ajax({
      url: ajaxUrl,
      method: "GET",
    })
      .done((html) => {
        $container.html(html);
      })
      .fail((xhr, statusText) => {
        if (statusText === "abort") {
          return;
        }
        renderError();
      })
      .always(() => {
        setLoading(false);
      });
  }

  loadDevices();

  $search.on("input", function () {
    state.q = String(this.value || "").trim();
    window.clearTimeout(typingTimer);
    typingTimer = window.setTimeout(() => loadDevices(), 320);
  });

  $perPage.on("change", function () {
    state.perPage = Number(this.value || 20);
    loadDevices();
  });

  $(document).on("click", "#deviceAjax .pagination a", function (event) {
    const href = $(this).attr("href");
    if (!href) return;
    event.preventDefault();
    loadDevices(href);
  });
})();
