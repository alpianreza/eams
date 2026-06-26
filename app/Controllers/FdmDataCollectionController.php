<?php

namespace App\Controllers;

use App\Models\FdmProductionSectionEntryModel;
use App\Models\FdmProductionSectionYearModel;

class FdmDataCollectionController extends BaseController
{
    private const START_YEAR = 2026;
    private const RANGE_YEARS = 5;
    private const MONTH_KEYS = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    private const MONTH_LABELS = [
        'jan' => 'Jan',
        'feb' => 'Feb',
        'mar' => 'Mar',
        'apr' => 'Apr',
        'may' => 'May',
        'jun' => 'Jun',
        'jul' => 'Jul',
        'aug' => 'Aug',
        'sep' => 'Sep',
        'oct' => 'Oct',
        'nov' => 'Nov',
        'dec' => 'Dec',
    ];
    private const DEFAULT_RETAILS = [
        ['key' => 'gap_inc', 'label' => 'GAP Inc.'],
        ['key' => 'target', 'label' => 'Target'],
        ['key' => 'walmart', 'label' => 'Walmart'],
        ['key' => 'macys', 'label' => "Macy's"],
    ];
    private const WORKFORCE_KEY = 'full_time_employee';

    private FdmProductionSectionEntryModel $entryModel;
    private FdmProductionSectionYearModel $yearModel;

    public function __construct()
    {
        $this->entryModel = new FdmProductionSectionEntryModel();
        $this->yearModel = new FdmProductionSectionYearModel();
    }

    public function index()
    {
        page('FDM Data Collection');

        $collections = [
            [
                'title' => 'Production Section',
                'subtitle' => 'Input bulanan retail, total assembler otomatis, dan jumlah tenaga kerja.',
                'status' => 'ready',
                'icon' => 'bi-table',
                'href' => '/fdm-data-collection/production-section',
            ],
            [
                'title' => 'Source Document',
                'subtitle' => 'Slot untuk dokumen sumber pendukung sebelum dipetakan ke form FDM.',
                'status' => 'soon',
                'icon' => 'bi-folder2-open',
                'href' => null,
            ],
            [
                'title' => 'Validation Queue',
                'subtitle' => 'Daftar pemeriksaan data yang siap divalidasi setelah struktur FDM final.',
                'status' => 'soon',
                'icon' => 'bi-patch-check',
                'href' => null,
            ],
        ];

        return view('fdm/index', [
            'title' => 'FDM Data Collection',
            'collections' => $collections,
        ]);
    }

    public function productionSection()
    {
        page('FDM Data Collection - Production Section');

        $requestedYear = (int) ($this->request->getGet('year') ?: 0);
        $dataset = $this->buildProductionSectionDataset($requestedYear > 0 ? $requestedYear : null);

        return view('fdm/production_section', [
            'boot' => $dataset,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function saveProductionSection()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to('/fdm-data-collection/production-section');
        }

        if ($this->isReadOnlyUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Anda hanya punya akses baca.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $year = (int) $this->request->getPost('report_year');
        if ($year <= 0) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Tahun report tidak valid.',
                'csrfHash' => csrf_hash(),
            ]);
        }

        $retailers = $this->sanitizeRetailers(json_decode((string) $this->request->getPost('retailers_json'), true));
        $workforce = $this->normalizeMonthlyValues(json_decode((string) $this->request->getPost('workforce_json'), true));

