<?php

namespace App\Controllers;

class ITController extends BaseController
{
    public function index()
    {
        page('IT Center');

        return view('it/index', [
            'title' => 'IT Center',
        ]);
    }
}
