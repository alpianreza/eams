<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Print QR Album - <?= esc($itemName) ?></title>

  <style id="dynamicPageRule">
    @page {
      size: 210mm 297mm;
      margin: 10mm;
    }
  </style>

  <style>
    :root {
      --labelW: 6cm;
      --labelH: 6cm;
      --qrSize: 92%;
      --gap: 2mm;
      --offsetX: 0mm;
      --offsetY: 0mm;
      --paperW: 210mm;
      --paperH: 297mm;
      --paperMargin: 10mm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 16px;
      font-family: "Segoe UI", Arial, Helvetica, sans-serif;
      background: linear-gradient(180deg, #eff4ff 0%, #f8fafc 100%);
      color: #0f172a;
    }

    .layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 14px;
      align-items: start;
    }

    .panel,
    .sheet {
      background: #ffffff;
      border: 1px solid #dbe2ec;
      border-radius: 14px;
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
    }

    .panel {
      position: sticky;
      top: 12px;
      padding: 14px;
      max-height: calc(100vh - 24px);
      overflow: auto;
    }

    .panel h1 {
      margin: 0;
      font-size: 1rem;
      font-weight: 700;
    }

    .panel .meta {
      margin-top: 4px;
      color: #64748b;
      font-size: 0.82rem;
    }

    .section-title {
      margin: 14px 0 6px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      color: #475569;
    }

    .template-group {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
    }

    .template-btn {
      border: 1px solid #cbd5e1;
      background: #f8fafc;
      color: #0f172a;
      border-radius: 10px;
      padding: 9px 8px;
      font-size: 0.84rem;
      font-weight: 700;
      cursor: pointer;
    }

    .template-btn.active {
      border-color: #2563eb;
      background: #2563eb;
      color: #ffffff;
    }

    .form-stack {
      display: grid;
      gap: 8px;
    }

    .field label {
      display: block;
      font-size: 0.78rem;
      color: #475569;
      font-weight: 600;
      margin-bottom: 4px;
    }

    .field select,
    .field input[type="range"] {
      width: 100%;
    }

    .field select {
      border: 1px solid #cbd5e1;
      border-radius: 10px;
      background: #fff;
      color: #0f172a;
      font-size: 0.84rem;
      padding: 8px 10px;
    }

    .field select:focus {
      outline: 2px solid rgba(37, 99, 235, 0.25);
      border-color: #2563eb;
    }

    .range-wrap {
      margin-bottom: 6px;
    }

    .range-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 4px;
      font-size: 0.78rem;
      color: #475569;
      font-weight: 600;
    }

    .print-btn {
      border: 0;
      width: 100%;
      margin-top: 6px;
      border-radius: 10px;
      padding: 11px 10px;
      background: #2563eb;
      color: #fff;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
    }

    .print-note {
      margin-top: 8px;
      font-size: 0.75rem;
      color: #64748b;
      line-height: 1.4;
    }

    .sheet {
      padding: 14px;
      min-height: 78vh;
      overflow: auto;
    }

    .sheet-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 10px;
      margin-bottom: 10px;
      font-size: 0.84rem;
      color: #475569;
      flex-wrap: wrap;
    }

    .sheet-title {
      font-size: 0.94rem;
      font-weight: 700;
      color: #0f172a;
    }

    .paper-preview-outer {
      background: #f1f5f9;
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      padding: 10px;
      overflow: auto;
    }

    .paper-preview {
      width: var(--paperW);
      min-height: var(--paperH);
      background: #fff;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      margin: 0 auto;
      padding: var(--paperMargin);
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(var(--labelW), var(--labelW)));
      justify-content: start;
      gap: var(--gap);
      transform: translate(var(--offsetX), var(--offsetY));
      padding-bottom: 10mm;
    }

    .label {
      width: var(--labelW);
      height: var(--labelH);
      border: 1px solid #94a3b8;
      border-radius: 8px;
      padding: 1.4mm;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: center;
      break-inside: avoid;
      page-break-inside: avoid;
    }

    .qr-wrap {
      width: 100%;
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .qr-wrap img {
      width: var(--qrSize);
      max-height: calc(var(--labelH) - 14mm);
      height: auto;
      object-fit: contain;
      image-rendering: auto;
    }

    .area {
      margin-top: 1.6mm;
      width: 100%;
      text-align: center;
      font-size: 10.5px;
      line-height: 1.24;
      color: #1f2937;
      font-weight: 600;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      padding: 0 1mm;
    }

    .empty-state {
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      text-align: center;
      color: #64748b;
      padding: 34px 14px;
      background: #f8fafc;
    }

    @media (max-width: 1100px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .panel {
        position: static;
        max-height: none;
      }
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
        margin: 0;
      }

      .panel,
      .sheet-head {
        display: none !important;
      }

      .sheet {
        border: 0;
        box-shadow: none;
        border-radius: 0;
        padding: 0;
        min-height: auto;
        overflow: visible;
      }

      .paper-preview-outer,
      .paper-preview {
        border: 0;
        box-shadow: none;
        border-radius: 0;
        background: transparent;
        width: auto;
        min-height: auto;
        padding: 0;
      }

      .grid {
        gap: var(--gap);
        padding-bottom: 0;
      }

      .label {
        box-shadow: none;
      }
    }
  </style>
