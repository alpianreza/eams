(function () {
  const page = document.querySelector('.sd-grid-page');
  if (!page) return;

  const saveUrl = page.dataset.saveUrl || '';
  const bulkUrl = page.dataset.bulkUrl || '';
  const periodKey = page.dataset.periodKey || '';
  const itemLabel = page.dataset.itemLabel || 'Checklist';
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
    cell.innerHTML = '<span class="sd-cell-mark"></span>';
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
        throw new Error(result.message || 'Gagal menyimpan checklist Smoke Detector.');
      }

      setCellState(cell, result.state || (mode === 'clear' ? 'empty' : mode));
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal menyimpan checklist Smoke Detector.');
    } finally {
      busyKey = null;
      cell.classList.remove('is-saving');
    }
  };

  const markAll = async () => {
    if (!bulkUrl || !periodKey || busyKey === '__bulk__') {
      return;
    }

    busyKey = '__bulk__';
    const button = page.querySelector('.sd-mark-all-btn');
    if (button) {
      button.disabled = true;
    }

    try {
      const body = new URLSearchParams();
      body.set('period_key', periodKey);
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
        throw new Error(result.message || `Gagal mencentang semua ${itemLabel}.`);
      }

      page.querySelectorAll('.sd-check-cell').forEach((cell) => setCellState(cell, 'ok'));
    } catch (error) {
      console.error(error);
      alert(error.message || `Gagal mencentang semua ${itemLabel}.`);
    } finally {
      busyKey = null;
      if (button) {
        button.disabled = false;
      }
    }
  };

  page.addEventListener('click', (event) => {
    const cell = event.target.closest('.sd-check-cell');
    if (cell) {
      const state = cell.dataset.state || 'empty';
      const nextMode = cycleMap[state] || 'ok';
      saveCell(cell, nextMode);
      return;
    }

    const bulkButton = event.target.closest('.sd-mark-all-btn');
    if (bulkButton) {
      markAll();
    }
  });
})();
