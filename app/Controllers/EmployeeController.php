<?php

namespace App\Controllers;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;

class EmployeeController extends BaseController
{
    protected $db;

    public function __construct()
    {
        helper('audit');
        $this->db = Database::connect();
    }

    public function index()
    {
        $employees = $this->db->table('employees e')
            ->select(
                "e.*, " .
                "SUM(CASE WHEN aa.id IS NOT NULL AND aa.returned_at IS NULL THEN 1 ELSE 0 END) AS active_assets, " .
                "COUNT(aa.id) AS assignment_history",
                false
            )
            ->join('asset_assignments aa', 'aa.employee_id = e.id', 'left')
            ->groupBy('e.id')
            ->orderBy('e.status', 'ASC')
            ->orderBy('e.name', 'ASC')
            ->get()
            ->getResultArray();

        return view('employees/index', [
            'employees' => $employees,
            'title' => 'Pemegang IT',
        ]);
    }

    public function create()
    {
        return view('employees/create', [
            'employee' => null,
            'formErrors' => session('form_errors') ?? [],
            'title' => 'Tambah Pemegang IT',
        ]);
    }

    public function store()
    {
        $payload = $this->collectEmployeePayload();
        $errors = $this->validateEmployeePayload($payload);

        if ($errors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('error', reset($errors))
                ->with('form_errors', $errors);
        }

        $payload['photo'] = $this->storeUploadedPhoto($this->request->getFile('photo'));
        $payload['status'] = 'active';

        $this->db->table('employees')->insert($payload);
        $employeeId = (int) $this->db->insertID();

        if (function_exists('audit_log')) {
            audit_log('employee_create', 'Tambah pemegang IT ID ' . $employeeId);
        }

        return redirect()->to(base_url('employees'))
            ->with('success', 'Pemegang IT berhasil ditambahkan.');
    }

    public function detail($id)
    {
        $employee = $this->findEmployee((int) $id);
        if (!$employee) {
            return redirect()->to(base_url('employees'))
                ->with('error', 'Data pemegang IT tidak ditemukan.');
        }

        $assignedAssets = $this->db->table('asset_assignments aa')
            ->select(
                'a.id AS asset_id, a.inventory_no, a.asset_name, a.status, ac.sub_category, aa.assigned_at'
            )
            ->join('assets a', 'a.id = aa.asset_id')
            ->join('asset_categories ac', 'ac.id = a.category_id')
            ->where('aa.employee_id', (int) $id)
            ->where('aa.returned_at', null)
            ->orderBy('ac.sub_category', 'ASC')
            ->get()
            ->getResultArray();

        $activeAssignments = $this->countAssignments((int) $id, true);
        $assignmentHistory = $this->countAssignments((int) $id, false);

        return view('employees/detail', [
            'employee' => $employee,
            'assignedAssets' => $assignedAssets,
            'activeAssignments' => $activeAssignments,
            'assignmentHistory' => $assignmentHistory,
            'title' => 'Detail Pemegang IT',
        ]);
    }

    public function edit($id)
    {
        $employee = $this->findEmployee((int) $id);
        if (!$employee) {
            return redirect()->to(base_url('employees'))
                ->with('error', 'Data pemegang IT tidak ditemukan.');
        }

        return view('employees/edit', [
            'employee' => $employee,
            'formErrors' => session('form_errors') ?? [],
            'title' => 'Edit Pemegang IT',
        ]);
    }

    public function update($id)
    {
        $id = (int) $id;
        $employee = $this->findEmployee($id);
        if (!$employee) {
            return redirect()->to(base_url('employees'))
                ->with('error', 'Data pemegang IT tidak ditemukan.');
        }

        $payload = $this->collectEmployeePayload();
        $errors = $this->validateEmployeePayload($payload, $id);

        if ($errors !== []) {
            return redirect()->back()
                ->withInput()
                ->with('error', reset($errors))
                ->with('form_errors', $errors);
        }

        $payload['photo'] = $this->storeUploadedPhoto(
            $this->request->getFile('photo'),
            $employee['photo'] ?? null
        );

        $this->db->table('employees')
            ->where('id', $id)
            ->update($payload);

        if (function_exists('audit_log')) {
            audit_log('employee_update', 'Edit pemegang IT ID ' . $id);
        }

        return redirect()->to(base_url('employees/detail/' . $id))
            ->with('success', 'Pemegang IT berhasil diperbarui.');
    }

    public function activate($id)
    {
        return $this->setEmployeeStatus((int) $id, 'active', 'aktifkan');
    }

    public function deactivate($id)
    {
        return $this->setEmployeeStatus((int) $id, 'inactive', 'nonaktifkan');
    }

