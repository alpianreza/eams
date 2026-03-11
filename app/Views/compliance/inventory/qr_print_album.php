<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title>QR Preview <?= esc($itemName) ?></title>
  <style>
    :root {
      --offsetX: 0mm;
      --offsetY: 0mm;
    }

    /* BODY */
    body {
      font-family: Arial, Helvetica, sans-serif;
      margin: 0;
      padding: 16px;
      background: #f4f6f9;
    }

    /* CARD */
    .card {
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    /* PREVIEW CANVAS */
    .preview-canvas {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, .05);
      min-height: 70vh;
      overflow: auto;
    }

    /* ===== GRID (SCREEN) ===== */
    .grid {
      display: grid;

      /* FIXED columns — penting */
      grid-template-columns: repeat(auto-fill, var(--w, 6cm));

      justify-content: start;
      gap: 2mm;

      padding-bottom: 14mm;
      /* bottom buffer print */
      transform: translate(var(--offsetX), var(--offsetY));
    }

    /* ===== LABEL ===== */
    .label {
      border: 1px solid #e5e7eb;
      background: #fff;
      padding: 1.5mm;
      box-sizing: border-box;

      width: var(--w, 6cm);
      height: var(--h, 6cm);

      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;

      border-radius: 6px;

      /* no split */
      break-inside: avoid;
      page-break-inside: avoid;
    }

    /* QR */
    .qr-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
    }

    .qr-wrap img {
      width: var(--qr, 92%);
      height: auto;
    }

    /* TEXT */
    .area {
      font-size: 11px;
      color: #6b7280;
      margin-top: 2mm;
      text-align: center;
      line-height: 1.2;
    }

    /* TEMPLATE BUTTON */
    .template-group {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .template-group button {
      flex: 1;
      padding: 10px 0;
      font-weight: 600;
      border-radius: 10px;
      border: 1px solid #dee2e6;
      background: #fff;
    }

    .template-group button.active {
      background: #0d6efd;
      color: #fff;
      border-color: #0d6efd;
    }

    .form-range {
      cursor: pointer
    }

    /* ===== PAGE RULE ===== */
    @page {
      size: A4 portrait;
      margin: 12mm;
      /* sedikit lebih aman */
    }

    /* ===== PRINT ===== */
    @media print {

      body {
        padding: 0;
        margin: 0;
        background: #fff;
      }

      /* hide control */
      .card,
      .toolbar {
        display: none !important;
      }

      /* IMPORTANT: grid tetap fixed */
      .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, var(--w));
        justify-content: start;
        gap: 2mm;

        /* buffer bawah extra */
        padding-bottom: 16mm;
      }

      .label {
        box-shadow: none;
      }

    }
  </style>
</head>

<body>
  <div class="container-fluid">

    <div class="row">

      <!-- CONTROL -->
      <div class="col-lg-3">

        <div class="card card-outline card-primary">
          <div class="card-header">
            <b>Print Label</b>
            <div class="text-muted small"><?= count($rows) ?> label</div>
          </div>

          <div class="card-body">

            <label class="fw-bold small mb-1">Template</label>
            <div class="template-group mb-3">
              <button data-t="5">5×5</button>
              <button data-t="6" class="active">6×6</button>
              <button data-t="7">7×7</button>
              <button data-t="8">8×8</button>
            </div>

            <label class="fw-bold small">Offset X</label>
            <input type="range" id="offsetX" min="-10" max="10" step="0.5" value="0" class="form-range mb-3">

            <label class="fw-bold small">Offset Y</label>
            <input type="range" id="offsetY" min="-10" max="10" step="0.5" value="0" class="form-range mb-3">

            <button class="btn btn-primary w-100" onclick="window.print()">
              Print Label
            </button>

            <div class="text-muted small mt-2">
              Matikan Headers & Footers
            </div>

          </div>
        </div>

      </div>

      <!-- PREVIEW -->
      <div class="col-lg-9">

        <div class="preview-canvas">
          <div class="grid" id="grid">

            <?php foreach ($rows as $r): ?>
              <div class="label">

                <div class="qr-wrap">
                  <img src="/uploads/qr/<?= esc($r['qr_image']) ?>">
                </div>

                <div class="area"><?= esc($r['specific_area']) ?></div>

              </div>
            <?php endforeach; ?>

          </div>

        </div>

      </div>

    </div>
  </div>


  <script>
    const grid = document.getElementById('grid');
    const offsetX = document.getElementById('offsetX');
    const offsetY = document.getElementById('offsetY');

    /* TEMPLATE BUTTON */
    document.querySelectorAll('.template-group button').forEach(btn => {
      btn.onclick = () => {

        document.querySelectorAll('.template-group button')
          .forEach(b => b.classList.remove('active'));

        btn.classList.add('active');

        applyTemplate(btn.dataset.t);

      };
    });

    function applyTemplate(val) {

      let w = '6cm';
      let h = '6cm';
      let qr = '92%';

      switch (val) {

        case '5':
          w = '5cm';
          h = '5cm';
          qr = '92%';
          break;
        case '6':
          w = '6cm';
          h = '6cm';
          qr = '92%';
          break;
        case '7':
          w = '7cm';
          h = '7cm';
          qr = '94%';
          break;
        case '8':
          w = '8cm';
          h = '8cm';
          qr = '95%';
          break;
      }

      grid.style.setProperty('--w', w);
      grid.style.setProperty('--h', h);
      grid.style.setProperty('--qr', qr);

    }

    /* OFFSET */
    function applyOffset() {
      document.documentElement.style.setProperty('--offsetX', offsetX.value + 'mm');
      document.documentElement.style.setProperty('--offsetY', offsetY.value + 'mm');
    }

    offsetX.oninput = applyOffset;
    offsetY.oninput = applyOffset;

    /* DEFAULT TEMPLATE */
    applyTemplate('6');
  </script>

</body>

</html>