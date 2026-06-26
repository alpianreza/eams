<?php

namespace App\Controllers;

use App\Models\AssetModel;
use Config\Database;

class ITAssetController extends BaseController
{
    protected $assetModel;
    protected $db;

    public function __construct()
    {
        $this->assetModel = new AssetModel();
        $this->db = Database::connect();
    }

    public function index()
    {
        $type = $this->request->getGet('type');
        $keyword = $this->request->getGet('q');
        $perPage = $this->request->getGet('perPage') ?? 20;
        $perPage = in_array((int) $perPage, [20, 50, 100], true) ? (int) $perPage : 20;

        $assets = $this->buildAssetListQuery($type, $keyword)->paginate($perPage);
        $pager  = $this->assetModel->pager;
        $pager->setPath('it-assets');

        return view('it_assets/index', [
            'assets'  => $assets,
            'pager'   => $pager,
            'type'    => $type,
            'perPage' => $perPage,
            'keyword' => $keyword,
            'title'   => 'Inventaris IT',
        ]);
    }

    public function ajax()
    {
        $type = $this->request->getGet('type');
        $keyword = $this->request->getGet('q');
        $perPage = $this->request->getGet('perPage') ?? 20;
        $perPage = in_array((int) $perPage, [20, 50, 100], true) ? (int) $perPage : 20;

        $assets = $this->buildAssetListQuery($type, $keyword)->paginate($perPage);
        $pager  = $this->assetModel->pager;
        $pager->setPath('it-assets');

        return view('it_assets/_table', [
            'assets' => $assets,
            'pager' => $pager,
        ]);
    }

