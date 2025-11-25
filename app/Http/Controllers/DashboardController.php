<?php

namespace App\Http\Controllers;

use App\Models\Conductor;

class DashboardController extends Controller
{
    public function index()
    {
        $conductoresCount = Conductor::count();

        return view('dashboard', compact('conductoresCount'));
    }
}

