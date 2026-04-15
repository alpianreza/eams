<?php

namespace App\Controllers;

use App\Models\EmsElectricConsumptionEntryModel;
use App\Models\EmsElectricConsumptionYearModel;
use App\Models\EmsMobileCombustionEntryModel;
use App\Models\EmsMobileCombustionYearModel;
use App\Models\EmsStationaryCombustionEntryModel;
use App\Models\EmsStationaryCombustionYearModel;
use App\Models\EmsWaterConsumptionEntryModel;
use App\Models\EmsWaterConsumptionYearModel;

class EmsReportController extends BaseController
{
    private const EMS_START_YEAR = 2026;
    private const EMS_RANGE_YEARS = 5;
    private const ELECTRIC_EMISSION_FACTOR = 0.87;
    private const STATIONARY_SECTIONS = [
        'solar' => [
            'label' => 'Solar',
            'unit' => 'Liter',
            'consumption_label' => 'Solar Consumption',
            'emission_factor' => 2.69,
        ],
        'lpg' => [
            'label' => 'LPG',
            'unit' => 'Kg',
            'consumption_label' => 'LPG Consumption',
            'emission_factor' => 2.984,
        ],
        'scrap' => [
            'label' => 'Scrap',
            'unit' => 'Kg',
            'consumption_label' => 'Scrap Consumption',
            'emission_factor' => 1.8,
        ],
    ];
    private const MOBILE_SECTIONS = [
        'petrol' => [
            'label' => 'Petrol',
            'unit' => 'Liter',
            'consumption_label' => 'Petrol Consumption',
            'emission_factor' => 2.28,
        ],
    ];

    private EmsElectricConsumptionEntryModel $electricEntryModel;
    private EmsElectricConsumptionYearModel $electricYearModel;
    private EmsMobileCombustionEntryModel $mobileEntryModel;
    private EmsMobileCombustionYearModel $mobileYearModel;
    private EmsStationaryCombustionEntryModel $stationaryEntryModel;
    private EmsStationaryCombustionYearModel $stationaryYearModel;
    private EmsWaterConsumptionEntryModel $waterEntryModel;
    private EmsWaterConsumptionYearModel $waterYearModel;

