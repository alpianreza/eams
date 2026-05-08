(function () {
  const page = document.querySelector('.gg-grid-page');
  if (!page) return;

  const saveUrl = page.dataset.saveUrl || '';
  const bulkUrl = page.dataset.bulkUrl || '';
  const inventoryId = page.dataset.inventoryId || '';
  const ym = page.dataset.ym || '';
  const frequency = page.dataset.frequency || '';
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
    cell.innerHTML = '<span class="gg-cell-mark"></span>';
  };

  const saveCell = async (cell, mode) => {
    const templateId = cell.dataset.templateId;
    const periodKey = cell.dataset.periodKey;
    const timeSlot = cell.dataset.timeSlot || '';
    const requestKey = `${inventoryId}:${templateId}:${periodKey}:${timeSlot}`;

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
      body.set('time_slot', timeSlot);
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
        throw new Error(result.message || 'Gagal menyimpan checklist grid.');
      }

      setCellState(cell, result.state || (mode === 'clear' ? 'empty' : mode));
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal menyimpan checklist grid.');
    } finally {
      busyKey = null;
      cell.classList.remove('is-saving');
    }
  };

  const setAllEligibleCellsToOk = () => {
    page.querySelectorAll('.gg-check-cell').forEach((cell) => {
      if (cell.dataset.offday === '1') return;
      const state = cell.dataset.state || 'empty';
      if (state !== 'empty') return;
      setCellState(cell, 'ok');
    });
  };

  const markAll = async () => {
    if (!bulkUrl || !inventoryId || !ym || busyKey === '__bulk__') {
      return;
    }

    busyKey = '__bulk__';
    const button = page.querySelector('.gg-mark-all-btn');
    if (button) {
      button.disabled = true;
    }

    try {
      const body = new URLSearchParams();
      body.set('inventory_id', inventoryId);
      body.set('ym', ym);
      body.set('frequency', frequency);
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
        throw new Error(result.message || 'Gagal mencentang semua checklist grid.');
      }

      setAllEligibleCellsToOk();
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal mencentang semua checklist grid.');
    } finally {
      busyKey = null;
      if (button) {
        button.disabled = false;
      }
    }
  };

  page.addEventListener('click', (event) => {
    const cell = event.target.closest('.gg-check-cell');
    if (cell) {
      if (cell.dataset.offday === '1') return;

      const state = cell.dataset.state || 'empty';
      const nextMode = cycleMap[state] || 'ok';
      saveCell(cell, nextMode);
      return;
    }

    const bulkButton = event.target.closest('.gg-mark-all-btn');
    if (bulkButton) {
      markAll();
    }
  });
})();
