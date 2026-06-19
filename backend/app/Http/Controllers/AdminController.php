<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\AdminDashboardService;

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
