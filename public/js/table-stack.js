/*
 * Tabel jadi kartu bertumpuk di ponsel.
 *
 * Mengisi atribut data-label pada setiap sel dari teks kepala kolom,
 * lalu menandai tabelnya dengan kelas .is-stacked. Sisanya urusan
 * table-stack.css.
 *
 * Dibuat begini supaya tidak ada satu pun file View yang perlu
 * disunting satu per satu.
 *
 * Kendali manual di markup:
 *   data-stack="1"  paksa aktif, lewati semua pemeriksaan
 *   data-stack="0"  jangan pernah ditumpuk
 *
 * Untuk tabel yang dimuat lewat AJAX, panggil ulang:
 *   window.eamsTableStack.refresh(elemenInduk)
 */
(function () {
  'use strict';

  var MIN_KOLOM = 2;
  var MAKS_KOLOM = 7;

  function teksBersih(el) {
    return (el.textContent || '').replace(/\s+/g, ' ').trim();
  }

  /*
   * Tabel hanya ditumpuk kalau strukturnya benar-benar sederhana.
   * Kalau ragu, biarkan apa adanya: tabel yang bisa digeser masih jauh
   * lebih baik daripada kartu yang labelnya meleset.
   */
  function bolehDitumpuk(table) {
    var pilihan = table.getAttribute('data-stack');
    if (pilihan === '0') return false;
    if (pilihan === '1') return true;

    // Grid checklist punya kolom lengket, jangan pernah diutak-atik.
    if (table.className && /-grid-table/.test(table.className)) return false;

    var barisKepala = table.tHead && table.tHead.rows.length === 1
      ? table.tHead.rows[0]
      : null;
    if (!barisKepala) return false;

    var kepala = barisKepala.cells;
    if (kepala.length < MIN_KOLOM || kepala.length > MAKS_KOLOM) return false;

    for (var i = 0; i < kepala.length; i++) {
      if (kepala[i].colSpan > 1 || kepala[i].rowSpan > 1) return false;
    }

    var tbody = table.tBodies[0];
    if (!tbody || tbody.rows.length === 0) return false;

    for (var r = 0; r < tbody.rows.length; r++) {
      var sel = tbody.rows[r].cells;

      // Baris pengelompokan atau baris "data kosong" — lewati tabelnya.
      if (sel.length !== kepala.length) return false;

      for (var c = 0; c < sel.length; c++) {
        if (sel[c].colSpan > 1 || sel[c].rowSpan > 1) return false;
      }
    }

    return true;
  }

  function pasang(table) {
    var barisKepala = table.tHead.rows[0];
    var judul = [];

    for (var i = 0; i < barisKepala.cells.length; i++) {
      judul.push(teksBersih(barisKepala.cells[i]));
    }

    var tbody = table.tBodies[0];
    for (var r = 0; r < tbody.rows.length; r++) {
      var sel = tbody.rows[r].cells;
      for (var c = 0; c < sel.length; c++) {
        sel[c].setAttribute('data-label', judul[c] || '');
      }
    }

    table.classList.add('is-stacked');

    var pembungkus = table.closest('.table-responsive');
    if (pembungkus) pembungkus.classList.add('has-stacked');
  }

  function jalankan(induk) {
    var akar = induk || document;
    var daftar = akar.querySelectorAll('table');

    for (var i = 0; i < daftar.length; i++) {
      var table = daftar[i];
      if (table.classList.contains('is-stacked')) continue;
      if (!bolehDitumpuk(table)) continue;
      pasang(table);
    }
  }

  window.eamsTableStack = { refresh: jalankan };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { jalankan(); });
  } else {
    jalankan();
  }
})();