    public function detail($id)
    {
        $db = \Config\Database::connect();

        $asset = $this->assetModel->find($id);

        $currentEmployee = $db->table('asset_assignments aa')
            ->select('
        e.name,
        e.employee_id,
        e.division,
        e.position,
        aa.assigned_at
    ')
            ->join('employees e', 'e.id = aa.employee_id')
            ->where('aa.asset_id', $id)
            ->where('aa.returned_at', null)
            ->get()
            ->getRow();

        $history = $db->table('asset_assignments aa')
            ->select('
        e.name,
        e.employee_id,
        e.division,
        e.position,
        aa.assigned_at,
        aa.returned_at
    ')
            ->join('employees e', 'e.id = aa.employee_id')
            ->where('aa.asset_id', $id)
            ->orderBy('aa.assigned_at', 'DESC')
            ->get()
            ->getResult();

        return view('it_assets/detail', [
            'asset'           => $asset,
            'currentEmployee' => $currentEmployee,
            'history'         => $history,
            'title'           => 'Detail Asset IT',
        ]);
    }

    public function assignForm($assetId)
    {
        $db = \Config\Database::connect();

        $asset = $this->assetModel->find($assetId);

        $employees = $db->table('employees')
            ->where('status', 'active')
            ->get()
            ->getResultArray();

        return view('it_assets/assign', [
            'asset'     => $asset,
            'employees' => $employees,
            'title'     => 'Assign Asset ke Karyawan',
        ]);
    }

    public function assignSave($assetId)
    {
        if (! hasWriteAccess()) {
            return redirect()->back()
                ->with('error', 'Anda hanya punya akses baca');
        }

        $db = \Config\Database::connect();
        $employeeId = $this->request->getPost('employee_id');

        $db->table('asset_assignments')
            ->where('asset_id', $assetId)
            ->where('returned_at', null)
            ->update([
                'returned_at' => date('Y-m-d H:i:s'),
            ]);

        $db->table('asset_assignments')->insert([
            'asset_id'    => $assetId,
            'employee_id' => $employeeId,
            'assigned_at' => date('Y-m-d H:i:s'),
            'returned_at' => null,
        ]);

        audit_log(
            'assign_asset',
            'Assign asset ID ' . $assetId . ' ke employee ID ' . $employeeId
        );

        return redirect()->to('it-assets/detail/' . $assetId)
            ->with('success', 'Asset berhasil di-assign');
    }

    public function create()
    {
        $categories = $this->db->table('asset_categories')
            ->where('category_name', 'IT')
            ->get()
            ->getResultArray();

        return view('it_assets/create', [
            'categories' => $categories,
            'title'      => 'Tambah Asset IT',
        ]);
    }

    public function store()
    {
        if (! hasWriteAccess()) {
            return redirect()->back()
                ->with('error', 'Anda hanya punya akses baca');
        }

        $photoName = null;
        $photo = $this->request->getFile('photo');
        if ($photo && $photo->isValid()) {
            $photoName = $photo->getRandomName();
            $photo->move('uploads/assets', $photoName);
        }

        $this->assetModel->insert([
            'inventory_no'  => $this->request->getPost('inventory_no'),
            'category_id'   => $this->request->getPost('category_id'),
            'asset_name'    => $this->request->getPost('asset_name'),
            'brand'         => $this->request->getPost('brand'),
            'serial_number' => $this->request->getPost('serial_number'),
            'photo'         => $photoName,
            'status'        => $this->request->getPost('status'),
            'location'      => $this->request->getPost('location'),
        ]);

        return redirect()->to('it-assets')
            ->with('success', 'Asset berhasil ditambahkan');
    }

    public function edit($id)
    {
        $asset = $this->assetModel->find($id);

        $categories = $this->db->table('asset_categories')
            ->where('category_name', 'IT')
            ->get()
            ->getResultArray();

        return view('it_assets/edit', [
            'asset'      => $asset,
            'categories' => $categories,
            'title'      => 'Edit Asset IT',
        ]);
    }

    public function update($id)
    {
        $db = \Config\Database::connect();
        $newStatus = $this->request->getPost('status');
        $photoName = $this->request->getPost('old_photo');

        $photo = $this->request->getFile('photo');
        if ($photo && $photo->isValid()) {
            $photoName = $photo->getRandomName();
            $photo->move('uploads/assets', $photoName);
        }

        $this->assetModel->update($id, [
            'inventory_no'  => $this->request->getPost('inventory_no'),
            'category_id'   => $this->request->getPost('category_id'),
            'asset_name'    => $this->request->getPost('asset_name'),
            'brand'         => $this->request->getPost('brand'),
            'serial_number' => $this->request->getPost('serial_number'),
            'purchase_date' => $this->request->getPost('purchase_date') ?: null,
            'photo'         => $photoName,
            'status'        => $newStatus,
            'location'      => $this->request->getPost('location'),
        ]);

        if ($newStatus === 'rusak') {
            $db->table('asset_assignments')
                ->where('asset_id', $id)
                ->where('returned_at', null)
                ->update([
                    'returned_at' => date('Y-m-d H:i:s'),
                ]);
        }

        return redirect()->to('it-assets/detail/' . $id)
            ->with('success', 'Asset berhasil diperbarui');
    }

    private function buildAssetListQuery(?string $type, ?string $keyword)
    {
        $builder = $this->assetModel
            ->select('assets.*')
            ->join('asset_categories', 'asset_categories.id = assets.category_id')
            ->where('asset_categories.category_name', 'IT');

        if ($type) {
            if ($type === 'Peripheral') {
                $builder->whereIn('asset_categories.sub_category', ['Mouse', 'Keyboard', 'Monitor']);
            } else {
                $builder->where('asset_categories.sub_category', $type);
            }
        }

        if ($keyword) {
            $builder->groupStart()
                ->like('assets.inventory_no', $keyword)
                ->orLike('assets.asset_name', $keyword)
                ->orLike('assets.brand', $keyword)
                ->orLike('assets.location', $keyword)
                ->groupEnd();
        }

        return $builder->orderBy('assets.id', 'DESC');
    }
}
