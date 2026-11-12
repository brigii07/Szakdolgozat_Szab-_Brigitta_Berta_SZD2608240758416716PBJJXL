<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function indexDashboard(Request $request)
    {
        return view('dashboard', $this->dashboardService->overview($request->user()));
    }
}
