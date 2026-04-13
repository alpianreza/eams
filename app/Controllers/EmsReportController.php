<?php

namespace App\Controllers;

use App\Models\EmsElectricConsumptionEntryModel;
use App\Models\EmsElectricConsumptionYearModel;
use App\Models\EmsWaterConsumptionEntryModel;
use App\Models\EmsWaterConsumptionYearModel;

class EmsReportController extends BaseController
{
    private const ELECTRIC_EMISSION_FACTOR = 0.87;

    private EmsElectricConsumptionEntryModel $electricEntryModel;
    private EmsElectricConsumptionYearModel $electricYearModel;
    private EmsWaterConsumptionEntryModel $entryModel;
    private EmsWaterConsumptionYearModel $yearModel;

    public function __construct()
    {
        $this->electricEntryModel = new EmsElectricConsumptionEntryModel();
        $this->electricYearModel = new EmsElectricConsumptionYearModel();
        $this->entryModel = new EmsWaterConsumptionEntryModel();
        $this->yearModel = new EmsWaterConsumptionYearModel();
    }

    public function index()
    {
        page('EMS Report');

        return view('ems/index', [
            'reports' => [
                [
                    'title' => 'Water Consumption',
                    'subtitle' => 'Monthly water consumption and intensity summary.',
                    'href' => '/ems-reports/water-consumption',
                    'status' => 'active',
                    'icon' => 'bi-droplet',
                ],
                [
                    'title' => 'Electric Consumption',
                    'subtitle' => 'Monthly electrical consumption, intensity, and emission summary.',
                    'href' => '/ems-reports/electric-consumption',
                    'status' => 'active',
                    'icon' => 'bi-lightning-charge',
                ],
                [
                    'title' => 'Oil Consumption',
                    'subtitle' => 'Coming soon.',
                    'href' => '#',
                    'status' => 'soon',
                    'icon' => 'bi-fuel-pump',
                ],
            ],
        ]);
    }

