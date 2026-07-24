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
    if (! in_array(session('role'), ['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    $model = new HolidayModel();

    $model->insert([
      'holiday_date' => $this->request->getPost('holiday_date'),
      'description'  => $this->request->getPost('description'),
    ]);

    return redirect()->back()->with('success', 'Hari libur ditambahkan');
  }

  public function delete($id)
  {
    if (session('role') !== 'admin') {
      return redirect()->back();
    }

    $model = new HolidayModel();
    $model->delete($id);

    return redirect()->back()->with('success', 'Hari libur dihapus');
  }

  public function update($id)
  {
    if (! in_array(session('role'), ['admin', 'compliance'])) {
      return redirect()->to('/unauthorized');
    }

    $model = new \App\Models\HolidayModel();

    $model->update($id, [
      'holiday_date' => $this->request->getPost('holiday_date'),
      'description'  => $this->request->getPost('description'),
    ]);

    return redirect()->back()->with('success', 'Hari libur diperbarui');
  }
}
