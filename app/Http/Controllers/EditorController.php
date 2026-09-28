<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class EditorController extends Controller
{
    public function index(): View
    {
        return view('editor.index', ['exercises' => require resource_path('live-code/demos.php')]);
    }
}
