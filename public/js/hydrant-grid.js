(function () {
  const page = document.querySelector('.hyd-grid-page');
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
    cell.classList.remove('is-empty', 'is-ok', 'is-not-ok');

    if (state === 'ok') {
      cell.classList.add('is-ok');
      cell.innerHTML = '<i class="bi bi-check-lg"></i>';
      return;
    }

    if (state === 'not_ok') {
      cell.classList.add('is-not-ok');
      cell.innerHTML = '<i class="bi bi-x-lg"></i>';
      return;
    }

    cell.classList.add('is-empty');
    cell.innerHTML = '<span class="hyd-cell-mark"></span>';
  };

  const saveCell = async (cell, mode) => {
    const inventoryId = cell.dataset.inventoryId;
    const templateId = cell.dataset.templateId;
    const periodKey = cell.dataset.periodKey;
    const requestKey = `${inventoryId}:${templateId}:${periodKey}`;

    if (!inventoryId || !templateId || !periodKey || busyKey === requestKey) {
      return;
    }

    busyKey = requestKey;
    cell.classList.add('is-saving');

    try {
      const body = new URLSearchParams();
      body.set('inventory_id', inventoryId);
      body.set('template_id', templateId);
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
        throw new Error(result.message || 'Gagal menyimpan checklist Hydrant.');
      }

      setCellState(cell, result.state || (mode === 'clear' ? 'empty' : mode));
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal menyimpan checklist Hydrant.');
    } finally {
      busyKey = null;
      cell.classList.remove('is-saving');
    }
  };

  const markAll = async () => {
    if (!bulkUrl || !ym || busyKey === '__bulk__') {
      return;
    }

    busyKey = '__bulk__';
    const button = page.querySelector('.hyd-mark-all-btn');
    if (button) button.disabled = true;

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
        throw new Error(result.message || 'Gagal mencentang semua Hydrant.');
      }

      page.querySelectorAll('.hyd-check-cell').forEach((cell) => {
        const state = cell.dataset.state || 'empty';
        if (state === 'empty') {
          setCellState(cell, 'ok');
        }
      });
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal mencentang semua Hydrant.');
    } finally {
      busyKey = null;
      if (button) button.disabled = false;
    }
  };

  page.addEventListener('click', (event) => {
    const cell = event.target.closest('.hyd-check-cell');
    if (cell) {
      const state = cell.dataset.state || 'empty';
      const nextMode = cycleMap[state] || 'ok';
      saveCell(cell, nextMode);
      return;
    }

    const bulkButton = event.target.closest('.hyd-mark-all-btn');
    if (bulkButton) {
      markAll();
    }
  });
})();