        $this->persistProductionSection($year, $retailers, $workforce);
        $dataset = $this->buildProductionSectionDataset($year);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Data production section tersimpan.',
            'payload' => $dataset,
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function buildProductionSectionDataset(?int $selectedYear = null): array
    {
        $availableYears = $this->ensureYears();
        $selectedYear = $selectedYear ?: $availableYears[0];
        if (! in_array($selectedYear, $availableYears, true)) {
            $availableYears[] = $selectedYear;
            sort($availableYears);
        }

        $yearRows = $this->yearModel->whereIn('report_year', $availableYears)->findAll();
        $yearIdByValue = [];
        foreach ($yearRows as $yearRow) {
            $yearIdByValue[(int) $yearRow['report_year']] = (int) $yearRow['id'];
        }

        $entries = [];
        if ($yearIdByValue !== []) {
            $entries = $this->entryModel
                ->whereIn('year_id', array_values($yearIdByValue))
                ->orderBy('display_order', 'ASC')
                ->findAll();
        }

        $entriesByYear = [];
        foreach ($entries as $entry) {
            $year = array_search((int) $entry['year_id'], $yearIdByValue, true);
            if ($year === false) {
                continue;
            }

            $entriesByYear[(int) $year][] = $entry;
        }

        $selectedEntries = $entriesByYear[$selectedYear] ?? [];
        $retailers = [];
        $workforce = null;
        foreach ($selectedEntries as $entry) {
            if (($entry['entry_type'] ?? 'retail') === 'workforce') {
                $workforce = [
                    'id' => (int) $entry['id'],
                    'key' => $entry['section_key'],
                    'label' => $entry['section_label'],
                    'frequency' => $entry['frequency_label'] ?: 'Monthly',
                    'values' => $this->normalizeMonthlyValues($entry['monthly_values'] ?? null),
                ];
                continue;
            }

            $retailers[] = [
                'id' => (int) $entry['id'],
                'key' => $entry['section_key'],
                'label' => $entry['section_label'],
                'frequency' => $entry['frequency_label'] ?: 'Monthly',
                'logoPath' => $entry['logo_path'] ?? null,
                'values' => $this->normalizeMonthlyValues($entry['monthly_values'] ?? null),
            ];
        }

        if ($retailers === []) {
            foreach (self::DEFAULT_RETAILS as $index => $retail) {
                $retailers[] = [
                    'id' => null,
                    'key' => $retail['key'],
                    'label' => $retail['label'],
                    'frequency' => 'Monthly',
                    'logoPath' => null,
                    'values' => $this->blankMonthValues(),
                    'displayOrder' => $index + 1,
                ];
            }
        }

        if ($workforce === null) {
            $workforce = [
                'id' => null,
                'key' => self::WORKFORCE_KEY,
                'label' => 'b) Number of Full Time employee',
                'frequency' => 'Monthly',
                'values' => $this->blankMonthValues(),
            ];
        }

        return [
            'selectedYear' => $selectedYear,
            'availableYears' => $availableYears,
            'saveUrl' => '/fdm-data-collection/production-section/save',
            'monthKeys' => self::MONTH_KEYS,
            'monthLabels' => self::MONTH_LABELS,
            'aggregateRow' => [
                'label' => 'a) Finished Product Assembler',
                'frequency' => 'Monthly',
                'values' => $this->sumRetailers($retailers),
            ],
            'retailers' => array_values($retailers),
            'workforce' => $workforce,
        ];
    }

    private function persistProductionSection(int $year, array $retailers, array $workforce): void
    {
        $yearRow = $this->yearModel->where('report_year', $year)->first();
        $yearId = $yearRow ? (int) $yearRow['id'] : (int) $this->yearModel->insert(['report_year' => $year], true);

        $existingEntries = $this->entryModel->where('year_id', $yearId)->findAll();
        $existingRetailById = [];
        $existingKeys = [];
        $existingWorkforce = null;
        foreach ($existingEntries as $entry) {
            $existingKeys[] = $entry['section_key'];
            if (($entry['entry_type'] ?? 'retail') === 'workforce') {
                $existingWorkforce = $entry;
            } else {
                $existingRetailById[(int) $entry['id']] = $entry;
            }
        }

        $keptRetailIds = [];
        $order = 1;
        foreach ($retailers as $retailer) {
            $label = trim((string) ($retailer['label'] ?? ''));
            if ($label === '') {
                $label = 'Retail Baru';
            }

            $record = [
                'year_id' => $yearId,
                'section_key' => $this->resolveSectionKey($retailer, $existingKeys, $label),
                'section_label' => $label,
                'entry_type' => 'retail',
                'frequency_label' => 'Monthly',
                'logo_path' => null,
                'display_order' => $order++,
                'monthly_values' => json_encode($this->normalizeMonthlyValues($retailer['values'] ?? []), JSON_UNESCAPED_UNICODE),
            ];

            $existingId = isset($retailer['id']) && is_numeric($retailer['id']) ? (int) $retailer['id'] : null;
            if ($existingId && isset($existingRetailById[$existingId])) {
                $this->entryModel->update($existingId, $record);
                $keptRetailIds[] = $existingId;
                continue;
            }

            $newId = (int) $this->entryModel->insert($record, true);
            $keptRetailIds[] = $newId;
            $existingKeys[] = $record['section_key'];
        }

        foreach ($existingRetailById as $existingId => $entry) {
            if (! in_array($existingId, $keptRetailIds, true)) {
                $this->entryModel->delete($existingId);
            }
        }

        $workforceRecord = [
            'year_id' => $yearId,
            'section_key' => self::WORKFORCE_KEY,
            'section_label' => 'b) Number of Full Time employee',
            'entry_type' => 'workforce',
            'frequency_label' => 'Monthly',
            'logo_path' => null,
            'display_order' => 999,
            'monthly_values' => json_encode($this->normalizeMonthlyValues($workforce), JSON_UNESCAPED_UNICODE),
        ];

        if ($existingWorkforce) {
            $this->entryModel->update($existingWorkforce['id'], $workforceRecord);
        } else {
            $this->entryModel->insert($workforceRecord);
        }
    }

