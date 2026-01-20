<?php

namespace App\Controllers;

use Config\Database;

class EmployeeController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // list karyawan
    public function index()
    {
        $employees = $this->db->table('employees')->get()->getResultArray();

        return view('employees/index', [
            'employees' => $employees,
            'title'     => 'Data Karyawan'
        ]);
    }

    // form tambah
    public function create()
    {
        return view('employees/create', [
            'title' => 'Tambah Karyawan'
        ]);
    }

    // simpan data
    public function store()
    {
        $photoName = null;

        $photo = $this->request->getFile('photo');
        if ($photo && $photo->isValid()) {
            $photoName = $photo->getRandomName();
            $photo->move('uploads/employees', $photoName);
        }

        $this->db->table('employees')->insert([
            'employee_id' => $this->request->getPost('employee_id'),
            'name'        => $this->request->getPost('name'),
            'division'    => $this->request->getPost('division'),
            'position'    => $this->request->getPost('position'),
            'photo'       => $photoName,
            'status'      => 'active'
        ]);

        return redirect()->to('employees')
            ->with('success', 'Data karyawan berhasil ditambahkan');
    }


    // detail karyawan
    // detail karyawan
    public function detail($id)
    {
        // ambil data karyawan
        $employee = $this->db->table('employees')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        // ambil asset yang sedang dipakai karyawan
        $assignedAssets = $this->db->table('asset_assignments aa')
            ->select('
            a.id AS asset_id,
            a.inventory_no,
            a.asset_name,
            a.status,
            ac.sub_category,
            aa.assigned_at
        ')
            ->join('assets a', 'a.id = aa.asset_id')
            ->join('asset_categories ac', 'ac.id = a.category_id')
            ->where('aa.employee_id', $id)
            ->where('aa.returned_at', null)
            ->orderBy('ac.sub_category', 'ASC')
            ->get()
            ->getResult();

        return view('employees/detail', [
            'employee'       => $employee,
            'assignedAssets' => $assignedAssets,
            'title'          => 'Detail Karyawan'
        ]);
    }

    //  edit karyawan
    public function edit($id)
    {
        $employee = $this->db->table('employees')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return view('employees/edit', [
            'employee' => $employee,
            'title'    => 'Edit Karyawan'
        ]);
    }

    // update / ganti foto
    public function update($id)
    {
        $photoName = $this->request->getPost('old_photo');

        $photo = $this->request->getFile('photo');
        if ($photo && $photo->isValid()) {
            $photoName = $photo->getRandomName();
            $photo->move('uploads/employees', $photoName);
        }

        $this->db->table('employees')
            ->where('id', $id)
            ->update([
                'employee_id' => $this->request->getPost('employee_id'),
                'name'        => $this->request->getPost('name'),
                'division'    => $this->request->getPost('division'),
                'position'    => $this->request->getPost('position'),
                'photo'       => $photoName,
            ]);

        return redirect()->to('employees/detail/' . $id)
            ->with('success', 'Data karyawan berhasil diperbarui');
    }

    //nonaktifkan karyawan
    public function deactivate($id)
    {
        $this->db->table('employees')
            ->where('id', $id)
            ->update(['status' => 'inactive']);

        return redirect()->to('employees')
            ->with('success', 'Karyawan berhasil dinonaktifkan');
    }

    public function unassign($employeeId, $assetId)
    {
        $db = \Config\Database::connect();

        $db->table('asset_assignments')
            ->where('employee_id', $employeeId)
            ->where('asset_id', $assetId)
            ->where('returned_at', null)
            ->update([
                'returned_at' => date('Y-m-d H:i:s')
            ]);
        audit_log(
            'unassign_asset',
            'Unassign asset ID ' . $assetId . ' dari employee ID ' . $employeeId
        );


        return redirect()->back()
            ->with('success', 'Asset berhasil di-unassign');
    }
}
