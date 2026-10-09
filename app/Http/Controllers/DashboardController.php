<?php

namespace App\Http\Controllers;

use App\Services\DashboardProgressService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardProgressService $progress): Response
    {
        return response()->view('dashboard.index', [
            'dashboard' => $progress->forUser($request->user()),
        ])->header('Cache-Control', 'no-store, private');
    }
}