    public function delete($id)
    {
        $id = (int) $id;
        $employee = $this->findEmployee($id);
        if (!$employee) {
            return redirect()->to(base_url('employees'))
                ->with('error', 'Data pemegang IT tidak ditemukan.');
        }

        $activeAssignments = $this->countAssignments($id, true);
        if ($activeAssignments > 0) {
            return redirect()->to(base_url('employees/detail/' . $id))
                ->with('error', 'Pemegang IT masih memiliki asset aktif. Unassign dulu sebelum dihapus.');
        }

        $assignmentHistory = $this->countAssignments($id, false);
        if ($assignmentHistory > 0) {
            return redirect()->to(base_url('employees/detail/' . $id))
                ->with('warning', 'Pemegang IT ini masih punya riwayat assignment. Untuk menjaga histori, gunakan nonaktifkan saja.');
        }

        $this->deleteEmployeePhoto($employee['photo'] ?? null);
        $this->db->table('employees')->where('id', $id)->delete();

        if (function_exists('audit_log')) {
            audit_log('employee_delete', 'Hapus pemegang IT ID ' . $id);
        }

        return redirect()->to(base_url('employees'))
            ->with('success', 'Pemegang IT berhasil dihapus.');
    }

    public function unassign($employeeId, $assetId)
    {
        $employeeId = (int) $employeeId;
        $assetId = (int) $assetId;

        $this->db->table('asset_assignments')
            ->where('employee_id', $employeeId)
            ->where('asset_id', $assetId)
            ->where('returned_at', null)
            ->update([
                'returned_at' => date('Y-m-d H:i:s'),
            ]);

        if (function_exists('audit_log')) {
            audit_log(
                'unassign_asset',
                'Unassign asset ID ' . $assetId . ' dari employee ID ' . $employeeId
            );
        }

        return redirect()->back()
            ->with('success', 'Asset berhasil di-unassign.');
    }

    private function collectEmployeePayload(): array
    {
        return [
            'employee_id' => trim((string) $this->request->getPost('employee_id')),
            'name' => trim((string) $this->request->getPost('name')),
            'division' => trim((string) $this->request->getPost('division')),
            'position' => trim((string) $this->request->getPost('position')),
        ];
    }

    private function validateEmployeePayload(array $payload, int $ignoreId = 0): array
    {
        $errors = [];

        foreach ([
            'employee_id' => 'ID karyawan wajib diisi.',
            'name' => 'Nama pemegang IT wajib diisi.',
            'division' => 'Divisi wajib diisi.',
            'position' => 'Jabatan wajib diisi.',
        ] as $field => $message) {
            if ($payload[$field] === '') {
                $errors[$field] = $message;
            }
        }

        $duplicate = $this->db->table('employees')
            ->where('employee_id', $payload['employee_id']);

        if ($ignoreId > 0) {
            $duplicate->where('id !=', $ignoreId);
        }

        if ($payload['employee_id'] !== '' && $duplicate->countAllResults() > 0) {
            $errors['employee_id'] = 'ID karyawan sudah digunakan.';
        }

        $photo = $this->request->getFile('photo');
        if ($photo instanceof UploadedFile && $photo->getError() !== UPLOAD_ERR_NO_FILE) {
            if (!$photo->isValid()) {
                $errors['photo'] = 'Upload foto gagal. Silakan pilih file gambar yang valid.';
            } else {
                $validMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!in_array($photo->getMimeType(), $validMimeTypes, true)) {
                    $errors['photo'] = 'Format foto harus JPG, PNG, atau WEBP.';
                } elseif ($photo->getSizeByUnit('mb') > 2) {
                    $errors['photo'] = 'Ukuran foto maksimal 2 MB.';
                }
            }
        }

        return $errors;
    }

    private function setEmployeeStatus(int $id, string $status, string $action)
    {
        $employee = $this->findEmployee($id);
        if (!$employee) {
            return redirect()->to(base_url('employees'))
                ->with('error', 'Data pemegang IT tidak ditemukan.');
        }

        $this->db->table('employees')
            ->where('id', $id)
            ->update(['status' => $status]);

        if (function_exists('audit_log')) {
            audit_log('employee_status', ucfirst($action) . ' pemegang IT ID ' . $id);
        }

        return redirect()->back()
            ->with('success', 'Status pemegang IT berhasil diperbarui.');
    }

    private function findEmployee(int $id): ?array
    {
        $employee = $this->db->table('employees')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return is_array($employee) ? $employee : null;
    }

    private function countAssignments(int $employeeId, bool $onlyActive): int
    {
        $builder = $this->db->table('asset_assignments')
            ->where('employee_id', $employeeId);

        if ($onlyActive) {
            $builder->where('returned_at', null);
        }

        return (int) $builder->countAllResults();
    }

    private function employeePhotoDirectory(): string
    {
        return rtrim((string) FCPATH, '\\/') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'employees';
    }

    private function storeUploadedPhoto(?UploadedFile $photo, ?string $oldPhoto = null): ?string
    {
        if (!$photo instanceof UploadedFile || $photo->getError() === UPLOAD_ERR_NO_FILE) {
            return $oldPhoto;
        }

        $directory = $this->employeePhotoDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $photoName = $photo->getRandomName();
        $photo->move($directory, $photoName, true);

        if ($oldPhoto && $oldPhoto !== $photoName) {
            $this->deleteEmployeePhoto($oldPhoto);
        }

        return $photoName;
    }

    private function deleteEmployeePhoto(?string $photoName): void
    {
        $photoName = trim((string) $photoName);
        if ($photoName === '') {
            return;
        }

        $path = $this->employeePhotoDirectory() . DIRECTORY_SEPARATOR . $photoName;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
