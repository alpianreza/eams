<?php

namespace App\Controllers;

use App\Models\EmsWaterConsumptionEntryModel;
use App\Models\EmsWaterConsumptionYearModel;

class EmsReportController extends BaseController
{
    private EmsWaterConsumptionEntryModel $entryModel;
    private EmsWaterConsumptionYearModel $yearModel;

    public function __construct()
    {
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
                    'href' => base_url('ems-reports/water-consumption'),
                    'status' => 'active',
                ],
                [
                    'title' => 'Electric Consumption',
                    'subtitle' => 'Coming soon.',
                    'href' => '#',
                    'status' => 'soon',
                ],
                [
                    'title' => 'Oil Consumption',
                    'subtitle' => 'Coming soon.',
                    'href' => '#',
                    'status' => 'soon',
                ],
            ],
        ]);
    }

    public function waterConsumption()
    {
        page('EMS Report - Water Consumption');

        $years = $this->yearModel->orderBy('report_year', 'ASC')->findAll();
        if (empty($years)) {
            $years = [['report_year' => (int) date('Y'), 'production_output' => null]];
        }

        $selectedYear = (int) ($this->request->getGet('year') ?: ($years[0]['report_year'] ?? date('Y')));
        $yearNumbers = array_map(static fn($row) => (int) $row['report_year'], $years);
        if (!in_array($selectedYear, $yearNumbers, true)) {
            $selectedYear = $yearNumbers[0];
        }

        $entries = $this->entryModel->orderBy('report_year', 'ASC')->orderBy('report_month', 'ASC')->findAll();
        $matrix = [];
        foreach ($entries as $entry) {
            $matrix[(int) $entry['report_year']][(int) $entry['report_month']] = (float) $entry['consumption_m3'];
        }

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        $selectedMonths = [];
        foreach ($months as $monthNum => $monthLabel) {
            $selectedMonths[$monthNum] = $matrix[$selectedYear][$monthNum] ?? null;
        }

        $yearMeta = [];
        foreach ($years as $row) {
            $year = (int) $row['report_year'];
            $monthlyTotal = 0.0;
            foreach ($months as $monthNum => $_label) {
                $monthlyTotal += (float) ($matrix[$year][$monthNum] ?? 0);
            }

            $productionOutput = $row['production_output'] !== null ? (float) $row['production_output'] : null;
            $intensity = ($productionOutput && $productionOutput > 0)
                ? ($monthlyTotal / $productionOutput)
                : null;

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

            $summaryRows[$year] = [
                'year' => $year,
                'water_usage' => $yearMeta[$year]['total'],
                'production_output' => $yearMeta[$year]['production_output'],
                'intensity' => $intensity,
                'change_vs_baseline' => $changeVsBaseline,
                'status' => $status,
            ];
        }

        $monthlySummary = [];
        foreach ($months as $monthNum => $monthLabel) {
            $row = ['label' => $monthLabel, 'values' => []];
            foreach ($yearNumbers as $index => $year) {
                $value = $matrix[$year][$monthNum] ?? null;
                $prevYear = $yearNumbers[$index - 1] ?? null;
                $prevValue = $prevYear ? ($matrix[$prevYear][$monthNum] ?? null) : null;
                $change = null;
                if ($prevYear !== null && $prevValue !== null && (float) $prevValue > 0 && $value !== null) {
                    $change = ((float) $value - (float) $prevValue) / (float) $prevValue * 100;
                }
                $row['values'][$year] = [
                    'value' => $value,
                    'change' => $change,
                ];
            }
            $monthlySummary[$monthNum] = $row;
        }

        return view('ems/water_consumption', [
            'years' => $yearNumbers,
            'selectedYear' => $selectedYear,
            'months' => $months,
            'selectedMonths' => $selectedMonths,
            'selectedProductionOutput' => $yearMeta[$selectedYear]['production_output'] ?? null,
            'monthlySummary' => $monthlySummary,
            'yearMeta' => $yearMeta,
            'summaryRows' => $summaryRows,
            'baselineYear' => $baselineYear,
        ]);
    }

    public function saveWaterConsumption()
    {
        if (session()->get('permission') === 'read' && session()->get('role') !== 'admin') {
            return redirect()->back()->with('error', 'Anda hanya punya akses baca.');
        }

        $year = (int) $this->request->getPost('report_year');
        if ($year <= 0) {
            return redirect()->back()->with('error', 'Tahun report tidak valid.');
        }

        $productionOutputRaw = str_replace(',', '', (string) $this->request->getPost('production_output'));
        $productionOutput = $productionOutputRaw !== '' ? (float) $productionOutputRaw : null;

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

        $months = $this->request->getPost('months');
        if (!is_array($months)) {
            $months = [];
        }

        for ($month = 1; $month <= 12; $month++) {
            $raw = str_replace(',', '', (string) ($months[$month] ?? '0'));
            $value = $raw !== '' ? (float) $raw : 0.0;
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

        return redirect()->to(base_url('ems-reports/water-consumption?year=' . $year))
            ->with('success', 'Data water consumption berhasil disimpan.');
    }
}