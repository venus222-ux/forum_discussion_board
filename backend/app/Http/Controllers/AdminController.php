<?php

namespace App\Http\Controllers;

use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(
        private AdminDashboardService $service
    ) {}

    public function dashboard(Request $request)
    {
        return response()->json(
            $this->service->getDashboardData($request->query('range', '30d'))
        );
    }
}
