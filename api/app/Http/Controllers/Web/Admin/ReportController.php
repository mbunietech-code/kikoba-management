<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index()
    {
        return view('admin.reports.index');
    }
}
