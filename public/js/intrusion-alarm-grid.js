(function () {
  const page = document.querySelector('.ia-grid-page');
  if (!page) return;

  const saveUrl = page.dataset.saveUrl || '';
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
    cell.innerHTML = '<span class="ia-cell-mark"></span>';
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
        throw new Error(result.message || 'Gagal menyimpan checklist Intrusion Alarm.');
      }

      setCellState(cell, result.state || (mode === 'clear' ? 'empty' : mode));
    } catch (error) {
      console.error(error);
      alert(error.message || 'Gagal menyimpan checklist Intrusion Alarm.');
    } finally {
      busyKey = null;
      cell.classList.remove('is-saving');
    }
  };

  page.addEventListener('click', (event) => {
    const cell = event.target.closest('.ia-check-cell');
    if (!cell) return;

    const state = cell.dataset.state || 'empty';
    const nextMode = cycleMap[state] || 'ok';
    saveCell(cell, nextMode);
  });
})();
