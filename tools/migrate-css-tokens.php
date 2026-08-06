<?php

/**
 * =====================================================================
 * MIGRASI WARNA HARDCODE KE DESIGN TOKEN
 * =====================================================================
 *
 * Mengganti nilai hex yang ditulis langsung di file CSS menjadi
 * var(--c-*) yang didefinisikan di public/assets/css/tokens.css.
 *
 * Kenapa lewat skrip, bukan diedit satu per satu?
 * Pekerjaan ini murni mekanis dan tersebar di ~160 KB CSS. Skrip
 * menjaminnya konsisten, bisa diulang, dan hasilnya bisa kamu periksa
 * lewat `git diff` sebelum di-commit.
 *
 * ---------------------------------------------------------------------
 * CARA PAKAI
 * ---------------------------------------------------------------------
 *   php tools/migrate-css-tokens.php              # lihat rencana saja
 *   php tools/migrate-css-tokens.php --apply      # tulis perubahan
 *   php tools/migrate-css-tokens.php --apply --file=app.css
 *
 * SELALU jalankan tanpa --apply dulu, lalu:
 *   git diff --stat
 *   git diff public/assets/css/app.css
 *
 * Kalau hasilnya tidak sesuai:
 *   git checkout -- public/assets/css
 * =====================================================================
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Skrip ini hanya untuk dijalankan dari terminal.\n");
}

$cssDir = dirname(__DIR__) . '/public/assets/css';

/**
 * File yang TIDAK boleh disentuh.
 *
 * - tokens.css        : sumber definisi token itu sendiri
 * - checklist-grid.css: sudah memakai token
 * - compat-tailwind.css: sudah memakai token
 * - pdf.css           : dompdf & mpdf tidak mendukung var() dengan andal,
 *                       PDF akan kehilangan warna kalau ikut diganti
 */
$skipFiles = [
    'tokens.css',
    'checklist-grid.css',
    'compat-tailwind.css',
    'pdf.css',
];

/**
 * Peta warna hasil audit seluruh file CSS.
 *
 * Satu hex selalu dipetakan ke satu token, apa pun propertinya, supaya
 * hasilnya bisa ditebak. Kunci ditulis huruf kecil.
 */
$colorMap = [
    // ---------- Netral / permukaan ----------
    '#ffffff' => 'var(--c-surface)',
    '#fff'    => 'var(--c-surface)',
    '#fefefe' => 'var(--c-surface)',
    '#f8fafc' => 'var(--c-surface-sunk)',
    '#f9fafb' => 'var(--c-surface-sunk)',
    '#f1f5f9' => 'var(--c-surface-hover)',
    '#f3f4f6' => 'var(--c-surface-hover)',
    '#f3f6fc' => 'var(--c-canvas)',
    '#f7f8fa' => 'var(--c-canvas)',

    // ---------- Garis ----------
    '#e2e8f0' => 'var(--c-border)',
    '#e5e7eb' => 'var(--c-border)',
    '#dbe4f1' => 'var(--c-border)',
    '#e3e7ee' => 'var(--c-border)',
    '#cbd5e1' => 'var(--c-border-strong)',
    '#d1d5db' => 'var(--c-border-strong)',
    '#d0d5dd' => 'var(--c-border-strong)',

    // ---------- Teks ----------
    '#94a3b8' => 'var(--c-text-subtle)',
    '#98a2b3' => 'var(--c-text-subtle)',
    '#9ca3af' => 'var(--c-text-subtle)',
    '#64748b' => 'var(--c-text-muted)',
    '#667085' => 'var(--c-text-muted)',
    '#6b7280' => 'var(--c-text-muted)',
    '#334155' => 'var(--c-text)',
    '#1e293b' => 'var(--c-text)',
    '#0f172a' => 'var(--c-text)',
    '#101828' => 'var(--c-text)',
    '#172033' => 'var(--c-text)',
    '#111827' => 'var(--c-text)',

    // ---------- Abu tua / hari libur ----------
    '#475569' => 'var(--c-offday)',
    '#475467' => 'var(--c-offday)',
    '#f2f4f7' => 'var(--c-offday-soft)',

    // ---------- Brand ----------
    '#3563d6' => 'var(--c-primary)',
    '#2563eb' => 'var(--c-primary)',
    '#3b82f6' => 'var(--c-primary)',
    '#2b53b8' => 'var(--c-primary-hover)',
    '#1d4ed8' => 'var(--c-primary-hover)',
    '#1e40af' => 'var(--c-primary-hover)',
    '#eff6ff' => 'var(--c-primary-soft)',
    '#edf4ff' => 'var(--c-primary-soft)',
    '#dbeafe' => 'var(--c-primary-soft)',
    '#93c5fd' => 'var(--c-primary)',

    // ---------- Info ----------
    '#0ea5e9' => 'var(--c-info)',
    '#175cd3' => 'var(--c-info)',

    // ---------- Berhasil ----------
    '#dcfce7' => 'var(--c-ok-soft)',
    '#ecfdf3' => 'var(--c-ok-soft)',
    '#d1fae5' => 'var(--c-ok-soft)',
    '#86efac' => 'var(--c-ok-border)',
    '#abefc6' => 'var(--c-ok-border)',
    '#6ee7b7' => 'var(--c-ok-border)',
    '#15803d' => 'var(--c-ok)',
    '#16a34a' => 'var(--c-ok)',
    '#067647' => 'var(--c-ok)',
    '#059669' => 'var(--c-ok)',

    // ---------- Bahaya ----------
    '#fee2e2' => 'var(--c-late-soft)',
    '#fef3f2' => 'var(--c-late-soft)',
    '#fef2f2' => 'var(--c-late-soft)',
    '#fca5a5' => 'var(--c-late-border)',
    '#fecdca' => 'var(--c-late-border)',
    '#b91c1c' => 'var(--c-late)',
    '#b42318' => 'var(--c-late)',
    '#dc2626' => 'var(--c-late)',
    '#ef4444' => 'var(--c-late)',

    // ---------- Peringatan ----------
    '#fffaeb' => 'var(--c-pending-soft)',
    '#fef3c7' => 'var(--c-pending-soft)',
    '#fffbeb' => 'var(--c-pending-soft)',
    '#fedf89' => 'var(--c-pending-border)',
    '#fde68a' => 'var(--c-pending-border)',
    '#b54708' => 'var(--c-pending)',
    '#b45309' => 'var(--c-pending)',
    '#d97706' => 'var(--c-pending)',
    '#f59e0b' => 'var(--c-pending)',
    '#ea580c' => 'var(--c-pending)',
];

