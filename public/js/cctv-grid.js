(function () {
  const page = document.querySelector('.cctv-grid-page');
  if (!page) return;

  const saveUrl = page.dataset.saveUrl || '';
  const bulkUrl = page.dataset.bulkUrl || '';
  const ym = page.dataset.ym || '';
  let csrfName = page.dataset.csrfName || '';
  let csrfHash = page.dataset.csrfHash || '';
  let busyKey = null;

  const cycleMap = {
    empty: 'ok',
    ok: 'not_ok',
    not_ok: 'clear',
  };

  const setCellState = (cell, state) => {
    cell.dataset.state = state;
    cell.classList.remove('is-empty', 'is-ok', 'is-alert');

    if (state === 'ok') {
      cell.classList.add('is-ok');
      cell.replaceChildren(document.createElement('i'));
      cell.firstChild.className = 'bi bi-check-lg';
      return;
    }

    if (state === 'not_ok' || state === 'na') {
      cell.classList.add('is-alert');
      cell.replaceChildren(document.createElement('i'));
      cell.firstChild.className = state === 'na' ? 'bi bi-dash-lg' : 'bi bi-exclamation-lg';
      return;
    }

    cell.classList.add('is-empty');
    cell.replaceChildren(document.createElement('span'));
    cell.firstChild.className = 'cctv-cell-mark';
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

  const setAllEmptyCellsToOk = () => {
    page.querySelectorAll('.cctv-check-cell').forEach((cell) => {
      if (cell.dataset.offday === '1') return;
      const state = cell.dataset.state || 'empty';
      if (state !== 'empty') return;
      setCellState(cell, 'ok');
    });
  };

  const markAll = async () => {
    if (!bulkUrl || !ym || busyKey === '__bulk__') {
      return;
    }

    busyKey = '__bulk__';
    const button = page.querySelector('.cctv-mark-all-btn');
    if (button) {
      button.disabled = true;
    }

    try {
      const body = new URLSearchParams();
      body.set('ym', ym);
      if (csrfName) {
        body.set(csrfName, csrfHash);
      }

      const response = await fetch(bulkUrl, {
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
        throw new Error(result.message || 'Gagal mencentang semua CCTV.');
      }

      setAllEmptyCellsToOk();
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal mencentang semua CCTV.');
    } finally {
      busyKey = null;
      if (button) {
        button.disabled = false;
      }
    }
  };

  page.addEventListener('click', (event) => {
    const cell = event.target.closest('.cctv-check-cell');
    if (cell) {
      if (cell.dataset.offday === '1') {
        return;
      }

      const state = cell.dataset.state || 'empty';
      if (state === 'na') {
        const detailUrl = cell.dataset.detailUrl;
        if (detailUrl) {
          window.location.href = detailUrl;
        }
        return;
      }

      const nextMode = cycleMap[state] || 'ok';
      saveCell(cell, nextMode);
      return;
    }

    const bulkButton = event.target.closest('.cctv-mark-all-btn');
    if (bulkButton) {
      markAll();
    }
  });
})();
