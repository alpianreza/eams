<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index()
    {
        page('Dashboard IT');

        $db = \Config\Database::connect();

        // 🔹 TOTAL ASSET IT
        $totalIT = $db->table('assets a')
            ->join('asset_categories ac', 'ac.id = a.category_id')
            ->where('ac.category_name', 'IT')
            ->countAllResults();

        // 🔹 ASSET DIPAKAI
        $usedAsset = $db->table('asset_assignments')
            ->where('returned_at', null)
            ->countAllResults();

        // 🔹 ASSET RUSAK
        $brokenAsset = $db->table('assets')
            ->where('status', 'rusak')
            ->countAllResults();

        // 🔹 COMPLIANCE ASSET
        $complianceAsset = $db->table('assets a')
            ->join('asset_categories ac', 'ac.id = a.category_id')
            ->where('ac.category_name', 'Compliance')
            ->countAllResults();

        // 🔹 KARYAWAN PEMAKAI KOMPUTER
        $computerUsers = $db->table('asset_assignments aa')
            ->select('e.name, e.division, e.position, a.asset_name, a.inventory_no, aa.assigned_at')
            ->join('employees e', 'e.id = aa.employee_id')
            ->join('assets a', 'a.id = aa.asset_id')
            ->join('asset_categories ac', 'ac.id = a.category_id')
            ->where('ac.sub_category', 'Komputer')
            ->where('aa.returned_at', null)
            ->orderBy('aa.assigned_at', 'DESC')
            ->limit(10)
            ->get()
            ->getResult();

        $statusSummary = $db->table('assets a')
            ->select('LOWER(a.status) as status, COUNT(*) as total')
            ->join('asset_categories ac', 'ac.id = a.category_id')
            ->where('ac.category_name', 'IT')
            ->groupBy('LOWER(a.status)')
            ->get()
            ->getResult();

        return view('dashboard/index', [
            'totalIT'         => $totalIT,
            'usedAsset'       => $usedAsset,
            'brokenAsset'     => $brokenAsset,
            'complianceAsset' => $complianceAsset,
            'computerUsers'   => $computerUsers,
            'statusSummary'   => $statusSummary,
            'title'           => 'Dashboard IT'
        ]);
    }
}
