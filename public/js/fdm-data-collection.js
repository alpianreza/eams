window.fdmDataCollectionIndex = function fdmDataCollectionIndex(boot = {}) {
  return {
    query: '',
    collections: Array.isArray(boot.collections) ? boot.collections : [],

    get filteredCollections() {
      const keyword = (this.query || '').trim().toLowerCase();

      if (!keyword) {
        return this.collections;
      }

      return this.collections.filter((collection) => {
        return [collection.title, collection.subtitle, collection.status]
          .filter(Boolean)
          .some((value) => String(value).toLowerCase().includes(keyword));
      });
    },
  };
};

window.fdmProductionSectionPage = function fdmProductionSectionPage(boot = {}) {
  return {
    selectedYear: Number(boot.selectedYear || new Date().getFullYear()),
    availableYears: Array.isArray(boot.availableYears) ? boot.availableYears : [],
    monthSequence: Object.entries(boot.monthLabels || {}).map(([key, label]) => ({ key, label })),
    aggregateRow: boot.aggregateRow || { label: 'a) Finished Product Assembler', frequency: 'Monthly' },
    retailers: Array.isArray(boot.retailers) ? boot.retailers.map((retail, index) => ({
      id: retail.id ?? null,
      key: retail.key || '',
      label: retail.label || '',
      frequency: retail.frequency || 'Monthly',
      logoPath: retail.logoPath || null,
      values: { ...(retail.values || {}) },
      uid: retail.id ? `retail-${retail.id}` : `retail-new-${index}-${Date.now()}`,
    })) : [],
    workforce: boot.workforce || {
      id: null,
      key: 'full_time_employee',
      label: 'b) Number of Full Time employee',
      frequency: 'Monthly',
      values: {},
    },
    saveUrl: boot.saveUrl || '/fdm-data-collection/production-section/save',
    csrfName: window.FDM_PRODUCTION_SECTION_CSRF?.name || '',
    csrfHash: window.FDM_PRODUCTION_SECTION_CSRF?.hash || '',
    saveState: 'idle',
    saveTimer: null,
    requestCounter: 0,

    get aggregateValues() {
      const totals = {};
      this.monthSequence.forEach((month) => {
        totals[month.key] = this.retailers.reduce((sum, retail) => {
          return sum + this.toNumber(retail.values?.[month.key]);
        }, 0);
      });

      return totals;
    },

    get saveStateClass() {
      return {
        'is-idle': this.saveState === 'idle',
        'is-dirty': this.saveState === 'dirty',
        'is-saving': this.saveState === 'saving',
        'is-saved': this.saveState === 'saved',
        'is-error': this.saveState === 'error',
      };
    },

    get saveMessage() {
      const messages = {
        idle: 'Siap diisi',
        dirty: 'Perubahan belum tersimpan',
        saving: 'Menyimpan otomatis...',
        saved: 'Tersimpan',
        error: 'Gagal menyimpan',
      };

      return messages[this.saveState] || messages.idle;
    },

    yearHref(year) {
      return `/fdm-data-collection/production-section?year=${encodeURIComponent(year)}`;
    },

    addRetail() {
      this.retailers.push({
        id: null,
        key: '',
        label: 'Retail Baru',
        frequency: 'Monthly',
        logoPath: null,
        values: this.blankValues(),
        uid: `retail-new-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
      });
      this.queueSave();
    },

    removeRetail(index) {
      this.retailers.splice(index, 1);
      this.queueSave();
    },

    blankValues() {
      const values = {};
      this.monthSequence.forEach((month) => {
        values[month.key] = 0;
      });
      return values;
    },

    queueSave() {
      this.saveState = 'dirty';
      window.clearTimeout(this.saveTimer);
      this.saveTimer = window.setTimeout(() => this.save(), 500);
    },

    async save() {
      const payloadId = ++this.requestCounter;
      this.saveState = 'saving';

      const body = new FormData();
      body.append('report_year', String(this.selectedYear));
      body.append('retailers_json', JSON.stringify(this.retailers.map((retail) => ({
        id: retail.id,
        key: retail.key,
        label: retail.label,
        values: retail.values,
      }))));
      body.append('workforce_json', JSON.stringify(this.workforce.values || {}));
      if (this.csrfName) {
        body.append(this.csrfName, this.csrfHash);
      }

      try {
        const response = await fetch(this.saveUrl, {
          method: 'POST',
          body,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        });

        const contentType = response.headers.get('content-type') || '';
        const result = contentType.includes('application/json') ? await response.json() : null;

        if (!response.ok || !result?.ok || !result?.payload) {
          throw new Error(result?.message || 'Gagal menyimpan data production section.');
        }

        if (payloadId !== this.requestCounter) {
          return;
        }

        this.applyPayload(result.payload);
        this.csrfHash = result.csrfHash || this.csrfHash;
        this.saveState = 'saved';
      } catch (error) {
        console.error(error);
        this.saveState = 'error';
      }
    },

    applyPayload(payload) {
      this.selectedYear = Number(payload.selectedYear || this.selectedYear);
      this.availableYears = Array.isArray(payload.availableYears) ? payload.availableYears : this.availableYears;
      this.aggregateRow = payload.aggregateRow || this.aggregateRow;
      this.retailers = Array.isArray(payload.retailers) ? payload.retailers.map((retail, index) => ({
        id: retail.id ?? null,
        key: retail.key || '',
        label: retail.label || '',
        frequency: retail.frequency || 'Monthly',
        logoPath: retail.logoPath || null,
        values: { ...(retail.values || {}) },
        uid: retail.id ? `retail-${retail.id}` : `retail-new-${index}-${Date.now()}`,
      })) : this.retailers;
      this.workforce = payload.workforce || this.workforce;
    },

    toNumber(value) {
      const parsed = Number.parseFloat(String(value ?? 0).replace(',', '.'));
      return Number.isFinite(parsed) ? parsed : 0;
    },

    formatNumber(value) {
      return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
      }).format(this.toNumber(value));
    },

    logoText(label) {
      const words = String(label || '')
        .replace(/^[^A-Za-z0-9]+/, '')
        .split(/\s+/)
        .filter(Boolean);

      if (words.length === 0) {
        return 'R';
      }

      if (words.length === 1) {
        return words[0].slice(0, 2).toUpperCase();
      }

      return `${words[0][0] || ''}${words[1][0] || ''}`.toUpperCase();
    },

    logoStyle(label) {
      const seed = String(label || 'Retail')
        .split('')
        .reduce((total, char) => total + char.charCodeAt(0), 0);
      const hue = seed % 360;
      return `background: linear-gradient(135deg, hsla(${hue}, 78%, 52%, 0.18), hsla(${(hue + 44) % 360}, 82%, 62%, 0.28)); color: hsl(${hue}, 68%, 32%);`;
    },
  };
};