    public function waterConsumption()
    {
        page('EMS Report - Water Consumption');

        $requestedYear = (int) ($this->request->getGet('year') ?: 0);
        $dataset = $this->buildWaterDataset($requestedYear > 0 ? $requestedYear : null);

        return view('ems/water_consumption', [
            'boot' => $dataset,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function electricConsumption()
    {
        page('EMS Report - Electric Consumption');

        $requestedYear = (int) ($this->request->getGet('year') ?: 0);
        $dataset = $this->buildElectricDataset($requestedYear > 0 ? $requestedYear : null);

        return view('ems/electric_consumption', [
            'boot' => $dataset,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function saveWaterConsumption()
    {
        if ($this->request->isAJAX()) {
            return $this->saveWaterConsumptionAjax();
        }

        if ($this->isReadOnlyUser()) {
            return redirect()->back()->with('error', 'Anda hanya punya akses baca.');
        }

        $year = (int) $this->request->getPost('report_year');
        if ($year <= 0) {
            return redirect()->back()->with('error', 'Tahun report tidak valid.');
        }

        $productionOutput = $this->parseDecimal($this->request->getPost('production_output'));
        $months = $this->sanitizeMonths($this->request->getPost('months'));
        $this->persistWaterConsumption($year, $productionOutput, $months);

        return redirect()->to(base_url('ems-reports/water-consumption?year=' . $year))
            ->with('success', 'Data water consumption berhasil disimpan.');
    }

    public function saveWaterConsumptionAjax()
    {
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

        $productionOutput = $this->parseDecimal($this->request->getPost('production_output'));
        $months = $this->sanitizeMonths($this->request->getPost('months'));
        $this->persistWaterConsumption($year, $productionOutput, $months);

        $dataset = $this->buildWaterDataset($year);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Data water consumption tersimpan otomatis.',
            'dataset' => $dataset,
            'summaryHtml' => view('ems/_water_summary_panels', [
                'years' => $dataset['years'] ?? [],
                'baselineYear' => $dataset['baselineYear'] ?? $year,
                'yearMeta' => $dataset['yearMeta'] ?? [],
                'monthlySummary' => $dataset['monthlySummary'] ?? [],
                'summaryRows' => $dataset['summaryRows'] ?? [],
            ]),
            'csrfHash' => csrf_hash(),
        ]);
    }

    public function saveElectricConsumption()
    {
        if ($this->request->isAJAX()) {
            return $this->saveElectricConsumptionAjax();
        }

        if ($this->isReadOnlyUser()) {
            return redirect()->back()->with('error', 'Anda hanya punya akses baca.');
        }

        $year = (int) $this->request->getPost('report_year');
        if ($year <= 0) {
            return redirect()->back()->with('error', 'Tahun report tidak valid.');
        }

        $productionOutput = $this->parseDecimal($this->request->getPost('production_output'));
        $months = $this->sanitizeMonths($this->request->getPost('months'));
        $this->persistElectricConsumption($year, $productionOutput, $months);

        return redirect()->to(base_url('ems-reports/electric-consumption?year=' . $year))
            ->with('success', 'Data electric consumption berhasil disimpan.');
    }

    private function saveElectricConsumptionAjax()
    {
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

        $productionOutput = $this->parseDecimal($this->request->getPost('production_output'));
        $months = $this->sanitizeMonths($this->request->getPost('months'));
        $this->persistElectricConsumption($year, $productionOutput, $months);
        $dataset = $this->buildElectricDataset($year);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Data electric consumption tersimpan otomatis.',
            'dataset' => $dataset,
            'summaryHtml' => view('ems/_electric_summary_panels', [
                'years' => $dataset['years'] ?? [],
                'selectedYear' => $dataset['selectedYear'] ?? $year,
                'yearMeta' => $dataset['yearMeta'] ?? [],
                'monthlySummary' => $dataset['monthlySummary'] ?? [],
                'months' => $dataset['months'] ?? [],
                'emissionFactor' => $dataset['emissionFactor'] ?? self::ELECTRIC_EMISSION_FACTOR,
            ]),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function buildWaterDataset(?int $requestedYear = null): array
    {
        $years = $this->yearModel->orderBy('report_year', 'ASC')->findAll();
        $currentYear = (int) date('Y');

        if (empty($years)) {
            $years = [['report_year' => $currentYear, 'production_output' => null, 'notes' => null]];
        }

        $yearNumbers = array_map(static fn($row) => (int) $row['report_year'], $years);
        if (!in_array($currentYear, $yearNumbers, true)) {
            $years[] = ['report_year' => $currentYear, 'production_output' => null, 'notes' => null];
            $yearNumbers[] = $currentYear;
            sort($yearNumbers);
            usort($years, static fn($a, $b) => ((int) $a['report_year']) <=> ((int) $b['report_year']));
        }

        $selectedYear = $requestedYear && in_array($requestedYear, $yearNumbers, true)
            ? $requestedYear
            : (in_array($currentYear, $yearNumbers, true) ? $currentYear : $yearNumbers[0]);

        $entries = $this->entryModel->orderBy('report_year', 'ASC')->orderBy('report_month', 'ASC')->findAll();
        $matrix = [];
        foreach ($entries as $entry) {
            $matrix[(int) $entry['report_year']][(int) $entry['report_month']] = (float) $entry['consumption_m3'];
        }

        $months = [
            1 => ['short' => 'Jan', 'long' => 'Januari'],
            2 => ['short' => 'Feb', 'long' => 'Februari'],
            3 => ['short' => 'Mar', 'long' => 'Maret'],
            4 => ['short' => 'Apr', 'long' => 'April'],
            5 => ['short' => 'May', 'long' => 'Mei'],
            6 => ['short' => 'Jun', 'long' => 'Juni'],
            7 => ['short' => 'Jul', 'long' => 'Juli'],
            8 => ['short' => 'Aug', 'long' => 'Agustus'],
            9 => ['short' => 'Sep', 'long' => 'September'],
            10 => ['short' => 'Oct', 'long' => 'Oktober'],
            11 => ['short' => 'Nov', 'long' => 'November'],
            12 => ['short' => 'Dec', 'long' => 'Desember'],
        ];

        $editorYears = [];
        $yearMeta = [];
        foreach ($years as $row) {
            $year = (int) $row['report_year'];
            $productionOutput = $row['production_output'] !== null ? (float) $row['production_output'] : null;
            $monthlyTotal = 0.0;
            $editorMonths = [];

            foreach ($months as $monthNum => $labels) {
                $value = isset($matrix[$year][$monthNum]) ? (float) $matrix[$year][$monthNum] : null;
                $editorMonths[$monthNum] = $value;
                $monthlyTotal += (float) ($value ?? 0);
            }

            $intensity = ($productionOutput !== null && $productionOutput > 0)
                ? ($monthlyTotal / $productionOutput)
                : null;

            $editorYears[$year] = [
                'year' => $year,
                'productionOutput' => $productionOutput,
                'months' => $editorMonths,
            ];

            $yearMeta[$year] = [
                'total' => $monthlyTotal,
                'production_output' => $productionOutput,
                'intensity' => $intensity,
                'notes' => $row['notes'] ?? null,
            ];
        }

        $baselineYear = $yearNumbers[0];
        $baselineIntensity = $yearMeta[$baselineYear]['intensity'] ?? null;

        $summaryRows = [];
        foreach ($yearNumbers as $year) {
            $intensity = $yearMeta[$year]['intensity'];
            $changeVsBaseline = null;
            $status = '-';

            if ($year === $baselineYear) {
                $status = 'Baseline';
            } elseif ($baselineIntensity !== null && $baselineIntensity > 0 && $intensity !== null) {
                $changeVsBaseline = (($intensity - $baselineIntensity) / $baselineIntensity) * 100;
                if (abs($changeVsBaseline) < 0.0001) {
                    $status = 'Stable';
                } elseif ($changeVsBaseline < 0) {
                    $status = 'Decrease';
                } else {
                    $status = 'Increase';
                }
            }

            $summaryRows[] = [
                'year' => $year,
                'waterUsage' => $yearMeta[$year]['total'],
                'productionOutput' => $yearMeta[$year]['production_output'],
                'intensity' => $intensity,
                'changeVsBaseline' => $changeVsBaseline,
                'status' => $status,
            ];
        }

        $monthlySummary = [];
        foreach ($months as $monthNum => $labels) {
            $values = [];
            foreach ($yearNumbers as $index => $year) {
                $value = $matrix[$year][$monthNum] ?? null;
                $prevYear = $yearNumbers[$index - 1] ?? null;
                $prevValue = $prevYear ? ($matrix[$prevYear][$monthNum] ?? null) : null;
                $change = null;
                if ($prevYear !== null && $prevValue !== null && (float) $prevValue > 0 && $value !== null) {
                    $change = (((float) $value - (float) $prevValue) / (float) $prevValue) * 100;
                }

                $values[] = [
                    'year' => $year,
                    'value' => $value,
                    'change' => $change,
                ];
            }

            $monthlySummary[] = [
                'month' => $monthNum,
                'label' => $labels['short'],
                'values' => $values,
            ];
        }

        return [
            'years' => $yearNumbers,
            'selectedYear' => $selectedYear,
            'baselineYear' => $baselineYear,
            'months' => $months,
            'editorYears' => $editorYears,
            'yearMeta' => $yearMeta,
            'monthlySummary' => $monthlySummary,
            'summaryRows' => $summaryRows,
        ];
    }

    private function persistWaterConsumption(int $year, ?float $productionOutput, array $months): void
    {
        $existingYear = $this->yearModel->where('report_year', $year)->first();
        if ($existingYear) {
            $this->yearModel->update($existingYear['id'], [
                'production_output' => $productionOutput,
            ]);
        } else {
            $this->yearModel->insert([
                'report_year' => $year,
                'production_output' => $productionOutput,
            ]);
        }

        for ($month = 1; $month <= 12; $month++) {
            $value = array_key_exists($month, $months) ? (float) $months[$month] : 0.0;
            $existingEntry = $this->entryModel
                ->where('report_year', $year)
                ->where('report_month', $month)
                ->first();

            if ($existingEntry) {
                $this->entryModel->update($existingEntry['id'], [
                    'consumption_m3' => $value,
                ]);
            } else {
                $this->entryModel->insert([
                    'report_year' => $year,
                    'report_month' => $month,
                    'consumption_m3' => $value,
                ]);
            }
        }
    }

    private function buildElectricDataset(?int $requestedYear = null): array
    {
        $years = $this->electricYearModel->orderBy('report_year', 'ASC')->findAll();
        $currentYear = (int) date('Y');
        $startingYear = 2026;

        if (empty($years)) {
            for ($year = $startingYear; $year <= $startingYear + 4; $year++) {
                $years[] = ['report_year' => $year, 'production_output' => null, 'notes' => null];
            }
        }

        $yearNumbers = array_map(static fn($row) => (int) $row['report_year'], $years);
        $targetYear = max($startingYear, $currentYear);
        if (!in_array($targetYear, $yearNumbers, true)) {
            $years[] = ['report_year' => $targetYear, 'production_output' => null, 'notes' => null];
            $yearNumbers[] = $targetYear;
            sort($yearNumbers);
            usort($years, static fn($a, $b) => ((int) $a['report_year']) <=> ((int) $b['report_year']));
        }

        $selectedYear = $requestedYear && in_array($requestedYear, $yearNumbers, true)
            ? $requestedYear
            : (in_array($targetYear, $yearNumbers, true) ? $targetYear : $yearNumbers[0]);

        $entries = $this->electricEntryModel->orderBy('report_year', 'ASC')->orderBy('report_month', 'ASC')->findAll();
        $matrix = [];
        foreach ($entries as $entry) {
            $matrix[(int) $entry['report_year']][(int) $entry['report_month']] = (float) $entry['consumption_kwh'];
        }

        $months = [
            1 => ['short' => 'Jan', 'long' => 'Januari'],
            2 => ['short' => 'Feb', 'long' => 'Februari'],
            3 => ['short' => 'Mar', 'long' => 'Maret'],
            4 => ['short' => 'Apr', 'long' => 'April'],
            5 => ['short' => 'May', 'long' => 'Mei'],
            6 => ['short' => 'Jun', 'long' => 'Juni'],
            7 => ['short' => 'Jul', 'long' => 'Juli'],
            8 => ['short' => 'Aug', 'long' => 'Agustus'],
            9 => ['short' => 'Sep', 'long' => 'September'],
            10 => ['short' => 'Oct', 'long' => 'Oktober'],
            11 => ['short' => 'Nov', 'long' => 'November'],
            12 => ['short' => 'Dec', 'long' => 'Desember'],
        ];

        $editorYears = [];
        $yearMeta = [];
        foreach ($years as $row) {
            $year = (int) $row['report_year'];
            $productionOutput = $row['production_output'] !== null ? (float) $row['production_output'] : null;
            $monthlyTotal = 0.0;
            $editorMonths = [];
            $trendValues = [];

            foreach ($months as $monthNum => $labels) {
                $value = isset($matrix[$year][$monthNum]) ? (float) $matrix[$year][$monthNum] : null;
                $editorMonths[$monthNum] = $value;
                $monthlyTotal += (float) ($value ?? 0);
                $trendValues[$monthNum] = (float) ($value ?? 0);
            }

            $intensity = ($productionOutput !== null && $productionOutput > 0)
                ? ($monthlyTotal / $productionOutput)
                : null;
            $emission = $monthlyTotal > 0 ? ($monthlyTotal * self::ELECTRIC_EMISSION_FACTOR) / 1000 : 0.0;

            $editorYears[$year] = [
                'year' => $year,
                'productionOutput' => $productionOutput,
                'months' => $editorMonths,
            ];

            $yearMeta[$year] = [
                'total' => $monthlyTotal,
                'production_output' => $productionOutput,
                'intensity' => $intensity,
                'emission' => $emission,
                'notes' => $row['notes'] ?? null,
                'trend' => $trendValues,
            ];
        }

        $monthlySummary = [];
        foreach ($months as $monthNum => $labels) {
            $values = [];
            foreach ($yearNumbers as $index => $year) {
                $value = $matrix[$year][$monthNum] ?? null;
                $prevYear = $yearNumbers[$index - 1] ?? null;
                $prevValue = $prevYear ? ($matrix[$prevYear][$monthNum] ?? null) : null;
                $change = null;
                if ($prevYear !== null && $prevValue !== null && (float) $prevValue > 0 && $value !== null) {
                    $change = (((float) $value - (float) $prevValue) / (float) $prevValue) * 100;
                }

                $values[] = [
                    'year' => $year,
                    'value' => $value,
                    'change' => $change,
                ];
            }

            $monthlySummary[] = [
                'month' => $monthNum,
                'label' => $labels['short'],
                'values' => $values,
            ];
        }

        return [
            'years' => $yearNumbers,
            'selectedYear' => $selectedYear,
            'months' => $months,
            'editorYears' => $editorYears,
            'yearMeta' => $yearMeta,
            'monthlySummary' => $monthlySummary,
            'emissionFactor' => self::ELECTRIC_EMISSION_FACTOR,
        ];
    }

    private function persistElectricConsumption(int $year, ?float $productionOutput, array $months): void
    {
        $existingYear = $this->electricYearModel->where('report_year', $year)->first();
        if ($existingYear) {
            $this->electricYearModel->update($existingYear['id'], [
                'production_output' => $productionOutput,
            ]);
        } else {
            $this->electricYearModel->insert([
                'report_year' => $year,
                'production_output' => $productionOutput,
            ]);
        }

        for ($month = 1; $month <= 12; $month++) {
            $value = array_key_exists($month, $months) ? (float) $months[$month] : 0.0;
            $existingEntry = $this->electricEntryModel
                ->where('report_year', $year)
                ->where('report_month', $month)
                ->first();

            if ($existingEntry) {
                $this->electricEntryModel->update($existingEntry['id'], [
                    'consumption_kwh' => $value,
                ]);
            } else {
                $this->electricEntryModel->insert([
                    'report_year' => $year,
                    'report_month' => $month,
                    'consumption_kwh' => $value,
                ]);
            }
        }
    }

    private function sanitizeMonths($months): array
    {
        if (!is_array($months)) {
            return [];
        }

        $clean = [];
        for ($month = 1; $month <= 12; $month++) {
            $clean[$month] = $this->parseDecimal($months[$month] ?? null) ?? 0.0;
        }

        return $clean;
    }

    private function parseDecimal($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(',', '', $normalized);
        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function isReadOnlyUser(): bool
    {
        return session()->get('permission') === 'read' && session()->get('role') !== 'admin';
    }
}