    private function ensureYears(): array
    {
        $currentYear = max((int) date('Y'), self::START_YEAR);
        $years = [];
        for ($offset = 0; $offset < self::RANGE_YEARS; $offset++) {
            $years[] = $currentYear + $offset;
        }

        foreach ($years as $year) {
            $existing = $this->yearModel->where('report_year', $year)->first();
            if (! $existing) {
                $this->yearModel->insert(['report_year' => $year]);
            }
        }

        return $years;
    }

    private function sanitizeRetailers($retailers): array
    {
        $retailers = is_array($retailers) ? $retailers : [];
        $sanitized = [];

        foreach ($retailers as $retailer) {
            if (! is_array($retailer)) {
                continue;
            }

            $sanitized[] = [
                'id' => isset($retailer['id']) && is_numeric($retailer['id']) ? (int) $retailer['id'] : null,
                'key' => trim((string) ($retailer['key'] ?? '')),
                'label' => trim((string) ($retailer['label'] ?? '')),
                'values' => $this->normalizeMonthlyValues($retailer['values'] ?? []),
            ];
        }

        return array_values($sanitized);
    }

    private function resolveSectionKey(array $retailer, array $existingKeys, string $label): string
    {
        $currentKey = trim((string) ($retailer['key'] ?? ''));
        if ($currentKey !== '') {
            return $currentKey;
        }

        $base = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $label));
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'retail';
        }

        $candidate = $base;
        $suffix = 2;
        while (in_array($candidate, $existingKeys, true)) {
            $candidate = $base . '_' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function sumRetailers(array $retailers): array
    {
        $sum = $this->blankMonthValues();
        foreach ($retailers as $retailer) {
            $values = $this->normalizeMonthlyValues($retailer['values'] ?? []);
            foreach (self::MONTH_KEYS as $monthKey) {
                $sum[$monthKey] += (float) ($values[$monthKey] ?? 0);
            }
        }

        return $sum;
    }

    private function normalizeMonthlyValues($values): array
    {
        if (is_string($values) && $values !== '') {
            $decoded = json_decode($values, true);
            if (is_array($decoded)) {
                $values = $decoded;
            }
        }

        $values = is_array($values) ? $values : [];
        $normalized = [];
        foreach (self::MONTH_KEYS as $monthKey) {
            $normalized[$monthKey] = $this->parseDecimal($values[$monthKey] ?? 0);
        }

        return $normalized;
    }

    private function blankMonthValues(): array
    {
        $blank = [];
        foreach (self::MONTH_KEYS as $monthKey) {
            $blank[$monthKey] = 0.0;
        }

        return $blank;
    }

    private function parseDecimal($value): float
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } else {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function isReadOnlyUser(): bool
    {
        return isReadOnlyAccess();
    }
}
