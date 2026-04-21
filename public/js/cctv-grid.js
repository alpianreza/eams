(function () {
  const page = document.querySelector('.cctv-grid-page');
  if (!page) return;

  const saveUrl = page.dataset.saveUrl || '';
  let csrfName = page.dataset.csrfName || '';
  let csrfHash = page.dataset.csrfHash || '';
  let busyKey = null;

  const setCellState = (cell, state) => {
    cell.dataset.state = state;
    cell.classList.remove('is-empty', 'is-ok', 'is-alert');

    if (state === 'ok') {
      cell.classList.add('is-ok');
      cell.innerHTML = '<i class="bi bi-check-lg"></i>';
      return;
    }

    if (state === 'not_ok' || state === 'na') {
      cell.classList.add('is-alert');
      cell.innerHTML = state === 'na' ? '<i class="bi bi-dash-lg"></i>' : '<i class="bi bi-exclamation-lg"></i>';
      return;
    }

    cell.classList.add('is-empty');
    cell.innerHTML = '<span class="cctv-cell-mark"></span>';
  };

  const saveCell = async (cell, mode) => {
    const inventoryId = cell.dataset.inventoryId;
    const periodKey = cell.dataset.periodKey;
    const requestKey = `${inventoryId}:${periodKey}`;
    if (!inventoryId || !periodKey || busyKey === requestKey) {
      return;
    }

    busyKey = requestKey;
    cell.classList.add('is-saving');

    try {
      const body = new URLSearchParams();
      body.set('inventory_id', inventoryId);
      body.set('period_key', periodKey);
      body.set('mode', mode);
      if (csrfName) {
        body.set(csrfName, csrfHash);
      }

      const response = await fetch(saveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body.toString(),
        credentials: 'same-origin',
      });

      const result = await response.json();
      csrfHash = result.csrfHash || csrfHash;

      if (!response.ok || !result.ok) {
        if (result.detailUrl) {
          window.location.href = result.detailUrl;
          return;
        }

        throw new Error(result.message || 'Gagal menyimpan checklist CCTV.');
      }

      setCellState(cell, result.state || (mode === 'ok' ? 'ok' : 'empty'));
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal menyimpan checklist CCTV.');
    } finally {
      busyKey = null;
      cell.classList.remove('is-saving');
    }
  };

  page.addEventListener('click', (event) => {
    const cell = event.target.closest('.cctv-check-cell');
    if (!cell) return;

    if (cell.dataset.offday === '1') {
      return;
    }

    const state = cell.dataset.state || 'empty';
    if (state === 'not_ok' || state === 'na') {
      const detailUrl = cell.dataset.detailUrl;
      if (detailUrl) {
        window.location.href = detailUrl;
      }
      return;
    }

    const nextMode = state === 'ok' ? 'clear' : 'ok';
    saveCell(cell, nextMode);
  });
})();
