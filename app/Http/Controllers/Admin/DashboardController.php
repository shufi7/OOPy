<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\LearningReportService;

class DashboardController extends Controller
{
    public function index(LearningReportService $reports)
    {
        return response()->view('admin.dashboard.index', ['stats' => $reports->dashboard()])->header('Cache-Control', 'no-store, private');
    }
}