    public function __construct()
    {
        $this->electricEntryModel = new EmsElectricConsumptionEntryModel();
        $this->electricYearModel = new EmsElectricConsumptionYearModel();
        $this->mobileEntryModel = new EmsMobileCombustionEntryModel();
        $this->mobileYearModel = new EmsMobileCombustionYearModel();
        $this->stationaryEntryModel = new EmsStationaryCombustionEntryModel();
        $this->stationaryYearModel = new EmsStationaryCombustionYearModel();
        $this->waterEntryModel = new EmsWaterConsumptionEntryModel();
        $this->waterYearModel = new EmsWaterConsumptionYearModel();
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
                    'subtitle' => 'Purchased electricity with intensity and emission.',
                    'href' => '/ems-reports/electric-consumption',
                    'status' => 'active',
                    'icon' => 'bi-lightning-charge',
                ],
                [
                    'title' => 'Stationary Combustion',
                    'subtitle' => 'Solar, LPG, dan Scrap sebagai sumber Scope 1.',
                    'href' => '/ems-reports/stationary-combustion',
                    'status' => 'active',
                    'icon' => 'bi-fuel-pump',
                ],
                [
                    'title' => 'Mobile Combustion',
                    'subtitle' => 'Konsumsi bahan bakar kendaraan untuk Scope 1.',
                    'href' => '/ems-reports/mobile-combustion',
                    'status' => 'active',
                    'icon' => 'bi-truck',
                ],
                [
                    'title' => 'GHG Summary',
                    'subtitle' => 'Rekap emisi tahunan dari report energi yang sudah diisi.',
                    'href' => '/ems-reports/ghg-summary',
                    'status' => 'active',
                    'icon' => 'bi-bar-chart-line',
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

    public function stationaryCombustion()
    {
        page('EMS Report - Stationary Combustion');

        $requestedYear = (int) ($this->request->getGet('year') ?: 0);
        $dataset = $this->buildStationaryDataset($requestedYear > 0 ? $requestedYear : null);

        return view('ems/combustion_report', [
            'boot' => $dataset,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
            'bootVar' => 'EMS_STATIONARY_BOOT',
            'alpineComponent' => 'emsCombustionPage',
            'saveUrl' => '/ems-reports/stationary-combustion/save',
        ]);
    }

    public function mobileCombustion()
    {
        page('EMS Report - Mobile Combustion');

        $requestedYear = (int) ($this->request->getGet('year') ?: 0);
        $dataset = $this->buildMobileDataset($requestedYear > 0 ? $requestedYear : null);

        return view('ems/combustion_report', [
            'boot' => $dataset,
            'csrfName' => csrf_token(),
            'csrfHash' => csrf_hash(),
            'bootVar' => 'EMS_MOBILE_BOOT',
            'alpineComponent' => 'emsCombustionPage',
            'saveUrl' => '/ems-reports/mobile-combustion/save',
        ]);
    }

    public function ghgSummary()
    {
        page('EMS Report - GHG Summary');

        return view('ems/ghg_summary', [
            'boot' => $this->buildGhgSummaryDataset(),
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

        return redirect()->to('/ems-reports/water-consumption?year=' . $year)
            ->with('success', 'Data water consumption berhasil disimpan.');
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

        return redirect()->to('/ems-reports/electric-consumption?year=' . $year)
            ->with('success', 'Data electric consumption berhasil disimpan.');
    }

    public function saveStationaryCombustion()
    {
        if ($this->request->isAJAX()) {
            return $this->saveCombustionAjax('stationary');
        }

        return redirect()->back();
    }

    public function saveMobileCombustion()
    {
        if ($this->request->isAJAX()) {
            return $this->saveCombustionAjax('mobile');
        }

        return redirect()->back();
    }

    private function saveWaterConsumptionAjax()
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
                'yearMeta' => $dataset['yearMeta'] ?? [],
                'monthlySummary' => $dataset['monthlySummary'] ?? [],
                'months' => $dataset['months'] ?? [],
                'emissionFactor' => $dataset['emissionFactor'] ?? self::ELECTRIC_EMISSION_FACTOR,
            ]),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function saveCombustionAjax(string $type)
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

        $sections = $type === 'stationary' ? self::STATIONARY_SECTIONS : self::MOBILE_SECTIONS;
        $sectionValues = $this->sanitizeSectionMonths($this->request->getPost('sections'), array_keys($sections));
        $productionOutput = $this->parseDecimal($this->request->getPost('production_output'));

        if ($type === 'stationary') {
            $this->persistCombustionConsumption($this->stationaryYearModel, $this->stationaryEntryModel, $year, $productionOutput, $sectionValues);
            $dataset = $this->buildStationaryDataset($year);
        } else {
            $this->persistCombustionConsumption($this->mobileYearModel, $this->mobileEntryModel, $year, $productionOutput, $sectionValues);
            $dataset = $this->buildMobileDataset($year);
        }

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Data combustion tersimpan otomatis.',
            'dataset' => $dataset,
            'summaryHtml' => view('ems/_combustion_summary_panels', [
                'reportTitle' => $dataset['reportTitle'],
                'reportSubtitle' => $dataset['reportSubtitle'],
                'sheetTitle' => $dataset['sheetTitle'],
                'companyName' => $dataset['companyName'],
                'address' => $dataset['address'],
                'baselineText' => $dataset['baselineText'],
                'years' => $dataset['years'],
                'sections' => $dataset['sections'],
                'months' => $dataset['months'],
                'sectionSummaries' => $dataset['sectionSummaries'],
                'yearMeta' => $dataset['yearMeta'],
                'totalEmissionLabel' => $dataset['totalEmissionLabel'],
            ]),
            'csrfHash' => csrf_hash(),
        ]);
    }

    private function buildWaterDataset(?int $requestedYear = null): array
    {
        $years = $this->waterYearModel->orderBy('report_year', 'ASC')->findAll();
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

        $entries = $this->waterEntryModel->orderBy('report_year', 'ASC')->orderBy('report_month', 'ASC')->findAll();
        $matrix = [];
        foreach ($entries as $entry) {
            $matrix[(int) $entry['report_year']][(int) $entry['report_month']] = (float) $entry['consumption_m3'];
        }

        $months = $this->emsMonths();
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

    private function buildElectricDataset(?int $requestedYear = null): array
    {
        [$years, $yearNumbers, $selectedYear] = $this->normalizeYearRows(
            $this->electricYearModel->orderBy('report_year', 'ASC')->findAll(),
            self::EMS_START_YEAR,
            self::EMS_RANGE_YEARS,
            $requestedYear
        );

        $entries = $this->electricEntryModel->orderBy('report_year', 'ASC')->orderBy('report_month', 'ASC')->findAll();
        $matrix = [];
        foreach ($entries as $entry) {
            $matrix[(int) $entry['report_year']][(int) $entry['report_month']] = (float) $entry['consumption_kwh'];
        }

        $months = $this->emsMonths();
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
                'trend' => $trendValues,
                'notes' => $row['notes'] ?? null,
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

    private function buildStationaryDataset(?int $requestedYear = null): array
    {
        return $this->buildCombustionDataset(
            $this->stationaryYearModel,
            $this->stationaryEntryModel,
            self::STATIONARY_SECTIONS,
            $requestedYear,
            'Stationary Combustion',
            '2026-2030 Stationary Combustion Consumption Report',
            'Baseline Year: 2026, Target: -2% s/d -5%',
            'Total Emission per Year'
        );
    }

    private function buildMobileDataset(?int $requestedYear = null): array
    {
        return $this->buildCombustionDataset(
            $this->mobileYearModel,
            $this->mobileEntryModel,
            self::MOBILE_SECTIONS,
            $requestedYear,
            'Mobile Combustion',
            '2026-2030 Mobile Combustion Consumption Report',
            'Baseline Year: 2026, Target: -2% s/d -5%',
            'Total Emission per Year'
        );
    }

    private function buildCombustionDataset($yearModel, $entryModel, array $sections, ?int $requestedYear, string $reportTitle, string $sheetTitle, string $baselineText, string $totalEmissionLabel): array
    {
        [$years, $yearNumbers, $selectedYear] = $this->normalizeYearRows(
            $yearModel->orderBy('report_year', 'ASC')->findAll(),
            self::EMS_START_YEAR,
            self::EMS_RANGE_YEARS,
            $requestedYear
        );

        $entries = $entryModel->orderBy('report_year', 'ASC')->orderBy('section_key', 'ASC')->orderBy('report_month', 'ASC')->findAll();
        $matrix = [];
        foreach ($entries as $entry) {
            $matrix[$entry['section_key']][(int) $entry['report_year']][(int) $entry['report_month']] = (float) $entry['consumption_amount'];
        }

        $months = $this->emsMonths();
        $editorYears = [];
        $yearMeta = [];
        $sectionSummaries = [];

        foreach ($years as $row) {
            $year = (int) $row['report_year'];
            $productionOutput = $row['production_output'] !== null ? (float) $row['production_output'] : null;
            $editorSections = [];
            $sectionMeta = [];
            $totalEmission = 0.0;

            foreach ($sections as $sectionKey => $sectionConfig) {
                $editorMonths = [];
                $monthlyTotal = 0.0;
                $trendValues = [];

                foreach ($months as $monthNum => $labels) {
                    $value = isset($matrix[$sectionKey][$year][$monthNum]) ? (float) $matrix[$sectionKey][$year][$monthNum] : null;
                    $editorMonths[$monthNum] = $value;
                    $monthlyTotal += (float) ($value ?? 0);
                    $trendValues[$monthNum] = (float) ($value ?? 0);
                }

                $intensity = ($productionOutput !== null && $productionOutput > 0)
                    ? ($monthlyTotal / $productionOutput)
                    : null;
                $emission = $monthlyTotal > 0 ? ($monthlyTotal * (float) $sectionConfig['emission_factor']) / 1000 : 0.0;
                $totalEmission += $emission;

                $editorSections[$sectionKey] = [
                    'months' => $editorMonths,
                ];

                $sectionMeta[$sectionKey] = [
                    'total' => $monthlyTotal,
                    'intensity' => $intensity,
                    'emission' => $emission,
                    'trend' => $trendValues,
                    'emission_factor' => (float) $sectionConfig['emission_factor'],
                ];
            }

            $editorYears[$year] = [
                'year' => $year,
                'productionOutput' => $productionOutput,
                'sections' => $editorSections,
            ];

            $yearMeta[$year] = [
                'production_output' => $productionOutput,
                'sections' => $sectionMeta,
                'total_emission' => $totalEmission,
            ];
        }

        foreach ($sections as $sectionKey => $sectionConfig) {
            $rows = [];
            foreach ($months as $monthNum => $labels) {
                $values = [];
                foreach ($yearNumbers as $index => $year) {
                    $value = $matrix[$sectionKey][$year][$monthNum] ?? null;
                    $prevYear = $yearNumbers[$index - 1] ?? null;
                    $prevValue = $prevYear ? ($matrix[$sectionKey][$prevYear][$monthNum] ?? null) : null;
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

                $rows[] = [
                    'month' => $monthNum,
                    'label' => $labels['short'],
                    'values' => $values,
                ];
            }

            $sectionSummaries[$sectionKey] = $rows;
        }

        return [
            'reportTitle' => $reportTitle,
            'reportSubtitle' => 'Input bulanan dan hitungan emisi otomatis.',
            'sheetTitle' => $sheetTitle,
            'companyName' => 'PT.Younghyun Star',
            'address' => 'Kmp. Kebon Randu RT.001/004 Ds. Sekarwangi Kec. Cibadak Kab. Sukabumi - Jawa Barat, Indonesia',
            'baselineText' => $baselineText,
            'years' => $yearNumbers,
            'selectedYear' => $selectedYear,
            'months' => $months,
            'sections' => $sections,
            'editorYears' => $editorYears,
            'yearMeta' => $yearMeta,
            'sectionSummaries' => $sectionSummaries,
            'totalEmissionLabel' => $totalEmissionLabel,
        ];
    }

    private function buildGhgSummaryDataset(): array
    {
        $stationary = $this->buildStationaryDataset(null);
        $mobile = $this->buildMobileDataset(null);
        $electric = $this->buildElectricDataset(null);

        $years = $electric['years'];
        $stationaryMeta = $stationary['yearMeta'];
        $mobileMeta = $mobile['yearMeta'];
        $electricMeta = $electric['yearMeta'];

        $rows = [
            ['scope' => 'Scope 1', 'activity' => 'Stationary combustion', 'source' => 'stationary'],
            ['scope' => '', 'activity' => 'Mobile combustion', 'source' => 'mobile'],
            ['scope' => '', 'activity' => 'Fugitive emissions from air-conditioning', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Other fugitive or process emissions', 'fixed' => null],
            ['scope' => '', 'activity' => 'Scope 1 - Total', 'formula' => 'scope1_total', 'is_total' => true],
            ['scope' => 'Scope 2', 'activity' => 'Purchased electricity - location based', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Purchased electricity - market based', 'source' => 'electric'],
            ['scope' => '', 'activity' => 'Purchased heat and steam', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Scope 2 - Location based + heat and steam', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Scope 2 - market based + heat and steam', 'formula' => 'scope2_market_total', 'is_total' => true],
            ['scope' => 'Scope 3', 'activity' => 'Purchased goods and services', 'fixed' => null],
            ['scope' => '', 'activity' => 'Capital goods', 'fixed' => null],
            ['scope' => '', 'activity' => 'Fuel-and energy-related activities (not included in scope 1 or scope 2)', 'fixed' => null],
            ['scope' => '', 'activity' => 'Upstream transportation and distribution', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Waste generated in operations', 'fixed' => null],
            ['scope' => '', 'activity' => 'Business travel', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Employee commuting', 'fixed' => 0],
            ['scope' => '', 'activity' => 'Upstream leased assets', 'fixed' => null],
            ['scope' => '', 'activity' => 'Downstream transportation and distribution', 'fixed' => null],
            ['scope' => '', 'activity' => 'Processing of sold products', 'fixed' => null],
            ['scope' => '', 'activity' => 'Use of sold products', 'fixed' => null],
            ['scope' => '', 'activity' => 'End-of-life treatment of sold products', 'fixed' => null],
            ['scope' => '', 'activity' => 'Downstream leased assets', 'fixed' => null],
            ['scope' => '', 'activity' => 'Franchises', 'fixed' => null],
            ['scope' => '', 'activity' => 'Investments', 'fixed' => null],
            ['scope' => 'Scope 1 (Biogenic)', 'activity' => '', 'fixed' => 0],
            ['scope' => 'Scope 2 (Biogenic)', 'activity' => '', 'fixed' => null],
            ['scope' => 'Scope 3 (Biogenic)', 'activity' => '', 'fixed' => 0],
            ['scope' => 'Total GHG emissions (tonnes CO2e)', 'activity' => '', 'formula' => 'grand_total', 'is_grand_total' => true],
        ];

        $compiledRows = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($years as $year) {
                $stationaryValue = (float) ($stationaryMeta[$year]['total_emission'] ?? 0);
                $mobileValue = (float) ($mobileMeta[$year]['total_emission'] ?? 0);
                $electricValue = (float) ($electricMeta[$year]['emission'] ?? 0);
                $scope1Total = $stationaryValue + $mobileValue;
                $scope2MarketTotal = $electricValue;
                $grandTotal = $scope1Total + $scope2MarketTotal;

                if (($row['source'] ?? null) === 'stationary') {
                    $values[$year] = $stationaryValue;
                } elseif (($row['source'] ?? null) === 'mobile') {
                    $values[$year] = $mobileValue;
                } elseif (($row['source'] ?? null) === 'electric') {
                    $values[$year] = $electricValue;
                } elseif (($row['formula'] ?? null) === 'scope1_total') {
                    $values[$year] = $scope1Total;
                } elseif (($row['formula'] ?? null) === 'scope2_market_total') {
                    $values[$year] = $scope2MarketTotal;
                } elseif (($row['formula'] ?? null) === 'grand_total') {
                    $values[$year] = $grandTotal;
                } else {
                    $values[$year] = $row['fixed'] ?? null;
                }
            }

            $compiledRows[] = [
                'scope' => $row['scope'],
                'activity' => $row['activity'],
                'values' => $values,
                'is_total' => !empty($row['is_total']),
                'is_grand_total' => !empty($row['is_grand_total']),
            ];
        }

        return [
            'years' => $years,
            'baselineYear' => $years[0] ?? self::EMS_START_YEAR,
            'title' => '2026-2030 GHG Calculation Per Year Based on Energy Consumption',
            'companyName' => 'PT.Younghyun Star',
            'address' => 'Kmp. Kebon Randu RT.001/004 Ds. Sekarwangi Kec. Cibadak Kab. Sukabumi - Jawa Barat, Indonesia',
            'rows' => $compiledRows,
        ];
    }

    private function persistWaterConsumption(int $year, ?float $productionOutput, array $months): void
    {
        $existingYear = $this->waterYearModel->where('report_year', $year)->first();
        if ($existingYear) {
            $this->waterYearModel->update($existingYear['id'], [
                'production_output' => $productionOutput,
            ]);
        } else {
            $this->waterYearModel->insert([
                'report_year' => $year,
                'production_output' => $productionOutput,
            ]);
        }

        for ($month = 1; $month <= 12; $month++) {
            $value = array_key_exists($month, $months) ? (float) $months[$month] : 0.0;
            $existingEntry = $this->waterEntryModel
                ->where('report_year', $year)
                ->where('report_month', $month)
                ->first();

            if ($existingEntry) {
                $this->waterEntryModel->update($existingEntry['id'], [
                    'consumption_m3' => $value,
                ]);
            } else {
                $this->waterEntryModel->insert([
                    'report_year' => $year,
                    'report_month' => $month,
                    'consumption_m3' => $value,
                ]);
            }
        }
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

    private function persistCombustionConsumption($yearModel, $entryModel, int $year, ?float $productionOutput, array $sectionValues): void
    {
        $existingYear = $yearModel->where('report_year', $year)->first();
        if ($existingYear) {
            $yearModel->update($existingYear['id'], [
                'production_output' => $productionOutput,
            ]);
        } else {
            $yearModel->insert([
                'report_year' => $year,
                'production_output' => $productionOutput,
            ]);
        }

        foreach ($sectionValues as $sectionKey => $months) {
            for ($month = 1; $month <= 12; $month++) {
                $value = array_key_exists($month, $months) ? (float) $months[$month] : 0.0;
                $existingEntry = $entryModel
                    ->where('report_year', $year)
                    ->where('section_key', $sectionKey)
                    ->where('report_month', $month)
                    ->first();

                if ($existingEntry) {
                    $entryModel->update($existingEntry['id'], [
                        'consumption_amount' => $value,
                    ]);
                } else {
                    $entryModel->insert([
                        'report_year' => $year,
                        'section_key' => $sectionKey,
                        'report_month' => $month,
                        'consumption_amount' => $value,
                    ]);
                }
            }
        }
    }

    private function normalizeYearRows(array $rows, int $startYear, int $rangeYears, ?int $requestedYear): array
    {
        $currentYear = (int) date('Y');
        $endingYear = max($startYear + $rangeYears - 1, $currentYear);
        $mapped = [];

        foreach ($rows as $row) {
            $mapped[(int) $row['report_year']] = $row;
        }

        for ($year = $startYear; $year <= $endingYear; $year++) {
            if (!isset($mapped[$year])) {
                $mapped[$year] = [
                    'report_year' => $year,
                    'production_output' => null,
                    'notes' => null,
                ];
            }
        }

        ksort($mapped);
        $years = array_values($mapped);
        $yearNumbers = array_map(static fn($row) => (int) $row['report_year'], $years);
        $selectedYear = $requestedYear && in_array($requestedYear, $yearNumbers, true)
            ? $requestedYear
            : (in_array($currentYear, $yearNumbers, true) ? $currentYear : $yearNumbers[0]);

        return [$years, $yearNumbers, $selectedYear];
    }

    private function emsMonths(): array
    {
        return [
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

    private function sanitizeSectionMonths($sections, array $sectionKeys): array
    {
        $clean = [];
        foreach ($sectionKeys as $sectionKey) {
            $clean[$sectionKey] = [];
            for ($month = 1; $month <= 12; $month++) {
                $clean[$sectionKey][$month] = $this->parseDecimal($sections[$sectionKey][$month] ?? null) ?? 0.0;
            }
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