</head>

<body>
  <?php $labelCount = count($rows ?? []); ?>

  <main class="layout">
    <aside class="panel">
      <h1>Print Label QR</h1>
      <div class="meta"><?= esc($itemName) ?> | <?= (int) $labelCount ?> label</div>

      <div class="section-title">Ukuran Label</div>
      <div class="template-group" id="templateGroup">
        <button type="button" class="template-btn" data-template="5">5 x 5 cm</button>
        <button type="button" class="template-btn active" data-template="6">6 x 6 cm</button>
        <button type="button" class="template-btn" data-template="7">7 x 7 cm</button>
        <button type="button" class="template-btn" data-template="8">8 x 8 cm</button>
      </div>

      <div class="section-title">Kertas & Orientasi</div>
      <div class="form-stack">
        <div class="field">
          <label for="paperSize">Ukuran Kertas</label>
          <select id="paperSize" class="form-select form-select-sm">
            <option value="A4" selected>A4 (210 x 297 mm)</option>
            <option value="LETTER">Letter (216 x 279 mm)</option>
            <option value="F4">F4 / Folio (210 x 330 mm)</option>
            <option value="A5">A5 (148 x 210 mm)</option>
          </select>
        </div>

        <div class="field">
          <label for="paperOrientation">Orientasi</label>
          <select id="paperOrientation" class="form-select form-select-sm">
            <option value="portrait" selected>Portrait</option>
            <option value="landscape">Landscape</option>
          </select>
        </div>

        <div class="field">
          <label for="pageMargin">Margin Cetak</label>
          <select id="pageMargin" class="form-select form-select-sm">
            <option value="5">5 mm</option>
            <option value="8">8 mm</option>
            <option value="10" selected>10 mm</option>
            <option value="12">12 mm</option>
            <option value="15">15 mm</option>
          </select>
        </div>
      </div>

      <div class="section-title">Posisi Cetak</div>

      <div class="range-wrap">
        <div class="range-head">
          <span>Offset X</span>
          <span id="offsetXVal">0 mm</span>
        </div>
        <input type="range" id="offsetX" min="-10" max="10" step="0.5" value="0">
      </div>

      <div class="range-wrap">
        <div class="range-head">
          <span>Offset Y</span>
          <span id="offsetYVal">0 mm</span>
        </div>
        <input type="range" id="offsetY" min="-10" max="10" step="0.5" value="0">
      </div>

      <button type="button" class="print-btn" onclick="window.print()">Print Sekarang</button>

      <div class="print-note">
        Tip: saat print, nonaktifkan header/footer browser agar layout label lebih presisi.
      </div>
    </aside>

    <section class="sheet">
      <div class="sheet-head">
        <div class="sheet-title">Preview Label - <?= esc($itemName) ?></div>
        <div id="paperMeta">A4 | Portrait | Margin 10 mm</div>
      </div>

      <?php if (empty($rows)): ?>
        <div class="empty-state">Tidak ada data QR untuk dicetak.</div>
      <?php else: ?>
        <div class="paper-preview-outer">
          <div class="paper-preview" id="paperPreview">
            <div class="grid" id="grid">
              <?php foreach ($rows as $r): ?>
                <?php
                $qrImage = trim((string) ($r['qr_image'] ?? ''));
                $qrUrl = $qrImage !== '' ? base_url('uploads/qr/' . rawurlencode($qrImage)) : '';
                ?>
                <div class="label">
                  <div class="qr-wrap">
                    <?php if ($qrUrl !== ''): ?>
                      <img src="<?= esc($qrUrl) ?>" alt="QR <?= esc($r['asset_code'] ?? '-') ?>">
                    <?php endif; ?>
                  </div>
                  <div class="area" title="<?= esc($r['specific_area'] ?? '-') ?>">
                    <?= esc($r['specific_area'] ?? '-') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <script>
    const root = document.documentElement;
    const grid = document.getElementById("grid");
    const templateButtons = document.querySelectorAll(".template-btn");
    const offsetX = document.getElementById("offsetX");
    const offsetY = document.getElementById("offsetY");
    const offsetXVal = document.getElementById("offsetXVal");
    const offsetYVal = document.getElementById("offsetYVal");

    const paperSize = document.getElementById("paperSize");
    const paperOrientation = document.getElementById("paperOrientation");
    const pageMargin = document.getElementById("pageMargin");
    const paperMeta = document.getElementById("paperMeta");
    const dynamicPageRule = document.getElementById("dynamicPageRule");

    const paperConfigs = {
      A4: { label: "A4", w: 210, h: 297 },
      LETTER: { label: "Letter", w: 216, h: 279 },
      F4: { label: "F4/Folio", w: 210, h: 330 },
      A5: { label: "A5", w: 148, h: 210 },
    };

    function applyTemplate(size) {
      let width = "6cm";
      let height = "6cm";
      let qrSize = "92%";

      if (size === "5") {
        width = "5cm";
        height = "5cm";
        qrSize = "91%";
      }

      if (size === "7") {
        width = "7cm";
        height = "7cm";
        qrSize = "93%";
      }

      if (size === "8") {
        width = "8cm";
        height = "8cm";
        qrSize = "95%";
      }

      root.style.setProperty("--labelW", width);
      root.style.setProperty("--labelH", height);
      root.style.setProperty("--qrSize", qrSize);
    }

    function applyOffset() {
      root.style.setProperty("--offsetX", offsetX.value + "mm");
      root.style.setProperty("--offsetY", offsetY.value + "mm");
      offsetXVal.textContent = offsetX.value + " mm";
      offsetYVal.textContent = offsetY.value + " mm";
    }

    function applyPaperSettings() {
      const paperKey = paperSize.value || "A4";
      const orientation = paperOrientation.value || "portrait";
      const margin = parseInt(pageMargin.value || "10", 10);
      const config = paperConfigs[paperKey] || paperConfigs.A4;

      let widthMm = config.w;
      let heightMm = config.h;

      if (orientation === "landscape") {
        const oldWidth = widthMm;
        widthMm = heightMm;
        heightMm = oldWidth;
      }

      root.style.setProperty("--paperW", widthMm + "mm");
      root.style.setProperty("--paperH", heightMm + "mm");
      root.style.setProperty("--paperMargin", margin + "mm");

      dynamicPageRule.textContent = `@page { size: ${widthMm}mm ${heightMm}mm; margin: ${margin}mm; }`;

      const orientationLabel = orientation === "landscape" ? "Landscape" : "Portrait";
      paperMeta.textContent = `${config.label} | ${orientationLabel} | Margin ${margin} mm`;
    }

    templateButtons.forEach((button) => {
      button.addEventListener("click", () => {
        templateButtons.forEach((btn) => btn.classList.remove("active"));
        button.classList.add("active");
        applyTemplate(button.dataset.template || "6");
      });
    });

    if (offsetX && offsetY) {
      offsetX.addEventListener("input", applyOffset);
      offsetY.addEventListener("input", applyOffset);
      applyOffset();
    }

    [paperSize, paperOrientation, pageMargin].forEach((el) => {
      el.addEventListener("change", applyPaperSettings);
    });

    if (grid) {
      applyTemplate("6");
      applyPaperSettings();
    }
  </script>
</body>

</html>
