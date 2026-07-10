<?php

namespace App\Controllers;

use App\Models\HolidayModel;

class HolidayController extends BaseController
{
  public function index()
  {
    page('Kelola Hari Libur Nasional');

    if (! in_array(session('role'), ['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    $year = $this->request->getGet('year') ?? date('Y');

    $model = new HolidayModel();

    $holidays = $model
      ->where('YEAR(holiday_date)', $year)
      ->orderBy('holiday_date', 'ASC')
      ->findAll();

    return view('holidays/index', [
      'holidays' => $holidays,
      'year'     => $year
    ]);
  }

  public function store()
  {
    helper('audit');

    if (! in_array(session('role'), ['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    $model = new HolidayModel();

    $date = $this->request->getPost('holiday_date');
    $description = $this->request->getPost('description');

    $model->insert([
      'holiday_date' => $date,
      'description'  => $description,
    ]);

    audit_log('holiday_create', 'Menambah hari libur: ' . $description . ' tanggal ' . $date);

    return redirect()->back()->with('success', 'Hari libur ditambahkan');
  }

  public function delete($id)
  {
    helper('audit');

    if (session('role') !== 'admin') {
      return redirect()->back();
    }

    $model = new HolidayModel();

    $holiday = $model->find($id);
    $model->delete($id);

    $holidayDesc = $holiday['description'] ?? 'ID ' . $id;
    audit_log('holiday_delete', 'Menghapus hari libur: ' . $holidayDesc);

    return redirect()->back()->with('success', 'Hari libur dihapus');
  }

  public function update($id)
  {
    helper('audit');

    if (! in_array(session('role'), ['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    $model = new \App\Models\HolidayModel();

    $date = $this->request->getPost('holiday_date');
    $description = $this->request->getPost('description');

    $model->update($id, [
      'holiday_date' => $date,
      'description'  => $description,
    ]);

    audit_log('holiday_update', 'Mengupdate hari libur ID ' . $id . ': ' . $description . ' tanggal ' . $date);

    return redirect()->back()->with('success', 'Hari libur diperbarui');
  }
}