// ---------------------------------------------------------------------
// Argumen
// ---------------------------------------------------------------------
$apply      = in_array('--apply', $argv, true);
$onlyFile   = null;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--file=')) {
        $onlyFile = substr($arg, 7);
    }
}

if (!is_dir($cssDir)) {
    exit("Folder CSS tidak ditemukan: {$cssDir}\n");
}

$files = glob($cssDir . '/*.css') ?: [];
sort($files);

$totalReplaced = 0;
$totalFiles    = 0;
$unknownHex    = [];
$printWarnings = [];

echo $apply
    ? "MODE: MENULIS PERUBAHAN\n\n"
    : "MODE: PRATINJAU (tidak ada file yang diubah)\n\n";

foreach ($files as $path) {
    $name = basename($path);

    if (in_array($name, $skipFiles, true)) {
        continue;
    }

    if ($onlyFile !== null && $name !== $onlyFile) {
        continue;
    }

    $original = file_get_contents($path);
    if ($original === false) {
        echo "  ! gagal membaca {$name}\n";
        continue;
    }

    // File yang dipakai untuk cetak perlu diperiksa manual.
    if (str_contains($original, '@media print')) {
        $printWarnings[] = $name;
    }

    $countInFile = 0;

    /**
     * Ganti hanya token warna yang berdiri sendiri.
     * Pola \B#hex\b menghindari tersentuhnya hex di dalam kata lain.
     */
    $updated = preg_replace_callback(
        '/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})\b/',
        function (array $m) use ($colorMap, &$countInFile, &$unknownHex, $name) {
            $hex = strtolower($m[0]);

            if (isset($colorMap[$hex])) {
                $countInFile++;
                return $colorMap[$hex];
            }

            $unknownHex[$hex][$name] = ($unknownHex[$hex][$name] ?? 0) + 1;

            return $m[0];
        },
        $original
    );

    if ($countInFile === 0) {
        continue;
    }

    $totalFiles++;
    $totalReplaced += $countInFile;

    printf("  %-34s %3d warna\n", $name, $countInFile);

    if ($apply) {
        file_put_contents($path, $updated);
    }
}

// ---------------------------------------------------------------------
// Ringkasan
// ---------------------------------------------------------------------
echo "\n";
echo str_repeat('-', 56) . "\n";
printf("Total: %d warna di %d file\n", $totalReplaced, $totalFiles);

if ($printWarnings !== []) {
    echo "\nPERIKSA MANUAL (mengandung @media print):\n";
    foreach ($printWarnings as $name) {
        echo "  - {$name}\n";
    }
    echo "  Sebagian mesin cetak tidak mendukung var(), cek hasil cetaknya.\n";
}

if ($unknownHex !== []) {
    echo "\nBELUM ADA DI PETA WARNA (" . count($unknownHex) . " nilai):\n";

    uasort($unknownHex, static fn(array $a, array $b): int => array_sum($b) <=> array_sum($a));

    foreach ($unknownHex as $hex => $files) {
        printf("  %-9s %2dx  (%s)\n", $hex, array_sum($files), implode(', ', array_keys($files)));
    }

    echo "\n  Tambahkan ke \$colorMap di skrip ini, lalu jalankan ulang.\n";
}

if (!$apply) {
    echo "\nJalankan ulang dengan --apply untuk menulis perubahan.\n";
}

echo "\n";
